@if ($activeTicket)
    <section aria-labelledby="ticket-heading" aria-live="polite">
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
