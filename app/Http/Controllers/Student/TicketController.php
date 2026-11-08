<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Ticket;
use App\Services\TicketQueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request, TicketQueueService $queue): View
    {
        return view('student.dashboard', $this->dashboardData($request, $queue));
    }

    public function refresh(Request $request, TicketQueueService $queue): View
    {
        return view('student._queue-status', $this->dashboardData($request, $queue));
    }

    private function dashboardData(Request $request, TicketQueueService $queue): array
    {
        $activeTicket = Ticket::query()
            ->where('student_id', $request->user()->id)
            ->whereIn('status', ['waiting', 'serving'])
            ->with(['department', 'service', 'serviceWindow'])
            ->first();

        return [
            'departments' => Department::query()
                ->where('is_active', true)
                ->whereHas('services', fn ($query) => $query->where('is_active', true))
                ->with(['services' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
                ->orderBy('name')
                ->get(),
            'activeTicket' => $activeTicket,
            'peopleAhead' => $activeTicket === null ? null : $queue->peopleAhead($activeTicket),
        ];
    }

    public function store(Request $request, TicketQueueService $queue): RedirectResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
        ]);

        try {
            $ticket = $queue->createTicket($request->user(), (int) $validated['service_id']);
        } catch (ValidationException $exception) {
            if (isset($exception->errors()['ticket'])) {
                return redirect()->route('student.dashboard')
                    ->with('status', $exception->errors()['ticket'][0]);
            }

            throw $exception;
        }

        return redirect()->route('student.dashboard')
            ->with('status', "Your ticket {$ticket->code} is ready.");
    }

    public function destroy(Request $request, Ticket $ticket, TicketQueueService $queue): RedirectResponse
    {
        $queue->cancelTicket($request->user(), $ticket->id);

        return redirect()->route('student.dashboard')
            ->with('status', "Ticket {$ticket->code} was cancelled.");
    }
}
