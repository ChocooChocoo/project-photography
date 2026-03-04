@extends('layouts.owner.app')
@section('title', 'View Studios Employee')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="5" class="card">
                        <div class="card-header">
                            <h5 class="card-title">List of Studios Employee</h5>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search schedules...">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">
                                    <i class="ti ti-filter me-1"></i>Filter By:
                                </span>
                                <div class="app-filter">
                                    <select data-table-filter="status" class="me-0 form-select form-control">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th data-table-sort>Studio Name</th>
                                        <th data-table-sort>Full Name</th>
                                        <th data-table-sort>Email Address</th>
                                        <th data-table-sort>Contact Number</th>
                                        <th data-table-sort>Role</th>
                                        <th data-table-sort>Access</th>
                                        <th data-table-sort>Status</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="d-flex">
                                                <div class="avatar-lg me-1">
                                                    <img src="{{ asset('assets/images/products/1.png') }}" class="img-fluid rounded">
                                                </div>
                                                <div>
                                                    <h5 class="mb-1">
                                                        <a href="#" class="link-reset">Studio Name</a>
                                                    </h5>
                                                    <p class="mb-0 fs-xxs">
                                                        <span class="fw-medium">Studio Owner:</span>
                                                        <span class="text-muted">John Doe</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <div>
                                                    <h5 class="mb-1">
                                                        <a href="javascript:void(0)" class="link-reset view-photographer" data-id="5">
                                                            Kaden Rojas
                                                        </a>
                                                    </h5>
                                                    <p class="mb-0 fs-xxs">
                                                        <span class="fw-medium">UUID:</span>
                                                        <span class="text-muted">2572cc78-50fd-4b8b-90e0-082a06d6ec36</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>uK4Kt@example.com</td>
                                        <td>+(63) 423 336 9884</td>
                                        <td>Human Resources Manager</td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-4 align-items-center">
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="fs-xxs text-muted mb-1">CREATE</span>
                                                    <div class="form-check form-check-success form-switch">
                                                        <input class="form-check-input" type="checkbox" role="switch" id="createSwitch" style="width: 2.5em; height: 1.3em;">
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="fs-xxs text-muted mb-1">READ</span>
                                                    <div class="form-check form-check-warning form-switch">
                                                        <input class="form-check-input" type="checkbox" role="switch" id="readSwitch" style="width: 2.5em; height: 1.3em;">
                                                    </div>
                                                </div>

                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="fs-xxs text-muted mb-1">UPDATE</span>
                                                    <div class="form-check form-check-primary form-switch">
                                                        <input class="form-check-input" type="checkbox" role="switch" id="updateSwitch" style="width: 2.5em; height: 1.3em;">
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="fs-xxs text-muted mb-1">DELETE</span>
                                                    <div class="form-check form-check-danger form-switch">
                                                        <input class="form-check-input" type="checkbox" role="switch" id="deleteSwitch" style="width: 2.5em; height: 1.3em;">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-success fs-8 px-1 w-100">ACTIVE</span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="#" class="btn btn-sm">
                                                    <i class="ti ti-eye fs-lg"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm">
                                                    <i class="ti ti-cancel fs-lg"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div data-table-pagination-info="studios"></div>
                                <div data-table-pagination></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection