<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketQueueService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueueConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->app['db']->connection();
        $driver = $connection->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Queue concurrency requires MySQL or MariaDB row locks.');
        }

        $this->exposeTestConnectionToWorkers($driver);
        Concurrency::driver('process');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_concurrent_ticket_creation_assigns_unique_daily_numbers(): void
    {
        $service = $this->admissionService();
        $firstStudentId = User::where('email', 'student001@example.com')->value('id');
        $secondStudentId = User::where('email', 'student002@example.com')->value('id');
        $serviceId = $service->id;

        $tickets = Concurrency::run([
            static function () use ($firstStudentId, $serviceId): array {
                $ticket = app(TicketQueueService::class)->createTicket(
                    User::query()->findOrFail($firstStudentId),
                    $serviceId,
                );

                return ['id' => $ticket->id, 'number' => $ticket->number, 'code' => $ticket->code];
            },
            static function () use ($secondStudentId, $serviceId): array {
                $ticket = app(TicketQueueService::class)->createTicket(
                    User::query()->findOrFail($secondStudentId),
                    $serviceId,
                );

                return ['id' => $ticket->id, 'number' => $ticket->number, 'code' => $ticket->code];
            },
        ], timeout: 30);

        $numbers = array_column($tickets, 'number');
        sort($numbers);

        $this->assertSame([1, 2], $numbers);
        $this->assertSame(2, Ticket::query()->count());
        $this->assertSame(2, Ticket::query()->distinct('number')->count('number'));
        $this->assertSame(2, $this->activityCount('ticket.created'));
    }

    public function test_concurrent_call_next_actions_assign_distinct_tickets_in_queue_order(): void
    {
        $queue = app(TicketQueueService::class);
        $service = $this->admissionService();
        $firstStudent = User::where('email', 'student001@example.com')->firstOrFail();
        $secondStudent = User::where('email', 'student002@example.com')->firstOrFail();
        $firstTicket = $queue->createTicket($firstStudent, $service->id);
        $secondTicket = $queue->createTicket($secondStudent, $service->id);
        $staff = User::where('email', 'admission.staff@example.com')->firstOrFail();
        $secondStaff = User::create([
            'full_name' => 'Second Admission Staff',
            'email' => 'second.admission.staff@example.com',
            'password' => 'temporary-password',
            'role' => 'staff',
            'department_id' => $staff->department_id,
            'is_active' => true,
        ]);
        $staff->department->windows()->where('position', 2)->update(['staff_id' => $secondStaff->id]);
        $staffId = $staff->id;
        $secondStaffId = $secondStaff->id;

        $calledIds = Concurrency::run([
            static function () use ($staffId): ?int {
                return app(TicketQueueService::class)
                    ->callNext(User::query()->findOrFail($staffId))
                    ?->id;
            },
            static function () use ($secondStaffId): ?int {
                return app(TicketQueueService::class)
                    ->callNext(User::query()->findOrFail($secondStaffId))
                    ?->id;
            },
        ], timeout: 30);

        $calledIdsInOrder = array_values($calledIds);
        sort($calledIdsInOrder);
        $expectedTicketIds = [$firstTicket->id, $secondTicket->id];
        sort($expectedTicketIds);
        $this->assertSame($expectedTicketIds, $calledIdsInOrder);
        $this->assertSame(
            [$firstTicket->id, $secondTicket->id],
            DB::table('audit_logs')
                ->where('action', 'ticket.called')
                ->orderBy('id')
                ->pluck('target_id')
                ->map(static fn ($id): int => (int) $id)
                ->all(),
        );
        $this->assertSame(
            2,
            Ticket::query()
                ->whereIn('id', [$firstTicket->id, $secondTicket->id])
                ->distinct('service_window_id')
                ->count('service_window_id'),
        );
        $this->assertSame(2, Ticket::query()->where('status', 'serving')->count());
        $this->assertSame(2, $this->activityCount('ticket.called'));
    }

    private function admissionService(): Service
    {
        $department = Department::where('prefix', 'AMS')->firstOrFail();

        return Service::where('department_id', $department->id)->firstOrFail();
    }

    private function activityCount(string $action): int
    {
        return (int) $this->app['db']->table('audit_logs')->where('action', $action)->count();
    }

    private function exposeTestConnectionToWorkers(string $driver): void
    {
        $variables = [
            'DB_CONNECTION' => $driver,
            'DB_DATABASE' => config("database.connections.{$driver}.database"),
            'DB_URL' => '',
        ];

        foreach ($variables as $name => $value) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
