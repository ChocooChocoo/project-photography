@extends('layouts.auth.app')
@section('title', 'Admin OTP Verification')

{{-- CONTENT --}}
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
                        <p class="text-muted w-lg-75 mt-3 mx-auto">Enter the 6-digit code sent to your email to finish signing in.</p>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            {{ $errors->first() }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.otp.verify') }}" novalidate>
                        @csrf

                        <div class="form-group mb-2">
                            <label for="code" class="form-label">Verification Code</label>
                            <input type="text" inputmode="numeric" maxlength="6" class="form-control text-center fs-4 fw-semibold" id="code" name="code" placeholder="000000" required autofocus>
                        </div>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary fw-semibold">Verify Code</button>
                        </div>
                    </form>

                    <p class="text-muted text-center mt-4 mb-0">
                        Didn't receive the code? <a href="{{ route('admin.login') }}" class="text-primary">Sign in again to request a new code</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
