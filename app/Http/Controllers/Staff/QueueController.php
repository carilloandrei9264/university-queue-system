<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ServiceWindow;
use App\Models\Ticket;
use App\Services\TicketQueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function index(Request $request): View
    {
        return view('staff.dashboard', $this->queueData($request));
    }

    public function refresh(Request $request): View
    {
        return view('staff._queue', $this->queueData($request));
    }

    private function queueData(Request $request): array
    {
        $queueDate = Carbon::now(config('app.timezone'))->toDateString();
        $staff = $request->user();
        $window = ServiceWindow::query()
            ->where('staff_id', $staff->id)
            ->where('is_active', true)
            ->with(['servingTicket' => fn ($query) => $query->where('queue_date', $queueDate)])
            ->first();

        return [
            'window' => $window,
            'currentTicket' => $window?->servingTicket,
            'tickets' => Ticket::query()
                ->where('department_id', $staff->department_id)
                ->where('queue_date', $queueDate)
                ->whereIn('status', ['waiting', 'serving', 'skipped'])
                ->with(['department', 'service', 'serviceWindow'])
                ->orderBy('number')
                ->get(),
        ];
    }

    public function callNext(Request $request, TicketQueueService $queue): RedirectResponse
    {
        $ticket = $queue->callNext($request->user());

        return redirect()->route('staff.dashboard')
            ->with('status', $ticket === null ? 'There are no waiting tickets.' : "Now serving {$ticket->code} at {$ticket->serviceWindow->name}.");
    }

    public function startBreak(Request $request, TicketQueueService $queue): RedirectResponse
    {
        $window = $queue->setWindowBreak($request->user(), true);

        return redirect()->route('staff.dashboard')->with('status', "{$window->name} is now on break.");
    }

    public function endBreak(Request $request, TicketQueueService $queue): RedirectResponse
    {
        $window = $queue->setWindowBreak($request->user(), false);

        return redirect()->route('staff.dashboard')->with('status', "{$window->name} is back in service.");
    }

    public function complete(Request $request, Ticket $ticket, TicketQueueService $queue): RedirectResponse
    {
        $queue->completeTicket($request->user(), $ticket->id);

        return redirect()->route('staff.dashboard')->with('status', "Ticket {$ticket->code} completed.");
    }

    public function skip(Request $request, Ticket $ticket, TicketQueueService $queue): RedirectResponse
    {
        $queue->skipTicket($request->user(), $ticket->id);

        return redirect()->route('staff.dashboard')->with('status', "Ticket {$ticket->code} skipped.");
    }

    public function recall(Request $request, Ticket $ticket, TicketQueueService $queue): RedirectResponse
    {
        $queue->recallTicket($request->user(), $ticket->id);

        return redirect()->route('staff.dashboard')->with('status', "Ticket {$ticket->code} recalled.");
    }
}
