<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card" role="alert">
            <p class="auth-brand">NU Queue-It</p>
            <p class="page-eyebrow">Error 403</p>
            <h1>Access denied</h1>
            <p class="hint">You don't have permission to open this page. Sign in with an account that has access, or contact your administrator.</p>
            <a class="button" href="{{ route('login') }}">Go to sign in</a>
            <a class="button secondary" href="{{ route('display.index') }}">View public queue displays</a>
        </section>
    </main>
    <script src="{{ asset('js/ui-states.js') }}" defer></script>
</body>
</html>
