<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Service;
use App\Models\User;
use App\Services\TicketQueueService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceWindowMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_migration_refuses_to_run_while_a_ticket_is_being_served(): void
    {
        $department = Department::where('prefix', 'AMS')->firstOrFail();
        $service = Service::where('department_id', $department->id)->firstOrFail();
        $student = User::where('email', 'student001@example.com')->firstOrFail();
        $staff = User::where('email', 'admission.staff@example.com')->firstOrFail();
        $queue = app(TicketQueueService::class);
        $ticket = $queue->createTicket($student, $service->id);
        $queue->callNext($staff);
        $migration = require database_path('migrations/2026_11_08_000007_create_service_windows_table.php');

        try {
            $migration->up();
            $this->fail('The service-window migration must not run while a ticket is being served.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                "Complete or skip today's serving tickets before adding service windows.",
                $exception->getMessage(),
            );
        }

        $this->assertSame('serving', $ticket->fresh()->status);
        $this->assertSame(3, $department->windows()->count());
    }
}
