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
        <div class="header-inner">
            <a class="brand" href="{{ route('student.dashboard') }}">NU QUEUE-IT</a>
            <span class="header-meta">Student portal</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="secondary" type="submit">Sign out</button>
            </form>
        </div>
    </header>
    <main class="page-shell">
        <div class="page-heading">
            <p class="page-eyebrow">Student dashboard</p>
            <h1>Your queue</h1>
            <p>Welcome, {{ auth()->user()->full_name }}. Take a ticket for an office service and track your place in line.</p>
        </div>

        @if (session('status'))
            <p class="status success-state" data-state="success" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <p class="error" role="alert">{{ $errors->first() }}</p>
        @endif

        <p class="refresh-note state-message" data-refresh-state data-state="ready" role="status" aria-live="polite">Queue status refreshes automatically every 5 seconds.</p>
        <div data-queue-refresh data-refresh-url="{{ route('student.queue-status') }}" data-sign-in-url="{{ route('login') }}">
            @include('student._queue-status')
        </div>
    </main>
    <script src="{{ asset('js/queue-refresh.js') }}" defer></script>
    <script src="{{ asset('js/ui-states.js') }}" defer></script>
</body>
</html>
