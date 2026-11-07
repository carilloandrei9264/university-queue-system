<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session expired | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card" role="alert">
            <p class="auth-brand">NU Queue-It</p>
            <p class="page-eyebrow">Session expired</p>
            <h1>Please sign in again</h1>
            <p class="hint">Your session ended or the form was open too long. Sign in again, then retry your action.</p>
            <a class="button" href="{{ route('login') }}">Go to sign in</a>
            <a class="button secondary" href="{{ route('display.index') }}">View public queue displays</a>
        </section>
    </main>
    <script src="{{ asset('js/ui-states.js') }}" defer></script>
</body>
</html>
