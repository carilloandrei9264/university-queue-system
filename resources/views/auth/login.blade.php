<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f4f7fb; }
        body { align-items: center; display: flex; justify-content: center; margin: 0; min-height: 100vh; padding: 1rem; }
        main { background: white; border-radius: 1rem; box-shadow: 0 1rem 3rem rgb(0 53 148 / 10%); max-width: 26rem; padding: 2rem; width: 100%; }
        h1 { color: #003594; margin-top: 0; }
        label { display: block; font-weight: 600; margin: 1rem 0 0.35rem; }
        input { border: 1px solid #667085; border-radius: 0.4rem; box-sizing: border-box; font: inherit; padding: 0.7rem; width: 100%; }
        input:focus, button:focus-visible { outline: 3px solid #ffb81c; outline-offset: 2px; }
        button { background: #003594; border: 0; border-radius: 0.4rem; color: white; cursor: pointer; font: inherit; font-weight: 700; margin-top: 1.25rem; padding: 0.75rem; width: 100%; }
        .error { color: #9b1c1c; }
        .hint { color: #475467; font-size: 0.9rem; line-height: 1.5; }
    </style>
</head>
<body>
    <main>
        <p>NU Queue-It</p>
        <h1>Sign in</h1>
        <p class="hint">
            @if ($demoLogin)
                Demo accounts are enabled for this local environment. Accounts created by an administrator can also sign in.
            @else
                Sign in with the temporary password provided by your administrator. Change it after your first sign-in.
            @endif
            University sign-in can be connected separately when the institution's identity provider is confirmed.
        </p>

        @if ($errors->any())
            <p class="error" role="alert">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            <button type="submit">Sign in</button>
        </form>
    </main>
</body>
</html>
