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
        .tutorial .tutorial-label { font-size: .75rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--ins-body-color); opacity: .75; margin-bottom: .25rem; }
        .tutorial .tutorial-lifecycle { display: grid; grid-template-columns: repeat(auto-fit, minmax(7.5rem, 1fr)); gap: .5rem; padding: 0; margin: 0 0 1.25rem; list-style: none; counter-reset: stage; }
        .tutorial .tutorial-lifecycle li { counter-increment: stage; border: 1px solid var(--ins-border-color); border-radius: .5rem; padding: .6rem .7rem; font-size: .875rem; line-height: 1.3; }
        .tutorial .tutorial-lifecycle li::before { content: counter(stage); display: inline-grid; place-items: center; width: 1.5rem; height: 1.5rem; margin-bottom: .35rem; border-radius: 50%; font-weight: 700; font-size: .8rem; background: var(--ins-body-color); color: var(--ins-body-bg); }
        .tutorial .tutorial-lifecycle li strong { display: block; }
    </style>
@endsection

@section('content')
    <main class="container py-4 tutorial" data-tutorial>
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-xl-6">
                <div class="card">
                    <h1 class="h3 mb-2">Your account tutorial</h1>
                    <p class="mb-4">How Platinum works, and what each of your {{ auth()->user()->role === 'client' ? 'client' : (auth()->user()->role === 'owner' ? 'studio owner' : 'freelancer') }} tools is for. You can replay it from your profile menu.</p>
                    <p id="tutorial-progress" class="fw-semibold" role="status" aria-live="polite">Step 1 of {{ count($steps) }}</p>

                    @foreach($steps as $step)
                        <section data-tutorial-step @if(!$loop->first) hidden @endif aria-labelledby="tutorial-heading-{{ $loop->index }}">
                            <h2 id="tutorial-heading-{{ $loop->index }}" class="h4 mb-3" tabindex="-1">{{ $step['title'] }}</h2>
                            <p class="tutorial-label">What it is</p>
                            <p class="mb-3">{{ $step['what'] }}</p>
                            @if(! empty($step['lifecycle']))
                                <p class="tutorial-label">The path every booking takes</p>
                                <ol class="tutorial-lifecycle">
                                    <li><strong>Request</strong>The client picks a package, date, and time.</li>
                                    <li><strong>Deposit</strong>The client pays the deposit or the full price.</li>
                                    <li><strong>Photographer assigned</strong>The studio assigns staff, or a freelancer takes it.</li>
                                    <li><strong>On-site check-in</strong>The photographer checks in with GPS; the client confirms.</li>
                                    <li><strong>Gallery delivered</strong>Photos are approved and published to the client.</li>
                                </ol>
                            @endif
                            <p class="tutorial-label">Why it matters</p>
                            <p class="mb-3">{{ $step['why'] }}</p>
                            @if($step['where'] && \Illuminate\Support\Facades\Route::has($step['where'][1]))
                                <p class="tutorial-label">Where to find it</p>
                                <p class="mb-4"><a href="{{ route($step['where'][1]) }}">{{ $step['where'][0] }}</a></p>
                            @endif
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
    <script src="{{ asset('assets/js/onboarding.js').'?v='.filemtime(public_path('assets/js/onboarding.js')) }}"></script>
@endsection
