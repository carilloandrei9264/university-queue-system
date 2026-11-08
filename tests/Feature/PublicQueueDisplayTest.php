<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketQueueService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicQueueDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-11-08 10:00:00', 'Asia/Manila'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_display_office_picker_is_public_and_lists_active_departments_only(): void
    {
        Department::where('prefix', 'RGS')->update(['is_active' => false]);

        $this->get(route('display.index'))
            ->assertOk()
            ->assertSee('Admission')
            ->assertSee('Accounting')
            ->assertDontSee('Registrar');
    }

    public function test_department_display_shows_serving_and_next_ticket_without_student_details(): void
    {
        $department = Department::where('prefix', 'AMS')->firstOrFail();
        $service = Service::where('department_id', $department->id)->firstOrFail();
        $student = User::where('email', 'student001@example.com')->firstOrFail();
        $nextStudent = User::where('email', 'student002@example.com')->firstOrFail();
        $queue = app(TicketQueueService::class);

        $serving = $queue->createTicket($student, $service->id);
        $next = $queue->createTicket($nextStudent, $service->id);
        $queue->callNext(User::where('email', 'admission.staff@example.com')->firstOrFail());

        $this->get(route('display.show', $department->prefix))
            ->assertOk()
            ->assertSee($department->name)
            ->assertSee($serving->code)
            ->assertSee('Window 1')
            ->assertSee('Window 2')
            ->assertSee('Ready')
            ->assertSee($next->code)
            ->assertSee('content="5"', false)
            ->assertDontSee($student->full_name)
            ->assertDontSee($student->email)
            ->assertDontSee($student->student_id)
            ->assertDontSee($nextStudent->full_name)
            ->assertDontSee($nextStudent->email)
            ->assertDontSee($nextStudent->student_id);
    }

    public function test_display_shows_each_window_break_state_and_next_waiting_ticket(): void
    {
        $department = Department::where('prefix', 'AMS')->firstOrFail();
        $staff = User::where('email', 'admission.staff@example.com')->firstOrFail();
        $ticket = app(TicketQueueService::class)
            ->createTicket(User::where('email', 'student001@example.com')->firstOrFail(), Service::where('department_id', $department->id)->value('id'));

        app(TicketQueueService::class)->setWindowBreak($staff, true);

        $this->get(route('display.show', $department->prefix))
            ->assertOk()
            ->assertSee('Window 1')
            ->assertSee('On break')
            ->assertSee('Window 2')
            ->assertSee('Ready')
            ->assertSee($ticket->code);
    }

    public function test_display_does_not_show_tickets_from_another_department_or_queue_date(): void
    {
        $admission = Department::where('prefix', 'AMS')->firstOrFail();
        $registrar = Department::where('prefix', 'RGS')->firstOrFail();
        $admissionService = Service::where('department_id', $admission->id)->firstOrFail();
        $registrarService = Service::where('department_id', $registrar->id)->firstOrFail();
        $firstStudent = User::where('email', 'student001@example.com')->firstOrFail();
        $secondStudent = User::where('email', 'student002@example.com')->firstOrFail();
        $thirdStudent = User::where('email', 'student003@example.com')->firstOrFail();
        $queue = app(TicketQueueService::class);

        $servingTicket = $queue->createTicket($firstStudent, $admissionService->id);
        $yesterdayTicket = $queue->createTicket($secondStudent, $admissionService->id);
        $otherDepartmentTicket = $queue->createTicket($thirdStudent, $registrarService->id);
        Ticket::whereKey($yesterdayTicket->id)->update(['queue_date' => '2026-11-07']);
        $queue->callNext(User::where('email', 'admission.staff@example.com')->firstOrFail());

        $this->get(route('display.show', $admission->prefix))
            ->assertOk()
            ->assertSee($servingTicket->code)
            ->assertDontSee($yesterdayTicket->code)
            ->assertDontSee($otherDepartmentTicket->code)
            ->assertSee('No tickets waiting.');
    }

    public function test_inactive_departments_have_no_public_display(): void
    {
        $department = Department::where('prefix', 'AMS')->firstOrFail();
        $department->update(['is_active' => false]);

        $this->get(route('display.show', $department->prefix))->assertNotFound();
    }
}
