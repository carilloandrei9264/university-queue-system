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
            <a class="brand" href="{{ route('display.index') }}">NU QUEUE-IT</a>
            <h1 class="display-title">{{ $department->name }} queue</h1>
            <a class="header-meta" href="{{ route('display.index') }}">All offices</a>
        </div>
    </header>
    <main class="display-shell">
        <section class="display-tile" aria-labelledby="serving-heading" aria-live="polite">
            <h2 id="serving-heading">Now serving</h2>
            @if ($servingTicket)
                <p class="display-ticket">{{ $servingTicket->code }}</p>
                <p class="display-help">Please proceed to the service counter.</p>
            @else
                <div class="empty-state"><p>No ticket is being served.</p></div>
            @endif
        </section>
        <section class="display-tile display-tile-next" aria-labelledby="next-heading">
            <h2 id="next-heading">Up next</h2>
            @if ($nextTicket)
                <p class="display-ticket">{{ $nextTicket->code }}</p>
            @else
                <div class="empty-state"><p>No tickets waiting.</p></div>
            @endif
        </section>
    </main>
    <footer class="display-footer">Updates automatically every 5 seconds · Please wait for your number to be called</footer>
</body>
</html>
