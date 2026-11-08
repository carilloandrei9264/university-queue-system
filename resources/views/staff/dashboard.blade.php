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
        <div class="header-inner staff-header-inner">
            <a class="brand" href="{{ route('staff.dashboard') }}">NU QUEUE-IT</a>
            <span class="staff-header-label">{{ auth()->user()->department->name }} office</span>
            <details class="profile-menu staff-profile-menu">
                <summary class="profile-trigger">
                    <span class="profile-avatar" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->full_name, 0, 1)) }}</span>
                    <span class="profile-copy">
                        <strong>{{ auth()->user()->full_name }}</strong>
                        <span>Office staff · {{ auth()->user()->department->name }}</span>
                    </span>
                    <span class="profile-chevron" aria-hidden="true"></span>
                </summary>
                <div class="profile-dropdown">
                    <p>{{ auth()->user()->department->name }} office<br><strong>{{ auth()->user()->full_name }}</strong></p>
                    <form method="POST" action="{{ route('logout') }}" data-logout-form>
                        @csrf
                        <button class="profile-logout" type="submit" data-loading-text="Signing out…">Sign out</button>
                    </form>
                </div>
            </details>
        </div>
    </header>
    <main class="page-shell staff-shell">
        <section class="staff-welcome" aria-labelledby="staff-welcome-heading">
            <div class="staff-welcome-copy">
                <p class="staff-welcome-kicker">Office staff console</p>
                <h1 id="staff-welcome-heading">{{ auth()->user()->department->name }} queue</h1>
                <p>Call and manage today’s tickets for your office.</p>
            </div>
            <form class="call-next-form" method="POST" action="{{ route('staff.call-next') }}">
                @csrf
                <button class="gold" type="submit" data-loading-text="Calling next…">Call next ticket <span aria-hidden="true">→</span></button>
            </form>
        </section>

        <div class="staff-queue-heading">
            <div>
                <p class="page-eyebrow">Today</p>
                <h2>Office queue</h2>
            </div>
            <span class="staff-live-label"><span aria-hidden="true"></span> Live updates</span>
        </div>

        @if (session('status'))
            <p class="status success-state staff-feedback" data-state="success" role="status">{{ session('status') }}</p>
        @endif

        <p class="staff-refresh state-message" data-refresh-state data-state="ready" role="status" aria-live="polite">
            <span class="staff-refresh-indicator" aria-hidden="true"></span>
            <span data-refresh-message>Queue refreshes automatically every 5 seconds.</span>
        </p>
        <div data-queue-refresh data-refresh-url="{{ route('staff.queue-status') }}" data-sign-in-url="{{ route('login') }}">
            @include('staff._queue')
        </div>
    </main>
    <script src="{{ asset('js/queue-refresh.js') }}" defer></script>
    <script src="{{ asset('js/ui-states.js') }}" defer></script>
</body>
</html>
