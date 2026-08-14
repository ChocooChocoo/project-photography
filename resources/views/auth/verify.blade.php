@extends('layouts.auth.app')
@section('title', 'Email Verification')

{{-- CONTENTS --}}
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xxl-4 col-md-6 col-sm-8">
                <div class="card p-4">
                    <div class="auth-brand text-center mb-4">
                        <a href="" class="logo-dark">
                            <img src="{{ asset('assets/images/logo-black.png') }}" alt="dark logo" height="28">
                        </a>
                        <a href="" class="logo-light">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="logo" height="28">
                        </a>
                    </div>

                    <div class="row">
                        <div class="col">
                            <h4 class="card-title text-primary fs-4 mb-2">Registration Success!</h4>
                            <p class="mb-3">Your account has been successfully registered. To use your account, please verify it by clicking the link sent to your email address.</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <a href="https://mail.google.com" target="_blank" class="btn btn-primary w-100">
                                <i data-lucide="mail" class="me-2"></i> Open Gmail
                            </a>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <a href="{{ route('onboarding') }}" class="btn btn-soft-primary w-100">
                                <i data-lucide="rocket" class="me-2"></i> Start onboarding
                            </a>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="{{ route('login') }}" class="text-primary">
                            <i data-lucide="arrow-left" class="me-1"></i> Back to Login Page
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection