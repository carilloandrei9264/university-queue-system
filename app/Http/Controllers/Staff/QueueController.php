<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
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
        $queueDate = Carbon::now(config('app.timezone'))->toDateString();
        $departmentId = $request->user()->department_id;

        return view('staff.dashboard', [
            'tickets' => Ticket::query()
                ->where('department_id', $departmentId)
                ->where('queue_date', $queueDate)
                ->whereIn('status', ['waiting', 'serving', 'skipped'])
                ->with(['department', 'service'])
                ->orderBy('number')
                ->get(),
        ]);
    }

    public function callNext(Request $request, TicketQueueService $queue): RedirectResponse
    {
        $ticket = $queue->callNext($request->user());

        return redirect()->route('staff.dashboard')
            ->with('status', $ticket === null ? 'There are no waiting tickets.' : "Now serving {$ticket->code}.");
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
