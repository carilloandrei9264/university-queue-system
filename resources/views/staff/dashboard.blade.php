<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff queue | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="app-header">
        <div class="header-inner">
            <a class="brand" href="{{ route('staff.dashboard') }}">NU QUEUE-IT</a>
            <span class="header-meta">{{ auth()->user()->department->name }} · Staff console</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="secondary" type="submit">Sign out</button>
            </form>
        </div>
    </header>
    <main class="page-shell">
        <div class="staff-topline">
            <div class="page-heading">
                <h1>{{ auth()->user()->department->name }} queue</h1>
                <p>Call and manage today’s tickets for your office.</p>
            </div>
            <form class="call-next-form" method="POST" action="{{ route('staff.call-next') }}">
                @csrf
                <button class="gold" type="submit">Call next ticket</button>
            </form>
        </div>
        @if (session('status'))
            <p class="status" role="status">{{ session('status') }}</p>
        @endif

        <p class="refresh-note" data-refresh-state role="status">Queue refreshes automatically every 5 seconds.</p>
        <div data-queue-refresh data-refresh-url="{{ route('staff.queue-status') }}">
            @include('staff._queue')
        </div>
    </main>
    <script src="{{ asset('js/queue-refresh.js') }}" defer></script>
</body>
</html>
