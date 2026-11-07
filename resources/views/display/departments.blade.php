<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Queue displays | {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f4f7fb; }
        body { margin: 0; }
        header { background: #003594; border-bottom: 0.35rem solid #ffb81c; color: white; padding: 1.25rem max(1.5rem, calc((100% - 72rem) / 2)); }
        main { margin: 3rem auto; max-width: 72rem; padding: 0 1.5rem; }
        ul { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); list-style: none; padding: 0; }
        a { background: white; border-radius: 0.75rem; box-shadow: 0 0.5rem 1.5rem rgb(0 53 148 / 8%); color: #003594; display: block; font-size: 1.25rem; font-weight: 700; padding: 1.5rem; text-decoration: none; }
        a:hover { box-shadow: 0 0.75rem 2rem rgb(0 53 148 / 14%); }
        :focus-visible { outline: 3px solid #ffb81c; outline-offset: 3px; }
    </style>
</head>
<body>
    <header><strong>NU Queue-It · Public queue displays</strong></header>
    <main>
        <h1>Choose an office</h1>
        @if ($departments->isEmpty())
            <p>No office displays are currently available.</p>
        @else
            <ul>
                @foreach ($departments as $department)
                    <li><a href="{{ route('display.show', $department->prefix) }}">{{ $department->name }}</a></li>
                @endforeach
            </ul>
        @endif
    </main>
</body>
</html>
