<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Queue displays | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="display-page">
    <header class="app-header">
        <div class="header-inner">
            <a class="brand" href="{{ route('display.index') }}"><img class="brand-icon" src="{{ asset('images/nu-bulldog-icon.webp') }}" alt=""> NU QUEUE-IT</a>
            <span class="header-meta">Public queue displays</span>
        </div>
    </header>
    <main class="page-shell">
        <h1>Choose an office</h1>
        <p class="header-meta">Select the board for the office you are visiting.</p>
        @if ($departments->isEmpty())
            <div class="empty-state"><p>No office displays are currently available.</p></div>
        @else
            <div class="display-chooser">
                @foreach ($departments as $department)
                    <a class="department-card" href="{{ route('display.show', $department->prefix) }}">
                        <span class="prefix">{{ $department->prefix }}</span>
                        <strong>{{ $department->name }}</strong>
                        <span>Open queue display <span aria-hidden="true">→</span></span>
                    </a>
                @endforeach
            </div>
        @endif
    </main>
    <script src="{{ asset('js/ui-states.js') }}" defer></script>
</body>
</html>
