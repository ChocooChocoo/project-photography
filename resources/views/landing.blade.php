<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Book verified photography studios and freelance photographers for weddings, portraits, events, and brand work, with clear peso pricing and private online galleries.">
    <meta name="theme-color" content="#0d0c0b">
    <title>Platinum Photography</title>

    <link href="{{ asset('assets/images/favicon.ico') }}" rel="icon"/>

    <script src="{{ asset('assets/js/config.js') }}"></script>

    <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/landing.css').'?v='.filemtime(public_path('assets/css/landing.css')) }}" rel="stylesheet" type="text/css" />
</head>
<body class="landing">
    <a href="#main" class="visually-hidden-focusable lp-btn lp-btn-gold position-absolute m-2" style="z-index: 20;">Skip to content</a>

    <header class="lp-header">
        <nav class="navbar navbar-expand-lg lp-wrap" aria-label="Main">
            <a class="lp-brand" href="/" aria-label="Platinum Photography, home">
                <b>PLATINUM</b>
                <small>Photography</small>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Open menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <div class="lp-nav ms-auto d-flex align-items-center gap-1">
                    <a href="#services">Services</a>
                    <a href="#how">How it works</a>
                    @if($studios->isNotEmpty())<a href="#studios">Studios</a>@endif
                    <a href="#for-studios">For studios</a>
                    <a href="{{ route('login') }}" class="lp-btn lp-btn-ghost ms-lg-2">Login</a>
                    <a href="{{ route('register') }}" class="lp-btn lp-btn-gold">Book a session</a>
                </div>
            </div>
        </nav>
    </header>

    <main id="main">
        <section class="lp-hero" aria-labelledby="hero-title">
            <div class="lp-hero-media" aria-hidden="true">
                @if(! empty($photos))
                    <img src="{{ asset('storage/'.$photos[0]) }}" alt="" fetchpriority="high">
                @endif
            </div>
            <div class="lp-wrap">
                <p class="lp-eyebrow lp-rise">Photography studios &amp; photographers in Cavite</p>
                <h1 id="hero-title" class="lp-serif lp-rise lp-rise-2">Every frame, <em>beautifully</em> kept.</h1>
                <p class="lp-lead lp-rise lp-rise-3">Book verified studios and freelance photographers for weddings, portraits, events, and brand work. Clear prices in pesos, a photographer who checks in when they arrive, and your photos delivered to a private gallery.</p>
                <div class="lp-actions lp-rise lp-rise-3">
                    <a href="{{ route('register') }}" class="lp-btn lp-btn-gold">Book a session</a>
                    <a href="#for-studios" class="lp-btn lp-btn-ghost">List your studio</a>
                </div>
                @if(($counts['studios'] ?? 0) > 0 || ($counts['freelancers'] ?? 0) > 0)
                    <div class="lp-proof lp-rise lp-rise-3">
                        @if($counts['studios'] > 0)
                            <span><strong>{{ number_format($counts['studios']) }}</strong>{{ \Illuminate\Support\Str::plural('studio', $counts['studios']) }} open for booking</span>
                        @endif
                        @if($counts['freelancers'] > 0)
                            <span><strong>{{ number_format($counts['freelancers']) }}</strong>freelance {{ \Illuminate\Support\Str::plural('photographer', $counts['freelancers']) }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        @if(count($photos) >= 4)
            <section class="lp-section" aria-labelledby="work-title">
                <div class="lp-wrap">
                    <div class="lp-heading">
                        <h2 id="work-title" class="lp-serif">Recent work from our studios</h2>
                        <p class="lp-muted">Real packages and portfolios, straight from the photographers you can book.</p>
                    </div>
                    <div class="lp-mosaic">
                        @foreach($photos as $photo)
                            <figure><img src="{{ asset('storage/'.$photo) }}" alt="Photo from a Platinum studio portfolio" loading="lazy" decoding="async"></figure>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="lp-section" id="services" aria-labelledby="services-title">
            <div class="lp-wrap">
                <div class="lp-heading">
                    <h2 id="services-title" class="lp-serif">Made for the moments you keep</h2>
                    <p class="lp-muted">Compare packages side by side: hours, edited photos, cameras, and any discount, before you book.</p>
                </div>
                <div class="lp-services">
                    <article class="lp-service"><span class="lp-index">I</span><h3>Weddings</h3><p>Full-day coverage, multiple locations, and a contract for multi-day events.</p></article>
                    <article class="lp-service"><span class="lp-index">II</span><h3>Portraits</h3><p>Studio sessions, family, and graduation, with edited photos you can compare to the originals.</p></article>
                    <article class="lp-service"><span class="lp-index">III</span><h3>Events</h3><p>Birthdays, debuts, and corporate nights. Flexible packages can run past closing until midnight.</p></article>
                    <article class="lp-service"><span class="lp-index">IV</span><h3>Commercial</h3><p>Product, food, and brand shoots, delivered to a private online gallery.</p></article>
                </div>
            </div>
        </section>

        <section class="lp-section" id="how" aria-labelledby="how-title">
            <div class="lp-wrap">
                <div class="lp-heading">
                    <h2 id="how-title" class="lp-serif">Three steps to your gallery</h2>
                </div>
                <ol class="lp-steps">
                    <li><h3>Choose your photographer</h3><p>Browse studios and freelancers, see their work and cameras, and pick a package that fits your budget.</p></li>
                    <li><h3>Book and pay the deposit</h3><p>Pick a date and time, agree to the terms, and pay securely. Your provider assigns a photographer.</p></li>
                    <li><h3>Shoot, then receive your photos</h3><p>You get an alert when your photographer checks in on site. Afterwards your edited photos arrive in your private gallery.</p></li>
                </ol>
            </div>
        </section>

        @if($studios->isNotEmpty())
            <section class="lp-section" id="studios" aria-labelledby="studios-title">
                <div class="lp-wrap">
                    <div class="lp-heading">
                        <h2 id="studios-title" class="lp-serif">Studios open for booking</h2>
                        <p class="lp-muted">Ratings come from clients who completed a booking.</p>
                    </div>
                    <div class="lp-studios">
                        @foreach($studios as $studio)
                            <article class="lp-studio">
                                @if($studio->studio_logo)
                                    <img src="{{ asset('storage/'.$studio->studio_logo) }}" alt="" loading="lazy">
                                @else
                                    <span class="lp-monogram" aria-hidden="true">{{ mb_substr($studio->studio_name, 0, 1) }}</span>
                                @endif
                                <div>
                                    <h3>{{ $studio->studio_name }}</h3>
                                    <p>
                                        {{ $studio->location->municipality ?? 'Cavite' }}
                                        @if((int) $studio->total_reviews > 0) · <span class="lp-gold">★ {{ number_format((float) $studio->avg_rating, 1) }}</span> ({{ $studio->total_reviews }}) @endif
                                        @if($studio->starting_price) · from ₱{{ number_format($studio->starting_price) }} @endif
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="lp-section" id="for-studios" aria-labelledby="for-studios-title">
            <div class="lp-wrap lp-split">
                <div>
                    <p class="lp-eyebrow">For studios &amp; freelancers</p>
                    <h2 id="for-studios-title" class="lp-serif lp-split-title">Run your whole studio in one place.</h2>
                    <p class="lp-muted mt-3 mb-4 lp-split-lead">Platinum handles bookings, payments, staff, and delivery, so you can spend your time behind the camera.</p>
                    <a href="{{ route('register') }}" class="lp-btn lp-btn-gold">Create a studio account</a>
                </div>
                <ul class="lp-ticks">
                    <li><strong>Packages with their own discounts</strong><span>Set hours, inclusions, and a discount per package.</span></li>
                    <li><strong>Photographers who check in</strong><span>GPS arrival check-in, and a replacement if someone has to leave mid-shoot.</span></li>
                    <li><strong>Staff, payroll, and permissions</strong><span>Give each role only the access it needs.</span></li>
                    <li><strong>Galleries clients love</strong><span>Deliver online, with before-and-after sliders.</span></li>
                </ul>
            </div>
        </section>

        <section class="lp-section lp-cta" aria-labelledby="cta-title">
            <div class="lp-wrap">
                <h2 id="cta-title" class="lp-serif">Your next favourite photo starts here.</h2>
                <p class="lp-muted">Create a free account to browse studios, compare packages, and book.</p>
                <div class="lp-actions">
                    <a href="{{ route('register') }}" class="lp-btn lp-btn-gold">Book a session</a>
                    <a href="{{ route('login') }}" class="lp-btn lp-btn-ghost">I already have an account</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="lp-footer">
        <div class="lp-wrap d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; {{ date('Y') }} Platinum Photography. All rights reserved.</span>
            <span>Prices shown in Philippine pesos (₱).</span>
        </div>
    </footer>

    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js').'?v='.filemtime(public_path('assets/js/app.js')) }}"></script>
</body>
</html>
