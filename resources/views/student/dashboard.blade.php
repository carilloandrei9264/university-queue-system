<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student queue | {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #172033; background: #f4f7fb; }
        body { margin: 0; }
        header { align-items: center; background: #003594; border-bottom: 0.3rem solid #ffb81c; color: white; display: flex; justify-content: space-between; padding: 1rem max(1rem, calc((100% - 58rem) / 2)); }
        main { background: white; border-radius: 1rem; box-shadow: 0 1rem 3rem rgb(0 53 148 / 10%); margin: 2rem auto; max-width: 54rem; padding: 2rem; width: calc(100% - 6rem); }
        section { border-top: 1px solid #d0d5dd; margin-top: 1rem; padding-top: 1rem; }
        label { display: block; font-weight: 600; margin: 1rem 0 0.35rem; }
        select { border: 1px solid #667085; border-radius: 0.4rem; box-sizing: border-box; font: inherit; max-width: 100%; padding: 0.7rem; width: 100%; }
        button { background: #003594; border: 0; border-radius: 0.4rem; color: white; cursor: pointer; font: inherit; font-weight: 700; margin-top: 1rem; padding: 0.7rem 1rem; }
        button.danger { background: #9b1c1c; }
        :focus-visible { outline: 3px solid #ffb81c; outline-offset: 2px; }
        .status { background: #ecfdf3; border-radius: 0.4rem; color: #05603a; padding: 0.75rem; }
        .error { color: #9b1c1c; }
        .ticket-code { color: #003594; font-size: clamp(2.5rem, 12vw, 4.5rem); font-weight: 800; margin: 0; }
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
        <h1>Student dashboard</h1>
        <p>Welcome, {{ auth()->user()->full_name }}.</p>

        @if (session('status'))
            <p class="status" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <p class="error" role="alert">{{ $errors->first() }}</p>
        @endif

        @if ($activeTicket)
            <section aria-labelledby="ticket-heading">
                <h2 id="ticket-heading">Your active ticket</h2>
                <p class="ticket-code">{{ $activeTicket->code }}</p>
                <p>{{ $activeTicket->department->name }} · {{ $activeTicket->service->name }}</p>
                <p>Status: {{ ucfirst($activeTicket->status) }}</p>
                @if ($activeTicket->status === 'waiting')
                    <p>People ahead: {{ $peopleAhead }}</p>
                    <form method="POST" action="{{ route('student.tickets.destroy', $activeTicket) }}">
                        @csrf
                        @method('DELETE')
                        <button class="danger" type="submit">Cancel ticket</button>
                    </form>
                @endif
            </section>
        @else
            <section aria-labelledby="take-ticket-heading">
                <h2 id="take-ticket-heading">Take a queue ticket</h2>
                @if ($departments->isEmpty())
                    <p>No offices are currently accepting tickets.</p>
                @else
                    <form method="POST" action="{{ route('student.tickets.store') }}">
                        @csrf
                        <label for="service-id">Choose an office service</label>
                        <select id="service-id" name="service_id" required>
                            <option value="">Select a service</option>
                            @foreach ($departments as $department)
                                <optgroup label="{{ $department->name }}">
                                    @foreach ($department->services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <button type="submit">Get ticket</button>
                    </form>
                @endif
            </section>
        @endif
    </main>
</body>
</html>
