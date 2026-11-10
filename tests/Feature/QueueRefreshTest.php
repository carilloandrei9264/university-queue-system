<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Service;
use App\Models\User;
use App\Services\TicketQueueService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueRefreshTest extends TestCase
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

    public function test_student_refresh_shows_only_their_current_ticket_and_updates_its_status(): void
    {
        $student = User::where('email', 'student001@example.com')->firstOrFail();
        $otherStudent = User::where('email', 'student002@example.com')->firstOrFail();
        $service = $this->serviceFor('AMS');
        $queue = app(TicketQueueService::class);
        $ticket = $queue->createTicket($student, $service->id);
        $otherTicket = $queue->createTicket($otherStudent, $service->id);

        $this->actingAs($student)
            ->get(route('student.queue-status'))
            ->assertOk()
            ->assertSee($ticket->code)
            ->assertDontSee($otherTicket->code)
            ->assertSee('People ahead')
            ->assertSee('class="student-people-ahead">0</strong>', false);

        $queue->callNext(User::where('email', 'admission.staff@example.com')->firstOrFail());

        $this->actingAs($student)
            ->get(route('student.queue-status'))
            ->assertOk()
            ->assertSee('Now serving')
            ->assertSee('Please proceed to Window 1.')
            ->assertDontSee('Cancel this ticket');
    }

    public function test_staff_refresh_is_limited_to_their_department(): void
    {
        $admissionTicket = app(TicketQueueService::class)
            ->createTicket(User::where('email', 'student001@example.com')->firstOrFail(), $this->serviceFor('AMS')->id);
        $registrarTicket = app(TicketQueueService::class)
            ->createTicket(User::where('email', 'student002@example.com')->firstOrFail(), $this->serviceFor('RGS')->id);

        $this->actingAs(User::where('email', 'admission.staff@example.com')->firstOrFail())
            ->get(route('staff.queue-status'))
            ->assertOk()
            ->assertSee($admissionTicket->code)
            ->assertSee('Ticket list')
            ->assertSee('1 waiting')
            ->assertDontSee($registrarTicket->code);
    }

    public function test_queue_refresh_routes_require_the_matching_role(): void
    {
        $student = User::where('email', 'student001@example.com')->firstOrFail();
        $staff = User::where('email', 'admission.staff@example.com')->firstOrFail();

        $this->actingAs($student)->get(route('staff.queue-status'))->assertForbidden();
        $this->flushSession();
        $this->actingAs($staff)->get(route('student.queue-status'))->assertForbidden();
    }

    private function serviceFor(string $prefix): Service
    {
        $department = Department::where('prefix', $prefix)->firstOrFail();

        return Service::where('department_id', $department->id)->firstOrFail();
    }
}
