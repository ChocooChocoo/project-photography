@extends('layouts.owner.app')
@section('title', 'Manage Permissions')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">List of Permissions</h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-3">
                                <li class="nav-item">
                                    <a href="#view-permissions" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                        View Permissions
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#create-permissions" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        Create Permissions
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane show active" id="view-permissions">
                                    <div data-table data-table-rows-per-page="10" id="">
                                        <div class="card-header border-light justify-content-end">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fw-semibold">
                                                    <i class="ti ti-filter me-1"></i>Filter By:
                                                </span>

                                                {{-- Filter --}}
                                                <div class="app-filter">
                                                    <select class="me-0 form-select form-control" id="">
                                                        <option value=""></option>
                                                        <option value=""></option>
                                                        <option value=""></option>
                                                    </select>
                                                </div>
                                                <div class="app-filter">
                                                    <select class="me-0 form-select form-control" id="">
                                                        <option value=""></option>
                                                        <option value=""></option>
                                                        <option value=""></option>
                                                    </select>
                                                </div>
                                                <div class="app-filter">
                                                    <select class="me-0 form-select form-control" id="">
                                                        <option value=""></option>
                                                        <option value=""></option>
                                                        <option value=""></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                    <tr class="text-uppercase fs-xxs">
                                                        <th data-table-sort="">Sample Text</th>
                                                        <th data-table-sort="">Sample Text</th>
                                                        <th data-table-sort="">Sample Text</th>
                                                        <th data-table-sort="">Sample Text</th>
                                                        <th data-table-sort="">Sample Text</th>
                                                        <th class="text-center" style="width: 1%;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>

                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="card-footer border-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div data-table-pagination-info="roles"></div>
                                                <div data-table-pagination></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="create-permissions">
                                    <h4 class="card-title text-primary mb-3">Manage Permissions</h4>
                                    <form class="needs-validation" novalidate>
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Example Label</label>
                                                <input type="text" class="form-control" name="" placeholder="" required>
                                                <div class="invalid-feedback">
                                                    Example Invalid Feedback
                                                </div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Example Label</label>
                                                <input type="text" class="form-control" name="" placeholder="" required>
                                                <div class="invalid-feedback">
                                                    Example Invalid Feedback
                                                </div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Example Label</label>
                                                <input type="text" class="form-control" name="" placeholder="" required>
                                                <div class="invalid-feedback">
                                                    Example Invalid Feedback
                                                </div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Select Example</label>
                                                <select class="form-select" name="" required>
                                                    <option value="">Select Example</option>
                                                    <option value="1">Option 1</option>
                                                    <option value="2">Option 2</option>
                                                    <option value="3">Option 3</option>
                                                </select>
                                                <div class="invalid-feedback">
                                                    Example Invalid Feedback
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col">
                                                <button class="btn btn-primary" type="submit">Create Role</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection