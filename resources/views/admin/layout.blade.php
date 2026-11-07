<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f4f7fb; }
        body { margin: 0; }
        header { align-items: center; background: #003594; border-bottom: 0.3rem solid #ffb81c; color: white; display: flex; justify-content: space-between; padding: 1rem max(1rem, calc((100% - 64rem) / 2)); }
        header a { color: white; }
        main { margin: 2rem auto; max-width: 64rem; padding: 0 1rem; }
        section { background: white; border-radius: 0.8rem; box-shadow: 0 0.5rem 1.5rem rgb(0 53 148 / 8%); margin: 1rem 0; padding: 1.25rem; }
        nav { display: flex; flex-wrap: wrap; gap: 1rem; }
        label { display: block; font-weight: 600; margin: 0.65rem 0 0.25rem; }
        input, select { border: 1px solid #667085; border-radius: 0.35rem; box-sizing: border-box; font: inherit; max-width: 100%; padding: 0.55rem; }
        input[type="text"], input[type="email"], input[type="password"], select { width: 100%; }
        input[type="checkbox"] { width: auto; }
        button { background: #003594; border: 0; border-radius: 0.35rem; color: white; cursor: pointer; font: inherit; font-weight: 650; margin-top: 0.75rem; padding: 0.6rem 0.9rem; }
        :focus-visible { outline: 3px solid #ffb81c; outline-offset: 2px; }
        .grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr)); }
        .record { border-top: 1px solid #d0d5dd; margin-top: 1rem; padding-top: 1rem; }
        .status { background: #ecfdf3; border-radius: 0.4rem; color: #05603a; padding: 0.75rem; }
        .error { color: #9b1c1c; }
        .muted { color: #475467; }
        .inline { align-items: center; display: flex; gap: 0.5rem; }
        @media (min-width: 48rem) {
            input[type="text"], input[type="email"], input[type="password"], select { max-width: 32rem; }
        }
    </style>
</head>
<body>
    <header>
        <strong><a href="{{ route('admin.dashboard') }}">NU Queue-It · Administration</a></strong>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sign out</button>
        </form>
    </header>
    <main>
        <nav aria-label="Admin sections">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a href="{{ route('admin.departments.index') }}">Departments</a>
            <a href="{{ route('admin.services.index') }}">Services</a>
            <a href="{{ route('admin.staff.index') }}">Office staff</a>
            <a href="{{ route('admin.students.index') }}">Students</a>
        </nav>

        @if (session('status'))
            <p class="status" role="status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <section class="error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <h1>@yield('title')</h1>
        @yield('content')
    </main>
</body>
</html>
