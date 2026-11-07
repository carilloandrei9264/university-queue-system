<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff queue | {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f4f7fb; }
        body { margin: 0; }
        header { align-items: center; background: #003594; border-bottom: 0.3rem solid #ffb81c; color: white; display: flex; justify-content: space-between; padding: 1rem max(1rem, calc((100% - 62rem) / 2)); }
        main { margin: 2rem auto; max-width: 58rem; padding: 0 1rem; }
        section { background: white; border-radius: 0.8rem; box-shadow: 0 0.5rem 1.5rem rgb(0 53 148 / 8%); margin: 1rem 0; padding: 1.25rem; }
        button { background: #003594; border: 0; border-radius: 0.4rem; color: white; cursor: pointer; font: inherit; font-weight: 700; margin: 0.25rem; padding: 0.65rem 0.9rem; }
        button.secondary { background: #475467; }
        :focus-visible { outline: 3px solid #ffb81c; outline-offset: 2px; }
        .status { background: #ecfdf3; border-radius: 0.4rem; color: #05603a; padding: 0.75rem; }
        .ticket-code { color: #003594; font-size: 1.5rem; font-weight: 800; }
        .actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    </style>
</head>
<body>
    <header>
        <strong>NU Queue-It · Staff</strong>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sign out</button>
        </form>
    </header>
    <main>
        <h1>{{ auth()->user()->department->name }} queue</h1>
        @if (session('status'))
            <p class="status" role="status">{{ session('status') }}</p>
        @endif

        <section>
            <h2>Call next</h2>
            <form method="POST" action="{{ route('staff.call-next') }}">
                @csrf
                <button type="submit">Call next ticket</button>
            </form>
        </section>

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
    </main>
</body>
</html>
