<section class="panel staff-queue-panel" aria-labelledby="queue-heading">
    <div class="staff-queue-panel-heading">
        <div>
            <p class="staff-queue-kicker">Ticket list</p>
            <h2 id="queue-heading">Today’s tickets</h2>
        </div>
        <div class="staff-queue-counts" aria-label="Queue summary">
            <span class="status-badge waiting">{{ $tickets->where('status', 'waiting')->count() }} waiting</span>
            <span class="status-badge serving">{{ $tickets->where('status', 'serving')->count() }} serving</span>
        </div>
    </div>
    @forelse ($tickets as $ticket)
        <article class="queue-item staff-queue-item {{ $ticket->status === 'serving' ? 'is-serving' : '' }} {{ $ticket->status === 'skipped' ? 'is-skipped' : '' }}">
            <div class="queue-item-main">
                <div class="staff-ticket-identity">
                    <p class="ticket-code">{{ $ticket->code }}</p>
                    <span class="queue-service">{{ $ticket->service->name }}</span>
                    <span class="queue-window">{{ $ticket->serviceWindow?->name ?? 'Window not assigned' }}</span>
                </div>
                <span class="status-badge {{ $ticket->status }}">{{ $ticket->status === 'serving' ? 'Now serving' : ucfirst($ticket->status) }}</span>
            </div>
            @if (in_array($ticket->status, ['serving', 'skipped'], true))
                <div class="queue-actions staff-queue-actions">
                    @if ((int) $ticket->handled_by === (int) auth()->id() && $ticket->status === 'serving')
                        <form method="POST" action="{{ route('staff.tickets.complete', $ticket) }}">
                            @csrf
                            <button type="submit" data-loading-text="Completing…">Complete</button>
                        </form>
                        <form method="POST" action="{{ route('staff.tickets.skip', $ticket) }}">
                            @csrf
                            <button class="secondary" type="submit" data-loading-text="Skipping…">Skip</button>
                        </form>
                    @elseif ((int) $ticket->handled_by === (int) auth()->id())
                        <form method="POST" action="{{ route('staff.tickets.recall', $ticket) }}">
                            @csrf
                            <button type="submit" data-loading-text="Recalling…">Recall</button>
                        </form>
                    @else
                        <span class="staff-ticket-owner">Handled at {{ $ticket->serviceWindow?->name ?? 'another window' }}</span>
                    @endif
                </div>
            @endif
        </article>
    @empty
        <div class="empty-state">
            <span class="staff-empty-icon" aria-hidden="true">✓</span>
            <h2>No active tickets</h2>
            <p>New tickets for your office will appear here automatically.</p>
        </div>
    @endforelse
</section>
