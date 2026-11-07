<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="app-header">
        <div class="header-inner">
            <a class="brand" href="{{ route('admin.dashboard') }}">NU QUEUE-IT</a>
            <span class="header-meta">Administration</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="header-signout" type="submit">Sign out</button>
            </form>
        </div>
    </header>
    <main class="page-shell">
        <div class="page-heading">
            <p class="page-eyebrow">{{ $title }}</p>
            <h1>Welcome, {{ auth()->user()->full_name }}</h1>
            <p>Manage your office setup, account access, and queue records.</p>
        </div>
        @isset($adminLinks)
            <nav class="admin-dashboard-grid" aria-label="Administration">
                @foreach ($adminLinks as $label => $link)
                    <a class="admin-link-card" href="{{ $link['url'] }}">
                        <strong>{{ $label }}</strong>
                        <span>{{ $link['description'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endisset
    </main>
    <script src="{{ asset('js/ui-states.js') }}" defer></script>
</body>
</html>
