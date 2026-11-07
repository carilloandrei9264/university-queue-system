<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f4f7fb; }
        body { margin: 0; }
        header { align-items: center; background: #003594; border-bottom: 0.3rem solid #ffb81c; color: white; display: flex; justify-content: space-between; padding: 1rem max(1rem, calc((100% - 60rem) / 2)); }
        main { background: white; border-radius: 1rem; box-shadow: 0 1rem 3rem rgb(0 53 148 / 10%); margin: 2rem auto; max-width: 56rem; padding: 2rem; width: calc(100% - 6rem); }
        button { background: #003594; border: 0; border-radius: 0.4rem; color: white; cursor: pointer; font: inherit; font-weight: 700; padding: 0.6rem 1rem; }
        button:focus-visible { outline: 3px solid #ffb81c; outline-offset: 2px; }
    </style>
</head>
<body>
    <header>
        <strong>NU Queue-It</strong>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sign out</button>
        </form>
    </header>
    <main>
        <h1>{{ $title }}</h1>
        <p>Welcome, {{ auth()->user()->full_name }}.</p>
        @isset($department)
            <p>Your assigned office: {{ $department->name }}</p>
        @endisset
        @isset($adminLinks)
            <nav aria-label="Administration">
                @foreach ($adminLinks as $label => $url)
                    <p><a href="{{ $url }}">{{ $label }}</a></p>
                @endforeach
            </nav>
        @endisset
        <p>The role-based sign-in foundation is ready. Queue features will appear here as they are built.</p>
    </main>
</body>
</html>
