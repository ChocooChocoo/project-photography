@extends('layouts.auth.app')
@section('title', 'Admin Login')

{{-- STYLES --}}
@section('styles')
    <style>
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            color: #6c757d;
        }

        .password-input-group {
            position: relative;
        }
    </style>
@endsection

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
                        <p class="text-muted w-lg-75 mt-3 mx-auto">Admin sign in. Enter your credentials to continue.</p>
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

                    <form method="POST" action="{{ route('admin.login.authenticate') }}" novalidate>
                        @csrf

                        <div class="form-group mb-2">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="Enter Email Address" required autofocus>
                        </div>

                        <div class="form-group mb-2">
                            <label for="password" class="form-label">Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter Password" required>
                                <span class="password-toggle" id="togglePassword">
                                    <i data-lucide="eye" id="eyeIcon"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary fw-semibold">Sign In</button>
                        </div>
                    </form>

                    <p class="text-muted text-center mt-4 mb-0">
                        Not an admin? <a href="{{ route('login') }}" class="text-primary">Use the regular login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#togglePassword').on('click', function() {
                const passwordInput = $('#password');
                const eyeIcon = $('#eyeIcon');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';

                passwordInput.attr('type', type);

                if (type === 'text') {
                    eyeIcon.attr('data-lucide', 'eye-off');
                } else {
                    eyeIcon.attr('data-lucide', 'eye');
                }

                if (window.lucide) {
                    lucide.createIcons();
                }
            });
        });
    </script>
@endsection
