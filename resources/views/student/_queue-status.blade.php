@if ($activeTicket)
    <section class="panel" aria-labelledby="ticket-heading" aria-live="polite">
        <div class="ticket-layout">
            <div class="ticket-hero">
                <p class="ticket-hero-label" id="ticket-heading">Your ticket</p>
                <p class="ticket-code">{{ $activeTicket->code }}</p>
                <p class="ticket-meta">{{ $activeTicket->department->name }} · {{ $activeTicket->service->name }}</p>
            </div>
            <div class="ticket-info">
                <div class="panel-heading">
                    <h2>Queue status</h2>
                    <span class="status-badge {{ $activeTicket->status }}">{{ $activeTicket->status === 'serving' ? 'Now serving' : ucfirst($activeTicket->status) }}</span>
                </div>
                @if ($activeTicket->status === 'waiting')
                    <div class="metric">
                        <span>People ahead</span>
                        <strong>{{ $peopleAhead }}</strong>
                    </div>
                    <p class="muted">Stay nearby. Your ticket status updates automatically.</p>
                @else
                    <p class="notice">It’s your turn. Please proceed to the office counter.</p>
                @endif
                @if ($activeTicket->status === 'waiting')
                    <form class="ticket-actions" method="POST" action="{{ route('student.tickets.destroy', $activeTicket) }}">
                        @csrf
                        @method('DELETE')
                        <button class="danger" type="submit">Cancel ticket</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@else
    <section class="panel" aria-labelledby="take-ticket-heading">
        <div class="panel-heading">
            <h2 id="take-ticket-heading">Take a ticket</h2>
        </div>
        <p class="muted">Choose the service you need. You can have one active ticket at a time.</p>
        @if ($departments->isEmpty())
            <div class="empty-state">
                <h2>No services available</h2>
                <p>No offices are currently accepting tickets. Please check again later.</p>
            </div>
        @else
            <form class="service-form" method="POST" action="{{ route('student.tickets.store') }}">
                @csrf
                <label for="service-id">Office service</label>
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
                <button class="gold" type="submit">Get my ticket</button>
            </form>
        @endif
    </section>
@endif
