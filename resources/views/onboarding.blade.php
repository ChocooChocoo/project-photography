@extends('layouts.auth.app')
@section('title', 'Account tutorial')

@section('styles')
    <style>
        .auth-box { padding: 0; }
        .tutorial { overflow-wrap: anywhere; }
        .tutorial .card { padding: clamp(1rem, 3vw, 2rem); }
        .tutorial .btn { min-height: 44px; }
        .tutorial .btn-primary {
            --ins-btn-bg: color-mix(in srgb, var(--ins-primary) 90%, black);
            --ins-btn-border-color: var(--ins-btn-bg);
            --ins-btn-hover-bg: color-mix(in srgb, var(--ins-primary) 80%, black);
            --ins-btn-hover-border-color: var(--ins-btn-hover-bg);
        }
        .tutorial .btn-outline-secondary {
            color: var(--ins-body-color);
            border-color: currentColor;
        }
        .tutorial .btn-outline-secondary:hover {
            background-color: var(--ins-body-color);
            color: var(--ins-body-bg);
        }
        .tutorial :focus-visible { outline: 3px solid var(--ins-body-color) !important; outline-offset: 3px; }
        .tutorial [hidden] { display: none !important; }
    </style>
@endsection

@section('content')
    <main class="container py-4 tutorial" data-tutorial>
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-xl-6">
                <div class="card">
                    <h1 class="h3 mb-2">Your account tutorial</h1>
                    <p class="mb-4">A short guide to your {{ auth()->user()->role === 'client' ? 'client' : (auth()->user()->role === 'owner' ? 'studio owner' : 'freelancer') }} tools. You can replay it from your profile menu.</p>
                    <p id="tutorial-progress" class="fw-semibold" role="status" aria-live="polite">Step 1 of {{ count($steps) }}</p>

                    @foreach($steps as [$heading, $description])
                        <section data-tutorial-step @if(!$loop->first) hidden @endif aria-labelledby="tutorial-heading-{{ $loop->index }}">
                            <h2 id="tutorial-heading-{{ $loop->index }}" class="h4 mb-3" tabindex="-1">{{ $heading }}</h2>
                            <p class="mb-4">{{ $description }}</p>
                        </section>
                    @endforeach

                    <noscript><p>Use Skip to open your dashboard. Enable JavaScript to move through each tutorial step.</p></noscript>
                    <form method="POST" action="{{ route('onboarding.complete') }}" data-tutorial-form>
                        @csrf
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-tutorial-back hidden>Back</button>
                            <button type="submit" class="btn btn-outline-secondary" data-tutorial-skip>Skip</button>
                            <button type="button" class="btn btn-primary ms-auto" data-tutorial-next hidden>Next</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    <script src="{{ asset('assets/js/onboarding.js') }}"></script>
@endsection
