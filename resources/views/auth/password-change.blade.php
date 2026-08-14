@extends('layouts.auth.app')
@section('title', 'Change Password')

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
                        <p class="text-muted w-lg-75 mt-3 mx-auto">For your security, please set a new password before continuing.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form id="passwordChangeForm" method="POST" action="{{ route('password.change.store') }}">
                        @csrf

                        <div class="form-group mb-2">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter current password" required>
                                <span class="password-toggle" data-toggle-password="current_password">
                                    <i data-lucide="eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="At least 8 characters" required>
                                <span class="password-toggle" data-toggle-password="new_password">
                                    <i data-lucide="eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" placeholder="Re-enter new password" required>
                                <span class="password-toggle" data-toggle-password="new_password_confirmation">
                                    <i data-lucide="eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-semibold">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            $('[data-toggle-password]').on('click', function() {
                const targetId = $(this).data('toggle-password');
                const input = $('#' + targetId);
                const icon = $(this).find('i');
                const type = input.attr('type') === 'password' ? 'text' : 'password';

                input.attr('type', type);

                if (type === 'text') {
                    icon.attr('data-lucide', 'eye-off');
                } else {
                    icon.attr('data-lucide', 'eye');
                }

                if (window.lucide) {
                    lucide.createIcons();
                }
            });
        });
    </script>
@endsection
