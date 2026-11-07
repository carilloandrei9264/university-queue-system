<section aria-labelledby="queue-heading">
    <h2 id="queue-heading">Today's queue</h2>
    @forelse ($tickets as $ticket)
        <article class="ticket">
            <p class="ticket-code">{{ $ticket->code }}</p>
            <p>{{ $ticket->service->name }} · {{ ucfirst($ticket->status) }}</p>
            @if (in_array($ticket->status, ['serving', 'skipped'], true))
                <div class="actions">
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
        <p>No tickets in today's queue yet.</p>
    @endforelse
</section>
