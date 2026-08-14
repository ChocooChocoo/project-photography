@extends('layouts.auth.app')
@section('title', 'Welcome')

{{-- STYLES --}}
@section('styles')
    <style>
        .onboarding-step {
            display: none;
        }

        .onboarding-step.active {
            display: block;
        }
    </style>
@endsection

{{-- CONTENTS --}}
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xxl-5 col-md-7 col-sm-9">
                <div class="card p-4">
                    <div class="auth-brand text-center mb-4">
                        <a href="" class="logo-dark">
                            <img src="{{ asset('assets/images/logo-black.png') }}" alt="dark logo" height="28">
                        </a>
                        <a href="" class="logo-light">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="logo" height="28">
                        </a>
                        <p class="text-muted w-lg-75 mt-3 mx-auto">Welcome! A few quick steps to get you started.</p>
                    </div>

                    {{-- STEP 1 --}}
                    <div class="onboarding-step active" data-step="1">
                        <div class="text-center mb-4">
                            <i class="ti ti-hand-wave fs-1 text-primary"></i>
                            <h4 class="card-title fw-bold mt-2 mb-2">Welcome to Lumora</h4>
                            <p class="text-muted mb-0">Your photography service platform for booking studios, freelancers, and everything in between.</p>
                        </div>
                    </div>

                    {{-- STEP 2 --}}
                    <div class="onboarding-step" data-step="2">
                        <div class="text-center mb-4">
                            <i class="ti ti-user-heart fs-1 text-primary"></i>
                            <h4 class="card-title fw-bold mt-2 mb-2">Complete Your Profile</h4>
                            <p class="text-muted mb-0">Keep your contact details up to date so photographers and studios can reach you easily.</p>
                        </div>
                    </div>

                    {{-- STEP 3 --}}
                    <div class="onboarding-step" data-step="3">
                        <div class="text-center mb-4">
                            <i class="ti ti-compass fs-1 text-primary"></i>
                            <h4 class="card-title fw-bold mt-2 mb-2">Explore & Book</h4>
                            <p class="text-muted mb-0">Browse verified studios and freelancers, compare packages, and book your next shoot.</p>
                        </div>
                    </div>

                    {{-- NAVIGATION --}}
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-soft-primary" id="backBtn">Back</button>

                        <form method="POST" action="{{ route('onboarding.complete') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link text-muted text-decoration-none" id="skipBtn">Skip</button>
                        </form>

                        <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            let currentStep = 1;
            const totalSteps = 3;

            function showStep(step) {
                $('.onboarding-step').removeClass('active');
                $(`.onboarding-step[data-step="${step}"]`).addClass('active');

                $('#backBtn').toggle(step > 1);

                if (step === totalSteps) {
                    $('#nextBtn').text('Finish');
                } else {
                    $('#nextBtn').text('Next');
                }
            }

            $('#nextBtn').on('click', function() {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                } else {
                    $('#skipBtn').trigger('click');
                }
            });

            $('#backBtn').on('click', function() {
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                }
            });

            showStep(currentStep);
        });
    </script>
@endsection
