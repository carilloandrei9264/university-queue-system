@if ($activeTicket)
    <section class="panel student-ticket-panel" aria-labelledby="ticket-heading" aria-live="polite">
        <div class="ticket-layout">
            <div class="ticket-hero student-ticket-hero">
                <span class="student-ticket-overline" id="ticket-heading">Your queue ticket</span>
                <p class="ticket-code" aria-label="Ticket number {{ $activeTicket->code }}">{{ $activeTicket->code }}</p>
                <p class="ticket-meta">{{ $activeTicket->department->name }}</p>
                <p class="student-ticket-service">{{ $activeTicket->service->name }}</p>
                <p class="student-ticket-date">Issued {{ $activeTicket->created_at->format('g:i A') }}</p>
            </div>
            <div class="ticket-info student-ticket-info">
                <div class="student-ticket-heading">
                    <div>
                        <p class="student-info-kicker">Current status</p>
                        <h2>{{ $activeTicket->status === 'serving' ? 'It’s your turn' : 'You’re in the queue' }}</h2>
                    </div>
                    <span class="status-badge {{ $activeTicket->status }}">{{ $activeTicket->status === 'serving' ? 'Now serving' : 'Waiting' }}</span>
                </div>
                @if ($activeTicket->status === 'waiting')
                    <ol class="student-queue-steps" aria-label="Queue progress">
                        <li class="is-current" aria-current="step"><span>1</span><strong>Waiting</strong></li>
                        <li><span>2</span><strong>Called to counter</strong></li>
                    </ol>
                    <div class="metric">
                        <span>People ahead</span>
                        <strong class="student-people-ahead">{{ $peopleAhead }}</strong>
                    </div>
                    <p class="student-queue-hint">Stay nearby. We’ll update your ticket here when the office calls you.</p>
                @else
                    <ol class="student-queue-steps" aria-label="Queue progress">
                        <li class="is-complete"><span aria-hidden="true">✓</span><strong>Waiting</strong></li>
                        <li class="is-current" aria-current="step"><span>2</span><strong>Called to counter</strong></li>
                    </ol>
                    <p class="notice student-turn-notice"><strong>Please proceed to the {{ $activeTicket->department->name }} counter.</strong><span>Have your ticket number ready.</span></p>
                @endif
                @if ($activeTicket->status === 'waiting')
                    <form class="ticket-actions" method="POST" action="{{ route('student.tickets.destroy', $activeTicket) }}">
                        @csrf
                        @method('DELETE')
                        <button class="danger" type="submit">Cancel this ticket</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@else
    <section class="panel student-ticket-panel student-booking-panel" aria-labelledby="take-ticket-heading">
        <div class="student-booking-intro">
            <span class="student-booking-icon" aria-hidden="true">01</span>
            <div>
                <p class="student-info-kicker">Start here</p>
                <h2 id="take-ticket-heading">What do you need help with?</h2>
                <p class="muted">Choose an office service to join its queue. You can have one active ticket at a time.</p>
            </div>
        </div>
        @if ($departments->isEmpty())
            <div class="empty-state student-empty-state">
                <h2>No services available</h2>
                <p>No offices are currently accepting tickets. Please check again later.</p>
            </div>
        @else
            <form class="service-form student-service-form" method="POST" action="{{ route('student.tickets.store') }}">
                @csrf
                <label for="service-id">Select an office service</label>
                <select id="service-id" name="service_id" required>
                    <option value="">Select a service</option>
                    @foreach ($departments as $department)
                        <optgroup label="{{ $department->name }}">
                            @foreach ($department->services as $service)
                                <option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>{{ $service->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p class="student-form-hint">Your ticket number will appear here after you join.</p>
                <button class="gold" type="submit">Join the queue <span aria-hidden="true">→</span></button>
            </form>
        @endif
    </section>
@endif
