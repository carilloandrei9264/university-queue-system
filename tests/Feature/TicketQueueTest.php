<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketQueueService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_student_gets_daily_department_ticket_numbers_and_cannot_hold_two_active_tickets(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-07 08:00:00', 'Asia/Manila'));

        $service = $this->serviceFor('AMS');
        $firstStudent = $this->student('student001@example.com');
        $secondStudent = $this->student('student002@example.com');
        $queue = app(TicketQueueService::class);

        $first = $queue->createTicket($firstStudent, $service->id);
        $second = $queue->createTicket($secondStudent, $service->id);

        $this->assertSame('AMS-001', $first->code);
        $this->assertSame('AMS-002', $second->code);
        $this->assertSame('2026-11-07', $first->queue_date->toDateString());
        $this->assertSame(2, DB::table('daily_counters')->where('department_id', $service->department_id)->value('last_number'));
        $this->assertSame(2, DB::table('audit_logs')->where('action', 'ticket.created')->count());

        try {
            $queue->createTicket($firstStudent, $service->id);
            $this->fail('A student with an active ticket must not receive another ticket.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('ticket', $exception->errors());
        }

        Carbon::setTestNow();
    }

    public function test_successful_ticket_mutations_are_audited_and_failed_actions_are_not(): void
    {
        $queue = app(TicketQueueService::class);
        $service = $this->serviceFor('AMS');
        $student = $this->student('student001@example.com');
        $staff = User::where('email', 'admission.staff@example.com')->firstOrFail();

        $ticket = $queue->createTicket($student, $service->id);
        $queue->callNext($staff);
        $queue->skipTicket($staff, $ticket->id);
        $queue->recallTicket($staff, $ticket->id);
        $queue->completeTicket($staff, $ticket->id);

        $cancelledTicket = $queue->createTicket($student, $service->id);
        $queue->cancelTicket($student, $cancelledTicket->id);

        $this->assertSame([
            'ticket.created',
            'ticket.called',
            'ticket.skipped',
            'ticket.recalled',
            'ticket.completed',
            'ticket.created',
            'ticket.cancelled',
        ], DB::table('audit_logs')->orderBy('id')->pluck('action')->all());

        try {
            $queue->completeTicket($staff, $ticket->id);
            $this->fail('A completed ticket cannot be completed a second time.');
        } catch (ValidationException) {
            $this->assertSame(7, DB::table('audit_logs')->count());
        }
    }

    public function test_ticket_numbers_restart_for_a_new_manila_queue_date(): void
    {
        $queue = app(TicketQueueService::class);
        $service = $this->serviceFor('RGS');

        Carbon::setTestNow(Carbon::parse('2026-11-07 23:59:00', 'Asia/Manila'));
        $yesterdayTicket = $queue->createTicket($this->student('student001@example.com'), $service->id);

        Carbon::setTestNow(Carbon::parse('2026-11-08 00:01:00', 'Asia/Manila'));
        $todayTicket = $queue->createTicket($this->student('student002@example.com'), $service->id);

        $this->assertSame('RGS-001', $yesterdayTicket->code);
        $this->assertSame('RGS-001', $todayTicket->code);
        $this->assertNotSame($yesterdayTicket->queue_date->toDateString(), $todayTicket->queue_date->toDateString());

        Carbon::setTestNow();
    }

    public function test_inactive_departments_and_services_cannot_receive_tickets(): void
    {
        $queue = app(TicketQueueService::class);
        $student = $this->student('student001@example.com');
        $service = $this->serviceFor('ACC');

        $service->update(['is_active' => false]);

        try {
            $queue->createTicket($student, $service->id);
            $this->fail('An inactive service must reject ticket creation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('service_id', $exception->errors());
        }

        $service->update(['is_active' => true]);
        $service->department->update(['is_active' => false]);

        try {
            $queue->createTicket($student, $service->id);
            $this->fail('An inactive department must reject ticket creation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('service_id', $exception->errors());
        }

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_staff_calls_the_oldest_ticket_in_only_their_own_department(): void
    {
        $queue = app(TicketQueueService::class);
        $admission = $this->serviceFor('AMS');
        $registrar = $this->serviceFor('RGS');
        $first = $queue->createTicket($this->student('student001@example.com'), $admission->id);
        $second = $queue->createTicket($this->student('student002@example.com'), $admission->id);
        $otherOffice = $queue->createTicket($this->student('student003@example.com'), $registrar->id);
        $staff = User::where('email', 'admission.staff@example.com')->firstOrFail();

        $called = $queue->callNext($staff);

        $this->assertSame($first->id, $called?->id);
        $this->assertSame('serving', $called?->status);
        $this->assertSame($staff->id, $called?->handled_by);
        $this->assertSame('waiting', $second->fresh()->status);
        $this->assertSame('waiting', $otherOffice->fresh()->status);
        $this->assertSame($second->id, $queue->callNext($staff)?->id);
        $this->assertNull($queue->callNext($staff));
    }

    public function test_students_can_cancel_only_their_own_waiting_tickets(): void
    {
        $queue = app(TicketQueueService::class);
        $service = $this->serviceFor('AMS');
        $student = $this->student('student001@example.com');
        $ticket = $queue->createTicket($student, $service->id);

        $this->expectException(AuthorizationException::class);
        $queue->cancelTicket($this->student('student002@example.com'), $ticket->id);
    }

    public function test_student_can_cancel_waiting_ticket_but_not_serving_ticket(): void
    {
        $queue = app(TicketQueueService::class);
        $service = $this->serviceFor('AMS');
        $student = $this->student('student001@example.com');
        $waiting = $queue->createTicket($student, $service->id);

        $cancelled = $queue->cancelTicket($student, $waiting->id);
        $this->assertSame('cancelled', $cancelled->status);
        $this->assertNotNull($cancelled->cancelled_at);

        $serving = $queue->createTicket($student, $service->id);
        $queue->callNext(User::where('email', 'admission.staff@example.com')->firstOrFail());

        try {
            $queue->cancelTicket($student, $serving->id);
            $this->fail('A serving ticket cannot be cancelled by a student.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('ticket', $exception->errors());
        }
    }

    public function test_staff_can_complete_skip_and_recall_with_valid_transitions(): void
    {
        $queue = app(TicketQueueService::class);
        $service = $this->serviceFor('AMS');
        $staff = User::where('email', 'admission.staff@example.com')->firstOrFail();
        $student = $this->student('student001@example.com');

        $completed = $queue->createTicket($student, $service->id);
        $queue->callNext($staff);
        $completed = $queue->completeTicket($staff, $completed->id);
        $this->assertSame('completed', $completed->status);
        $this->assertNotNull($completed->completed_at);

        $skipped = $queue->createTicket($student, $service->id);
        $queue->callNext($staff);
        $skipped = $queue->skipTicket($staff, $skipped->id);
        $this->assertSame('skipped', $skipped->status);
        $this->assertNotNull($skipped->completed_at);

        $recalled = $queue->recallTicket($staff, $skipped->id);
        $this->assertSame('serving', $recalled->status);
        $this->assertNull($recalled->completed_at);

        $queue->completeTicket($staff, $recalled->id);
        $this->assertSame('completed', $recalled->fresh()->status);
    }

    public function test_staff_cannot_manage_tickets_in_another_department(): void
    {
        $queue = app(TicketQueueService::class);
        $ticket = $queue->createTicket($this->student('student001@example.com'), $this->serviceFor('RGS')->id);
        $staff = User::where('email', 'admission.staff@example.com')->firstOrFail();

        $this->expectException(AuthorizationException::class);
        $queue->completeTicket($staff, $ticket->id);
    }

    public function test_only_active_users_with_the_correct_role_can_operate_the_queue(): void
    {
        $service = $this->serviceFor('AMS');
        $queue = app(TicketQueueService::class);
        $student = $this->student('student001@example.com');

        try {
            $queue->createTicket(User::where('role', 'admin')->firstOrFail(), $service->id);
            $this->fail('Admins cannot take student tickets.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('tickets', 0);
        }

        $inactiveStudent = $this->student('student002@example.com');
        $inactiveStudent->update(['is_active' => false]);

        try {
            $queue->createTicket($inactiveStudent, $service->id);
            $this->fail('An inactive student cannot take tickets.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('tickets', 0);
        }

        $ticket = $queue->createTicket($student, $service->id);
        $queue->callNext(User::where('email', 'admission.staff@example.com')->firstOrFail());
        $inactiveStaff = User::where('email', 'admission.staff@example.com')->firstOrFail();
        $inactiveStaff->update(['is_active' => false]);

        $this->expectException(AuthorizationException::class);
        $queue->completeTicket($inactiveStaff, $ticket->id);
    }

    public function test_student_ticket_form_creates_and_displays_only_their_ticket(): void
    {
        $student = $this->student('student001@example.com');
        $service = $this->serviceFor('AMS');

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee($service->name);

        $this->actingAs($student)
            ->post(route('student.tickets.store'), ['service_id' => $service->id])
            ->assertRedirect(route('student.dashboard'));

        $ticket = Ticket::firstOrFail();

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee($ticket->code)
            ->assertSee('People ahead')
            ->assertSee('<strong>0</strong>', false);
    }

    public function test_staff_queue_routes_run_call_and_completion_actions(): void
    {
        $ticket = app(TicketQueueService::class)
            ->createTicket($this->student('student001@example.com'), $this->serviceFor('RGS')->id);
        $staff = User::where('email', 'registrar.staff@example.com')->firstOrFail();

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee($ticket->code);

        $this->actingAs($staff)
            ->post(route('staff.call-next'))
            ->assertRedirect(route('staff.dashboard'));

        $this->actingAs($staff)
            ->post(route('staff.tickets.complete', $ticket))
            ->assertRedirect(route('staff.dashboard'));

        $this->assertSame('completed', $ticket->fresh()->status);
    }

    private function serviceFor(string $prefix): Service
    {
        $department = Department::where('prefix', $prefix)->firstOrFail();

        return Service::where('department_id', $department->id)->firstOrFail();
    }

    private function student(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }
}
