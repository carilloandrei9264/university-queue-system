<?php

namespace App\Services;

use App\Domain\TicketStatus;
use App\Models\DailyCounter;
use App\Models\Department;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketQueueService
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function createTicket(User $student, int $serviceId): Ticket
    {
        return DB::transaction(function () use ($student, $serviceId): Ticket {
            $lockedStudent = User::query()->whereKey($student->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureStudentCanQueue($lockedStudent);

            $activeTicket = Ticket::query()
                ->where('student_id', $lockedStudent->id)
                ->whereIn('status', [TicketStatus::Waiting->value, TicketStatus::Serving->value])
                ->first();

            if ($activeTicket !== null) {
                throw ValidationException::withMessages([
                    'ticket' => 'You already have an active ticket. Finish or cancel it before taking another.',
                ]);
            }

            $service = Service::query()->whereKey($serviceId)->lockForUpdate()->first();

            if ($service === null || ! $service->is_active) {
                throw ValidationException::withMessages([
                    'service_id' => 'Choose an active service.',
                ]);
            }

            $department = Department::query()
                ->whereKey($service->department_id)
                ->lockForUpdate()
                ->first();

            if ($department === null || ! $department->is_active) {
                throw ValidationException::withMessages([
                    'service_id' => 'This office is not currently accepting tickets.',
                ]);
            }

            $queueDate = Carbon::now(config('app.timezone'))->toDateString();

            DB::table('daily_counters')->insertOrIgnore([
                'department_id' => $department->id,
                'queue_date' => $queueDate,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = DailyCounter::query()
                ->where('department_id', $department->id)
                ->where('queue_date', $queueDate)
                ->lockForUpdate()
                ->firstOrFail();

            $number = $counter->last_number + 1;
            $counter->update(['last_number' => $number]);

            $ticket = Ticket::create([
                'department_id' => $department->id,
                'service_id' => $service->id,
                'student_id' => $lockedStudent->id,
                'queue_date' => $queueDate,
                'number' => $number,
                'code' => $department->prefix.'-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'status' => TicketStatus::Waiting->value,
            ]);
            $this->activityLogger->record($lockedStudent, 'ticket.created', 'ticket', $ticket->id, $department->id);

            return $ticket;
        }, attempts: 3);
    }

    public function callNext(User $staff): ?Ticket
    {
        return DB::transaction(function () use ($staff): ?Ticket {
            $lockedStaff = User::query()->whereKey($staff->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureStaffCanQueue($lockedStaff);

            $department = Department::query()
                ->whereKey($lockedStaff->department_id)
                ->lockForUpdate()
                ->first();

            if ($department === null || ! $department->is_active) {
                throw new AuthorizationException('Your assigned office is inactive.');
            }

            $queueDate = Carbon::now(config('app.timezone'))->toDateString();

            $ticket = Ticket::query()
                ->where('department_id', $department->id)
                ->where('queue_date', $queueDate)
                ->where('status', TicketStatus::Waiting->value)
                ->orderBy('number')
                ->lockForUpdate()
                ->first();

            if ($ticket === null) {
                return null;
            }

            $this->transition($ticket, TicketStatus::Serving);
            $ticket->called_at = now();
            $ticket->handled_by = $lockedStaff->id;
            $ticket->save();
            $this->activityLogger->record($lockedStaff, 'ticket.called', 'ticket', $ticket->id, $department->id);

            return $ticket->fresh(['department', 'service', 'student']);
        }, attempts: 3);
    }

    public function cancelTicket(User $student, int $ticketId): Ticket
    {
        return DB::transaction(function () use ($student, $ticketId): Ticket {
            $lockedStudent = User::query()->whereKey($student->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureStudentCanQueue($lockedStudent);

            $ticket = Ticket::query()->whereKey($ticketId)->lockForUpdate()->firstOrFail();

            if ((int) $ticket->student_id !== (int) $lockedStudent->id) {
                throw new AuthorizationException('You can only cancel your own ticket.');
            }

            $this->transition($ticket, TicketStatus::Cancelled);
            $ticket->cancelled_at = now();
            $ticket->save();
            $this->activityLogger->record($lockedStudent, 'ticket.cancelled', 'ticket', $ticket->id, $ticket->department_id);

            return $ticket;
        }, attempts: 3);
    }

    public function completeTicket(User $staff, int $ticketId): Ticket
    {
        return $this->transitionStaffTicket($staff, $ticketId, TicketStatus::Completed);
    }

    public function skipTicket(User $staff, int $ticketId): Ticket
    {
        return $this->transitionStaffTicket($staff, $ticketId, TicketStatus::Skipped);
    }

    public function recallTicket(User $staff, int $ticketId): Ticket
    {
        return DB::transaction(function () use ($staff, $ticketId): Ticket {
            $lockedStaff = User::query()->whereKey($staff->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureStaffCanQueue($lockedStaff);

            $ticket = Ticket::query()->whereKey($ticketId)->lockForUpdate()->firstOrFail();
            $this->ensureTicketBelongsToStaff($ticket, $lockedStaff);

            if ($ticket->queue_date->toDateString() !== Carbon::now(config('app.timezone'))->toDateString()) {
                throw ValidationException::withMessages([
                    'ticket' => 'Only a skipped ticket from today can be recalled.',
                ]);
            }

            $this->transition($ticket, TicketStatus::Serving);
            $ticket->called_at = now();
            $ticket->completed_at = null;
            $ticket->handled_by = $lockedStaff->id;
            $ticket->save();
            $this->activityLogger->record($lockedStaff, 'ticket.recalled', 'ticket', $ticket->id, $ticket->department_id);

            return $ticket->fresh(['department', 'service', 'student']);
        }, attempts: 3);
    }

    public function peopleAhead(Ticket $ticket): int
    {
        return Ticket::query()
            ->where('department_id', $ticket->department_id)
            ->where('queue_date', $ticket->queue_date->toDateString())
            ->where('status', TicketStatus::Waiting->value)
            ->where('number', '<', $ticket->number)
            ->count();
    }

    private function transitionStaffTicket(User $staff, int $ticketId, TicketStatus $nextStatus): Ticket
    {
        return DB::transaction(function () use ($staff, $ticketId, $nextStatus): Ticket {
            $lockedStaff = User::query()->whereKey($staff->getKey())->lockForUpdate()->firstOrFail();
            $this->ensureStaffCanQueue($lockedStaff);

            $ticket = Ticket::query()->whereKey($ticketId)->lockForUpdate()->firstOrFail();
            $this->ensureTicketBelongsToStaff($ticket, $lockedStaff);
            $this->transition($ticket, $nextStatus);

            $ticket->completed_at = now();
            $ticket->handled_by = $lockedStaff->id;
            $ticket->save();
            $action = $nextStatus === TicketStatus::Completed ? 'ticket.completed' : 'ticket.skipped';
            $this->activityLogger->record($lockedStaff, $action, 'ticket', $ticket->id, $ticket->department_id);

            return $ticket->fresh(['department', 'service', 'student']);
        }, attempts: 3);
    }

    private function ensureStudentCanQueue(User $student): void
    {
        if (! $student->is_active || $student->role !== 'student') {
            throw new AuthorizationException('An active student account is required.');
        }
    }

    private function ensureStaffCanQueue(User $staff): void
    {
        if (! $staff->is_active || $staff->role !== 'staff' || $staff->department_id === null) {
            throw new AuthorizationException('An active staff account assigned to an office is required.');
        }
    }

    private function ensureTicketBelongsToStaff(Ticket $ticket, User $staff): void
    {
        if ((int) $ticket->department_id !== (int) $staff->department_id) {
            throw new AuthorizationException('You can only manage tickets in your assigned office.');
        }
    }

    private function transition(Ticket $ticket, TicketStatus $nextStatus): void
    {
        $currentStatus = TicketStatus::tryFrom($ticket->status);

        if ($currentStatus === null || ! $currentStatus->canTransitionTo($nextStatus)) {
            throw ValidationException::withMessages([
                'ticket' => "A ticket cannot move from {$ticket->status} to {$nextStatus->value}.",
            ]);
        }

        $ticket->status = $nextStatus->value;
    }
}
