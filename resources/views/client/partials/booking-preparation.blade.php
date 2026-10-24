<section class="booking-preparation mt-4" aria-labelledby="preparationTitle">
    <h5 id="preparationTitle">Booking preparation</h5>
    <p>Preferred camera brand: <strong>{{ $booking->preferred_camera_brand ?: 'No preference' }}</strong></p>
    <div class="row g-3 mx-0">
        <div class="col-md-6">
            <h6>Preparation steps</h6>
            <ul class="list-unstyled">
                @if($booking->requiresContract())
                    <li>Contract: {{ $booking->contractFullySigned() ? 'Signed by both parties' : 'Waiting for signatures' }}</li>
                @endif
                @if($booking->booking_type === 'studio')
                    <li>Photographers: {{ $booking->assignedPhotographers->where('status', '!=', 'cancelled')->isNotEmpty() ? 'Assigned' : 'Waiting for assignment' }}</li>
                    <li>Equipment: {{ $booking->equipment->isNotEmpty() ? 'Assigned' : 'Waiting for assignment' }}</li>
                    <li>Arrival proof: {{ $booking->assignedPhotographers->whereNotNull('on_site_proof_image')->isNotEmpty() ? 'Submitted' : 'Waiting for arrival' }}</li>
                @endif
            </ul>
            <h6>Assigned equipment</h6>
            <ul class="ps-3">
                @forelse($booking->equipment as $item)
                    <li class="text-break">{{ $item->equipment_name }} ({{ $item->equipment_type }})<br>
                        Brand: {{ $item->brand ?: 'Not specified' }}. {{ $item->confirmed ? 'Photographer confirmed' : 'Waiting for the photographer to confirm' }}
                        @if($item->brand_mismatch)
                            <br>Brand differs from your preference ({{ $booking->preferred_camera_brand }}).
                        @endif
                    </li>
                @empty
                    <li>No equipment assigned yet.</li>
                @endforelse
            </ul>
            <h6>Materials included</h6>
            @forelse($booking->packages as $package)
                @if(!empty($package->materials))
                    <p class="mb-1">{{ $package->package_name }}</p>
                    <ul class="ps-3">
                        @foreach($package->materials as $material)
                            <li class="text-break">{{ $material['quantity'] ?? 1 }} &times; {{ $material['name'] ?? '' }}</li>
                        @endforeach
                    </ul>
                @endif
            @empty
                <p>No package materials recorded.</p>
            @endforelse
            @if($booking->packages->every(fn ($package) => empty($package->materials)))
                <p>No materials listed for this booking.</p>
            @endif
        </div>
        <div class="col-md-6">
            <h6>Booking timeline</h6>
            <ol class="ps-3">
                @forelse($booking->events as $event)
                    <li class="mb-2 text-break"><strong>{{ $event->title }}</strong><br>
                        <time datetime="{{ $event->occurred_at->toIso8601String() }}">{{ $event->occurred_at->format('M d, Y g:i A') }}</time>
                        @if(!empty($event->meta['name']))<br>{{ $event->meta['name'] }}@endif
                        @if(!empty($event->meta['brand'])) ({{ $event->meta['brand'] }})@endif
                    </li>
                @empty
                    <li>No milestones recorded yet. Earlier milestones are not backfilled.</li>
                @endforelse
            </ol>
        </div>
    </div>
    <h6>Photographer arrival proof</h6>
    @forelse($booking->assignedPhotographers->whereNotNull('on_site_proof_image') as $assignment)
        <figure>
            <figcaption>{{ $assignment->photographer?->first_name }} {{ $assignment->photographer?->last_name }}: {{ $assignment->on_site_at?->format('M d, Y g:i A') }}</figcaption>
            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($assignment->on_site_proof_image) }}" target="_blank" rel="noopener" class="d-inline-block" style="min-width:44px;min-height:44px">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($assignment->on_site_proof_image) }}" alt="Arrival proof submitted by {{ $assignment->photographer?->first_name }}" class="img-fluid rounded" style="max-height:240px">
            </a>
            @if($assignment->latitude !== null && $assignment->longitude !== null)
                <p>Submitted coordinates: {{ $assignment->latitude }}, {{ $assignment->longitude }} (not independently verified).</p>
            @endif
        </figure>
    @empty
        <p>No arrival proof submitted yet.</p>
    @endforelse
</section>
