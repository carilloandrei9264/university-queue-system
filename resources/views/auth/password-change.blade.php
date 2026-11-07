<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change temporary password | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="auth-shell">
      <section class="auth-card">
        <p class="auth-brand">NU Queue-It</p>
        <h1>Change your temporary password</h1>
        <p class="hint">For your account's security, set a new password before continuing. Use at least 12 characters.</p>
        @if ($errors->any())
            <p class="error validation-summary" role="alert" tabindex="-1" data-validation-summary>{{ $errors->first() }}</p>
        @endif
        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf
            <label for="current-password">Temporary password</label>
            <input id="current-password" name="current_password" type="password" autocomplete="current-password" required>
            <label for="new-password">New password</label>
            <input id="new-password" name="password" type="password" minlength="12" autocomplete="new-password" required>
            <label for="new-password-confirmation">Confirm new password</label>
            <input id="new-password-confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
            <button type="submit">Change password</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="secondary" type="submit">Sign out</button>
        </form>
      </section>
    </main>
    <script src="{{ asset('js/ui-states.js') }}" defer></script>
</body>
</html>
