<section class="panel" aria-labelledby="queue-heading">
    <div class="panel-heading">
        <h2 id="queue-heading">Today’s queue</h2>
        <span class="status-badge">{{ $tickets->where('status', 'waiting')->count() }} waiting</span>
    </div>
    @forelse ($tickets as $ticket)
        <article class="queue-item {{ $ticket->status === 'serving' ? 'is-serving' : '' }}">
            <div class="queue-item-main">
                <p class="ticket-code">{{ $ticket->code }}</p>
                <span class="queue-service">{{ $ticket->service->name }}</span>
                <span class="status-badge {{ $ticket->status }}">{{ $ticket->status === 'serving' ? 'Now serving' : ucfirst($ticket->status) }}</span>
            </div>
            @if (in_array($ticket->status, ['serving', 'skipped'], true))
                <div class="queue-actions">
                    @if ($ticket->status === 'serving')
                        <form method="POST" action="{{ route('staff.tickets.complete', $ticket) }}">
                            @csrf
                            <button type="submit">Complete</button>
                        </form>
                        <form method="POST" action="{{ route('staff.tickets.skip', $ticket) }}">
                            @csrf
                            <button class="secondary" type="submit">Skip</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('staff.tickets.recall', $ticket) }}">
                            @csrf
                            <button type="submit">Recall</button>
                        </form>
                    @endif
                </div>
            @endif
        </article>
    @empty
        <div class="empty-state">
            <h2>Queue is clear</h2>
            <p>There are no tickets in today’s queue yet.</p>
        </div>
    @endforelse
</section>
