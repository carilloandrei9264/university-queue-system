<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="refresh" content="5">
    <title>{{ $department->name }} queue | {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f4f7fb; }
        body { margin: 0; min-height: 100vh; }
        header { background: #003594; border-bottom: 0.5rem solid #ffb81c; color: white; padding: 1.25rem 3vw; }
        header a { color: white; }
        main { display: grid; gap: 2rem; grid-template-columns: minmax(0, 1.6fr) minmax(16rem, 1fr); margin: clamp(2rem, 8vh, 5rem) auto; max-width: 90rem; padding: 0 3vw; }
        section { background: white; border-radius: 1rem; box-shadow: 0 1rem 3rem rgb(0 53 148 / 9%); min-height: 18rem; padding: clamp(1.5rem, 4vw, 3rem); }
        h1 { font-size: clamp(1.75rem, 4vw, 3rem); margin: 0; }
        h2 { color: #475467; font-size: clamp(1rem, 2vw, 1.5rem); margin: 0 0 2rem; text-transform: uppercase; }
        .ticket { color: #003594; font-size: clamp(4rem, 14vw, 10rem); font-weight: 800; letter-spacing: 0.04em; line-height: 1; margin: 0; overflow-wrap: anywhere; }
        .next-ticket { font-size: clamp(2.5rem, 8vw, 5rem); }
        .empty { color: #667085; font-size: clamp(1.5rem, 4vw, 2.5rem); margin: 0; }
        .refresh { color: #667085; font-size: 0.9rem; grid-column: 1 / -1; text-align: right; }
        :focus-visible { outline: 3px solid #ffb81c; outline-offset: 3px; }
        @media (max-width: 48rem) { main { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header>
        <a href="{{ route('display.index') }}">All offices</a>
        <h1>{{ $department->name }} queue</h1>
    </header>
    <main>
        <section aria-labelledby="serving-heading" aria-live="polite">
            <h2 id="serving-heading">Now serving</h2>
            @if ($servingTicket)
                <p class="ticket">{{ $servingTicket->code }}</p>
                <p>Please proceed to the service counter.</p>
            @else
                <p class="empty">No ticket is being served.</p>
            @endif
        </section>
        <section aria-labelledby="next-heading">
            <h2 id="next-heading">Up next</h2>
            @if ($nextTicket)
                <p class="ticket next-ticket">{{ $nextTicket->code }}</p>
            @else
                <p class="empty">No tickets waiting.</p>
            @endif
        </section>
        <p class="refresh">This display refreshes automatically every 5 seconds.</p>
    </main>
</body>
</html>
