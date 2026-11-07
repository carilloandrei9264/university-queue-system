<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change temporary password | {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f4f7fb; }
        body { align-items: center; display: flex; justify-content: center; margin: 0; min-height: 100vh; padding: 1rem; }
        main { background: white; border-radius: 1rem; box-shadow: 0 1rem 3rem rgb(0 53 148 / 10%); max-width: 30rem; padding: 2rem; width: 100%; }
        h1 { color: #003594; }
        label { display: block; font-weight: 600; margin: 1rem 0 0.35rem; }
        input { border: 1px solid #667085; border-radius: 0.4rem; box-sizing: border-box; font: inherit; padding: 0.7rem; width: 100%; }
        button { background: #003594; border: 0; border-radius: 0.4rem; color: white; cursor: pointer; font: inherit; font-weight: 700; margin-top: 1.25rem; padding: 0.75rem; }
        :focus-visible { outline: 3px solid #ffb81c; outline-offset: 2px; }
        .error { color: #9b1c1c; }
    </style>
</head>
<body>
    <main>
        <h1>Change your temporary password</h1>
        <p>For your account's security, set a new password before continuing. Use at least 12 characters.</p>
        @if ($errors->any())
            <p class="error" role="alert">{{ $errors->first() }}</p>
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
            <button type="submit">Sign out</button>
        </form>
    </main>
</body>
</html>
