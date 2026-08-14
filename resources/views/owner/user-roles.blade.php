@extends('layouts.owner.app')
@section('title', 'Assign User Roles')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Assign Roles to Studio Users</h5>
                        </div>
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if (isset($errors) && $errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if ($studios->count() > 1)
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <form method="GET" action="{{ route('owner.user-roles.index') }}">
                                            <label class="form-label">Studio</label>
                                            <select class="form-select" name="studio_id" onchange="this.form.submit()">
                                                @foreach ($studios as $studio)
                                                    <option value="{{ $studio->id }}" @selected($studio->id === $studioId)>{{ $studio->studio_name }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('owner.user-roles.update') }}">
                                @csrf
                                <input type="hidden" name="studio_id" value="{{ $studioId }}">

                                <div class="row">
                                    <div class="col-lg-7">
                                        <h6 class="card-title text-primary mb-3">Studio Users</h6>

                                        @if ($users->isEmpty())
                                            <p class="text-muted">No users are associated with this studio yet.</p>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                        <tr class="text-uppercase fs-xxs">
                                                            <th style="width: 1%;">
                                                                <div class="form-check mb-0">
                                                                    <input class="form-check-input" type="checkbox" id="selectAllUsers">
                                                                </div>
                                                            </th>
                                                            <th>User</th>
                                                            <th>Current Roles</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($users as $user)
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check mb-0">
                                                                        <input class="form-check-input user-checkbox" type="checkbox" name="user_ids[]" value="{{ $user->id }}" id="user_{{ $user->id }}">
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <label class="mb-0" for="user_{{ $user->id }}">
                                                                        <h6 class="mb-1">{{ $user->full_name }}</h6>
                                                                        <small class="text-muted">{{ $user->email }}</small>
                                                                    </label>
                                                                </td>
                                                                <td>
                                                                    @if ($user->roles->isNotEmpty())
                                                                        @foreach ($user->roles as $assignedRole)
                                                                            <span class="badge badge-soft-primary">{{ $assignedRole->display_name }}</span>
                                                                        @endforeach
                                                                    @else
                                                                        <span class="text-muted">No roles assigned</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="col-lg-5">
                                        <h6 class="card-title text-primary mb-3">Roles to Apply</h6>
                                        <p class="text-muted fs-xxs mb-3">Check the users above and the roles to apply, then click Save. Unchecked roles are removed from the selected users.</p>

                                        <div class="border rounded p-3">
                                            @foreach ($roles as $role)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input role-checkbox" type="checkbox" name="role_ids[]" value="{{ $role->id }}" id="role_{{ $role->id }}">
                                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                                        {{ $role->display_name }}
                                                        <small class="text-muted d-block">{{ $role->name }}</small>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i>Save
                                    </button>
                                    <a href="{{ route('owner.role.index') }}" class="btn btn-light ms-2">Back to Roles</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#selectAllUsers').on('change', function() {
                $('.user-checkbox').prop('checked', $(this).is(':checked'));
            });

            $('.user-checkbox').on('change', function() {
                const $checkboxes = $('.user-checkbox');
                $('#selectAllUsers').prop('checked', $checkboxes.length > 0 && $checkboxes.filter(':checked').length === $checkboxes.length);
            });
        });
    </script>
@endsection
