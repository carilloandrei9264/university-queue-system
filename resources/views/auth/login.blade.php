<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="auth-shell">
        <div class="auth-layout">
            <section class="auth-intro">
                <p class="auth-brand">NU Queue-It</p>
                <div>
                    <p class="auth-eyebrow">University service queues</p>
                    <h2>A clearer way to move through campus services.</h2>
                    <p>Take a queue ticket, keep track of your place, and know when it is your turn.</p>
                </div>
                <div class="auth-visual" aria-hidden="true">
                    <span class="auth-visual-line"></span>
                    <span class="auth-visual-dot"></span>
                    <span class="auth-visual-card">
                        <span>YOUR CAMPUS</span>
                        <strong>One step at a time.</strong>
                    </span>
                </div>
            </section>
            <section class="auth-card">
                <p class="auth-eyebrow">Student and staff access</p>
                <h1>Welcome back</h1>
                <p class="auth-subtitle">Sign in to continue to your queue.</p>
                <p class="hint">
                    @if ($demoLogin)
                        Demo accounts are enabled for this local environment. Accounts created by an administrator can also sign in.
                    @else
                        Sign in with the temporary password provided by your administrator. Change it after your first sign-in.
                    @endif
                    University sign-in can be connected separately when the institution's identity provider is confirmed.
                </p>

                @if ($errors->any())
                    <p class="error validation-summary" role="alert" tabindex="-1" data-validation-summary>{{ $errors->first() }}</p>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <label for="email">University email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>

                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>

                    <button type="submit">Sign in</button>
                </form>
            </section>
        </div>
    </main>
    <script src="{{ asset('js/ui-states.js') }}" defer></script>
</body>
</html>
