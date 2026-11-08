<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student queue | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="app-header">
        <div class="header-inner student-header-inner">
            <a class="brand" href="{{ route('student.dashboard') }}">NU QUEUE-IT</a>
            <span class="student-header-label">Student portal</span>
            <details class="profile-menu student-profile-menu">
                <summary class="profile-trigger">
                    <span class="profile-avatar" aria-hidden="true">{{ strtoupper(mb_substr(auth()->user()->full_name, 0, 1)) }}</span>
                    <span class="profile-copy">
                        <strong>{{ auth()->user()->full_name }}</strong>
                        <span>Student account</span>
                    </span>
                    <span class="profile-chevron" aria-hidden="true"></span>
                </summary>
                <div class="profile-dropdown">
                    <p>Signed in as<br><strong>{{ auth()->user()->full_name }}</strong></p>
                    <form method="POST" action="{{ route('logout') }}" data-logout-form>
                        @csrf
                        <button class="profile-logout" type="submit" data-loading-text="Signing out…">Sign out</button>
                    </form>
                </div>
            </details>
        </div>
    </header>
    <main class="page-shell student-shell">
        <section class="student-welcome" aria-labelledby="student-welcome-heading">
            <div class="student-welcome-copy">
                <p class="student-welcome-kicker">NU Queue-It · Student portal</p>
                <h1 id="student-welcome-heading">Welcome, {{ auth()->user()->full_name }}</h1>
                <p>Take a ticket for a campus service and follow your place in line right here.</p>
            </div>
            <span class="student-welcome-mark" aria-hidden="true">NU</span>
        </section>

        <div class="student-section-heading">
            <div>
                <p class="page-eyebrow">Your visit</p>
                <h2>Your queue</h2>
            </div>
            <span class="student-live-label"><span aria-hidden="true"></span> Live updates</span>
        </div>

        @if (session('status'))
            <p class="status success-state student-feedback" data-state="success" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <p class="error student-feedback" role="alert">{{ $errors->first() }}</p>
        @endif

        <p class="student-refresh state-message" data-refresh-state data-state="ready" role="status" aria-live="polite">
            <span class="student-refresh-indicator" aria-hidden="true"></span>
            <span data-refresh-message>Queue status refreshes automatically every 5 seconds.</span>
        </p>
        <div data-queue-refresh data-refresh-url="{{ route('student.queue-status') }}" data-sign-in-url="{{ route('login') }}">
            @include('student._queue-status')
        </div>
    </main>
    <script src="{{ asset('js/queue-refresh.js') }}" defer></script>
    <script src="{{ asset('js/ui-states.js') }}" defer></script>
</body>
</html>
