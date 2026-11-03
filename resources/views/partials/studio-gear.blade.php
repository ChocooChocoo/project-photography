{{-- Cameras and gear a studio lists, shown to clients before they book. --}}
@php($gear = collect($equipment ?? [])->where('status', '!=', 'retired')->values())
@if($gear->isNotEmpty())
    <div class="{{ $class ?? 'mb-4' }}">
        <h5 class="card-title text-primary mb-3">{{ $title ?? 'Cameras & gear' }}</h5>
        <div class="row g-2">
            @foreach($gear as $item)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-2 h-100">
                        <div class="fw-semibold text-break">{{ $item->name }}</div>
                        <div class="small text-muted text-break">
                            {{ collect([$item->type, $item->brand, $item->model])->filter()->implode(' · ') }}
                            @if($item->status === 'maintenance') · Under maintenance @endif
                        </div>
                        @if($item->specs)
                            <div class="small mt-1 text-break">{{ $item->specs }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
