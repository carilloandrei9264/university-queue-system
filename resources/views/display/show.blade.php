<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="refresh" content="5">
    <title>{{ $department->name }} queue | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="display-page">
    <header class="app-header">
        <div class="header-inner display-header-inner">
            <a class="brand" href="{{ route('display.index') }}"><img class="brand-icon" src="{{ asset('images/nu-bulldog-icon.webp') }}" alt=""> NU QUEUE-IT</a>
            <h1 class="display-title">{{ $department->name }} queue</h1>
            <a class="header-meta" href="{{ route('display.index') }}">All offices</a>
        </div>
    </header>
    <main class="display-shell display-multiwindow-shell">
        <section class="display-windows" aria-label="Service windows">
            @forelse ($windows as $window)
                <article class="display-window-card {{ $window->is_on_break ? 'is-on-break' : '' }}" aria-live="polite">
                    <h2>{{ $window->name }}</h2>
                    @if ($window->is_on_break)
                        <p class="display-window-state">On break</p>
                        <p class="display-window-help">This window will return shortly.</p>
                    @elseif ($window->servingTicket)
                        <p class="display-ticket">{{ $window->servingTicket->code }}</p>
                        <p class="display-window-help">Please proceed to this window.</p>
                    @else
                        <p class="display-window-state">Ready</p>
                        <p class="display-window-help">Waiting for the next ticket.</p>
                    @endif
                </article>
            @empty
                <div class="display-window-card">
                    <h2>No service windows</h2>
                    <p class="display-window-help">Please contact the office administrator.</p>
                </div>
            @endforelse
        </section>
        <aside class="display-next-panel" aria-labelledby="next-heading">
            <p class="display-next-kicker">Queue</p>
            <h2 id="next-heading">Up next</h2>
            @if ($nextTicket)
                <p class="display-next-ticket">{{ $nextTicket->code }}</p>
                <p class="display-next-help">Please wait for a window to call your number.</p>
            @else
                <p class="display-next-empty">No tickets waiting.</p>
            @endif
        </aside>
    </main>
    <footer class="display-footer">Updates automatically every 5 seconds · Follow the window shown beside your ticket number</footer>
    <script src="{{ asset('js/ui-states.js') }}" defer></script>
</body>
</html>
