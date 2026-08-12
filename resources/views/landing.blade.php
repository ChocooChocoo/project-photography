<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Platinum Photography</title>

    <link href="{{ asset('assets/images/favicon.ico') }}" rel="icon"/>

    <script src="{{ asset('assets/js/config.js') }}"></script>

    <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
        .hero-section {
            background-color: #1e293b;
        }
        [data-bs-theme="dark"] .hero-section {
            background-color: var(--bs-body-bg);
        }
        .cta-section {
            background-color: #1e293b;
        }
        [data-bs-theme="dark"] .cta-section {
            background-color: var(--bs-body-bg);
        }
        .hero-section h1 {
            animation: fadeInUp 0.8s ease-out;
        }
        .hero-section .lead {
            animation: fadeInUp 0.8s ease-out 0.15s both;
        }
        .hero-section .btn {
            animation: fadeInUp 0.8s ease-out 0.3s both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .service-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
        }
        .testimonial-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--bs-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .theme-settings-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1040;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--bs-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            transition: transform 0.2s;
        }
        .theme-settings-btn:hover {
            transform: scale(1.1);
            color: #fff;
        }
        [data-bs-theme="dark"] .navbar {
            background-color: var(--bs-body-bg) !important;
        }
        [data-bs-theme="dark"] .navbar .navbar-brand {
            color: var(--bs-body-color);
        }
        [data-bs-theme="dark"] .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        [data-bs-theme="dark"] footer {
            background-color: var(--bs-body-bg) !important;
            color: var(--bs-body-color) !important;
        }
        footer {
            background-color: #1e293b;
            border-top: 1px solid var(--bs-border-color);
        }
        footer p {
            color: #fff;
        }
        .navbar {
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        .navbar.is-scrolled {
            background-color: color-mix(in srgb, var(--bs-body-bg) 80%, transparent) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/">
                <i data-lucide="camera" class="text-primary" width="28" height="28"></i>
                Platinum Photography
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <div class="ms-auto d-flex flex-column flex-lg-row gap-2">
                    <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-section text-white text-center py-5">
        <div class="container py-5">
            <h1 class="display-4 fw-bold">Capturing Moments That Last Forever</h1>
            <p class="lead mx-auto mt-3 opacity-75" style="max-width: 640px;">Platinum Photography connects you with professional photographers and studios for weddings, events, portraits, and commercial shoots.</p>
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg mt-4 px-4">Find a Photographer</a>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Our Services</h2>
                <p class="text-muted">Whatever your occasion, we have the right photographer for you.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 text-center shadow-sm border-0">
                        <div class="card-body py-4">
                            <div class="service-icon bg-primary bg-opacity-10">
                                <i data-lucide="heart" class="text-primary" width="32" height="32"></i>
                            </div>
                            <h5 class="card-title">Weddings</h5>
                            <p class="card-text text-muted">Elegant coverage of your special day, from preparations to the final dance.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 text-center shadow-sm border-0">
                        <div class="card-body py-4">
                            <div class="service-icon bg-success bg-opacity-10">
                                <i data-lucide="party-popper" class="text-success" width="32" height="32"></i>
                            </div>
                            <h5 class="card-title">Events</h5>
                            <p class="card-text text-muted">Birthdays, corporate functions, and everything in between, captured beautifully.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 text-center shadow-sm border-0">
                        <div class="card-body py-4">
                            <div class="service-icon bg-warning bg-opacity-10">
                                <i data-lucide="user" class="text-warning" width="32" height="32"></i>
                            </div>
                            <h5 class="card-title">Portraits</h5>
                            <p class="card-text text-muted">Professional headshots and personal portraits that bring out your best.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 text-center shadow-sm border-0">
                        <div class="card-body py-4">
                            <div class="service-icon bg-info bg-opacity-10">
                                <i data-lucide="building-2" class="text-info" width="32" height="32"></i>
                            </div>
                            <h5 class="card-title">Commercial</h5>
                            <p class="card-text text-muted">Product, food, and brand photography tailored to your business needs.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-body-secondary py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">About Us</h2>
            </div>
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-8 text-center">
                    <p class="lead">Platinum Photography connects clients with the finest photographers and studios across the region. Whether you need a wedding photographer, event coverage, professional headshots, or commercial product photography, our platform makes finding and booking the right creative partner effortless.</p>
                    <div class="row g-4 mt-4">
                        <div class="col-sm-4">
                            <h3 class="fw-bold text-primary mb-0">50+</h3>
                            <p class="text-muted mb-0">Photographers</p>
                        </div>
                        <div class="col-sm-4">
                            <h3 class="fw-bold text-primary mb-0">500+</h3>
                            <p class="text-muted mb-0">Bookings Completed</p>
                        </div>
                        <div class="col-sm-4">
                            <h3 class="fw-bold text-primary mb-0">4.8</h3>
                            <p class="text-muted mb-0">Average Rating</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">What Our Clients Say</h2>
                <p class="text-muted">Real feedback from people who booked through our platform.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="testimonial-avatar">MJ</div>
                                <div>
                                    <p class="fw-semibold mb-0">Maria &amp; John</p>
                                    <div class="text-warning">
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="card-text text-muted">"Our wedding photos are absolutely stunning. The photographer captured every emotion perfectly. The booking process was seamless."</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="testimonial-avatar" style="background: var(--bs-success);">AR</div>
                                <div>
                                    <p class="fw-semibold mb-0">Alex Rivera</p>
                                    <div class="text-warning">
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="card-text text-muted">"Booking a photographer for our product launch was effortless. The results exceeded our expectations and the turnaround was incredibly fast."</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="testimonial-avatar" style="background: var(--bs-info);">SL</div>
                                <div>
                                    <p class="fw-semibold mb-0">Sarah Lim</p>
                                    <div class="text-warning">
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                        <i data-lucide="star" width="14" height="14" fill="currentColor"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="card-text text-muted">"The portraits came out beautiful. Professional service from start to finish. Highly recommended to anyone looking for quality photography."</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section text-white py-5">
        <div class="container py-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <h2 class="fw-bold mb-2">Ready to Book Your Photographer?</h2>
                    <p class="lead mb-4 opacity-75">Join Platinum Photography today and find the perfect photographer for your next event.</p>
                    <div class="d-flex flex-column flex-sm-row flex-wrap gap-3">
                        <span class="d-flex align-items-center gap-2">
                            <i data-lucide="badge-check" width="20" height="20"></i>
                            Verified photographers
                        </span>
                        <span class="d-flex align-items-center gap-2">
                            <i data-lucide="calendar-check" width="20" height="20"></i>
                            Easy booking &amp; payment
                        </span>
                        <span class="d-flex align-items-center gap-2">
                            <i data-lucide="image" width="20" height="20"></i>
                            Galleries delivered online
                        </span>
                    </div>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <a href="{{ route('register') }}" class="btn btn-light btn-lg px-5">
                        Get Started
                        <i data-lucide="arrow-right" width="20" height="20" class="ms-1"></i>
                    </a>
                    <p class="small opacity-75 mt-2 mb-0">Free to join — no hidden fees</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="text-white text-center py-3">
        <div class="container">
            <p class="mb-0 opacity-50">&copy; {{ date('Y') }} Platinum Photography. All rights reserved.</p>
        </div>
    </footer>

    <!-- Floating theme settings button -->
    <button class="theme-settings-btn" data-bs-toggle="offcanvas" data-bs-target="#theme-settings-offcanvas" title="Display Settings">
        <i class="ti ti-settings icon-spin fs-24"></i>
    </button>

    <!-- Theme settings offcanvas -->
    @include('layouts.client.theme')

    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <script>
        (function () {
            var nav = document.querySelector('.navbar');
            if (!nav) return;
            var toggle = function () {
                nav.classList.toggle('is-scrolled', window.scrollY > 10);
            };
            window.addEventListener('scroll', toggle, { passive: true });
            toggle();
        })();
    </script>
</body>
</html>
