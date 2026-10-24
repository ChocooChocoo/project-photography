{{-- Reschedule panel for a booking. Loaded into the booking modal by partials/booking-reschedule-script. --}}
@php
    $open = $requests->first(fn ($request) => $request->isOpen());
    $provider = $booking->booking_type === 'studio' ? 'studio' : 'photographer';
    $slotOf = fn ($request) => ['date' => $request->requested_date->toDateString(), 'start_time' => $request->start_time, 'end_time' => $request->end_time];
    $canRequest = in_array($booking->status, ['pending', 'confirmed'], true);
    $statusText = [
        'approved' => 'Approved',
        'rejected' => 'Declined',
        'accepted' => 'Moved to an offered date',
    ];
@endphp
<h5>Reschedule</h5>
<p class="mb-2">Current schedule: <strong>{{ $label(['date' => $booking->event_date->toDateString(), 'start_time' => $booking->start_time, 'end_time' => $booking->end_time]) }}</strong></p>

@if ($open)
    <article class="border rounded p-3 mb-3" style="overflow-wrap:anywhere">
        <p class="mb-1">{{ $role === 'client' ? 'You asked' : 'The client asked' }} to move this booking to <strong>{{ $label($slotOf($open)) }}</strong>.</p>
        @if ($open->reason)
            <p class="mb-2">Reason: {{ $open->reason }}</p>
        @endif

        @if ($open->status === 'pending' && $role === 'client')
            <p class="mb-0">Waiting for the {{ $provider }} to answer.</p>
        @elseif ($open->status === 'pending')
            <form data-reschedule-action="{{ route($portal.'.booking.reschedule.approve', $open->id) }}" class="d-inline-block me-2 mb-2">
                <button class="btn btn-primary" style="min-height:44px">Approve new time</button>
            </form>
            <form data-reschedule-action="{{ route($portal.'.booking.reschedule.reject', $open->id) }}" class="mb-3">
                <label class="form-label" for="rs-reject-note-{{ $open->id }}">Note to the client (optional)</label>
                <textarea id="rs-reject-note-{{ $open->id }}" name="response_note" class="form-control mb-2" maxlength="1000"></textarea>
                <button class="btn btn-outline-danger" style="min-height:44px">Decline request</button>
            </form>
            <form data-reschedule-action="{{ route($portal.'.booking.reschedule.offer', $open->id) }}">
                <fieldset>
                    <legend class="fs-6">Or offer other dates (up to 3)</legend>
                    @for ($i = 0; $i < 3; $i++)
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-md-4">
                                <label class="form-label small" for="rs-alt-date-{{ $i }}">Option {{ $i + 1 }} date</label>
                                <input type="date" id="rs-alt-date-{{ $i }}" name="alternatives[{{ $i }}][date]" class="form-control" @if ($i === 0) required @endif>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small" for="rs-alt-start-{{ $i }}">Start</label>
                                <input type="time" id="rs-alt-start-{{ $i }}" name="alternatives[{{ $i }}][start_time]" class="form-control" @if ($i === 0) required @endif>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label small" for="rs-alt-end-{{ $i }}">End</label>
                                <input type="time" id="rs-alt-end-{{ $i }}" name="alternatives[{{ $i }}][end_time]" class="form-control" @if ($i === 0) required @endif>
                            </div>
                        </div>
                    @endfor
                    <label class="form-label" for="rs-offer-note-{{ $open->id }}">Note to the client (optional)</label>
                    <textarea id="rs-offer-note-{{ $open->id }}" name="response_note" class="form-control mb-2" maxlength="1000"></textarea>
                    <button class="btn btn-outline-primary" style="min-height:44px">Send other dates</button>
                </fieldset>
            </form>
        @elseif ($open->status === 'alternatives_offered')
            <p class="mb-2">The {{ $provider }} cannot do that time and offered other dates.</p>
            @if ($open->response_note)
                <p class="mb-2">Note: {{ $open->response_note }}</p>
            @endif
            @if ($role === 'client')
                @foreach ($open->alternatives as $index => $alternative)
                    <form data-reschedule-action="{{ route('client.booking.reschedule.accept', $open->id) }}" class="d-inline-block me-2 mb-2">
                        <input type="hidden" name="alternative" value="{{ $index }}">
                        <button class="btn btn-outline-primary" style="min-height:44px">Choose {{ $label($alternative) }}</button>
                    </form>
                @endforeach
            @else
                <ul class="mb-0">
                    @foreach ($open->alternatives as $alternative)
                        <li>{{ $label($alternative) }}</li>
                    @endforeach
                </ul>
                <p class="mb-0 mt-2">Waiting for the client to choose.</p>
            @endif
        @endif
    </article>
@elseif ($role === 'client' && $canRequest)
    <form data-reschedule-action="{{ route('client.booking.reschedule.store', $booking->id) }}" class="mb-3">
        <fieldset>
            <legend class="fs-6">Ask for a new date or time</legend>
            <div class="row g-2 mb-2">
                <div class="col-12 col-md-4">
                    <label class="form-label small" for="rs-date-{{ $booking->id }}">New date</label>
                    <input type="date" id="rs-date-{{ $booking->id }}" name="requested_date" class="form-control" min="{{ now()->addDay()->toDateString() }}" required>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label small" for="rs-start-{{ $booking->id }}">Start</label>
                    <input type="time" id="rs-start-{{ $booking->id }}" name="start_time" class="form-control" required>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label small" for="rs-end-{{ $booking->id }}">End</label>
                    <input type="time" id="rs-end-{{ $booking->id }}" name="end_time" class="form-control" required>
                </div>
            </div>
            <label class="form-label" for="rs-reason-{{ $booking->id }}">Reason (optional)</label>
            <textarea id="rs-reason-{{ $booking->id }}" name="reason" class="form-control mb-2" maxlength="1000"></textarea>
            <button class="btn btn-primary" style="min-height:44px">Send reschedule request</button>
            <p class="form-text mb-0">The {{ $provider }} checks the new time and approves it, declines it, or offers other dates. You will get a notification.</p>
        </fieldset>
    </form>
@elseif ($role === 'client')
    <p>This booking can no longer be rescheduled.</p>
@else
    <p class="mb-2">No reschedule request is waiting.</p>
@endif

@php $answered = $requests->reject(fn ($request) => $request->isOpen()); @endphp
@if ($answered->isNotEmpty())
    <h6 class="mt-3">Earlier requests</h6>
    <ul class="ps-3 mb-0">
        @foreach ($answered as $past)
            <li>{{ $label($slotOf($past)) }}: {{ $statusText[$past->status] ?? ucfirst($past->status) }}@if ($past->response_note) ({{ $past->response_note }})@endif</li>
        @endforeach
    </ul>
@endif
