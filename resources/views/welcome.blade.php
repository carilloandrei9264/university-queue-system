<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="auth-shell">
      <section class="auth-card">
        <p class="auth-brand">NU Queue-It</p>
        <h1>Welcome to NU Queue-It</h1>
        <p class="hint">A simpler way to join and follow your university office queue.</p>
        <a class="button" href="{{ route('login') }}">Sign in</a>
        <a class="button secondary" href="{{ route('display.index') }}">View office displays</a>
      </section>
    </main>
</body>
</html>
