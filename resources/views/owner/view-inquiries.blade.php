@extends('layouts.owner.app')
@section('title', 'Inquiries')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="card-title">
                                <h4 class="card-title">Inquiries</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="col">
                                <ul class="nav nav-tabs mb-3" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a href="#view_inquiries" data-bs-toggle="tab" aria-expanded="false" class="nav-link active" aria-selected="true" role="tab">
                                            View Inquiries
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#create_inquiry" data-bs-toggle="tab" aria-expanded="false" class="nav-link" aria-selected="false" role="tab" tabindex="-1">
                                            Create Inquiry
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    {{-- VIEW INQUIRIES TAB --}}
                                    <div class="tab-pane active show" id="view_inquiries" role="tabpanel">
                                        {{-- FILTERS --}}
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                                                    <span class="fw-semibold">
                                                        <i class="ti ti-filter me-1"></i>Filter By:
                                                    </span>
                                                    <div class="app-filter">
                                                        <select id="exampleFilter" class="form-select form-control" style="min-width: 150px;">
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                        </select>
                                                    </div>
                                                    <div class="app-filter">
                                                        <select id="exampleFilter" class="form-select form-control" style="min-width: 150px;">
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                        </select>
                                                    </div>
                                                    <div class="app-filter">
                                                        <select id="exampleFilter" class="form-select form-control" style="min-width: 150px;">
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                        </select>
                                                    </div>
                                                    <div class="app-filter">
                                                        <select id="exampleFilter" class="form-select form-control" style="min-width: 150px;">
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                            <option value="">exampleFilter</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- TAB CONTENT --}}
                                        <div class="table-responsive">
                                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0" id="inquiriesTable">
                                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                    <tr class="text-uppercase fs-xxs">
                                                        <th>Example</th>
                                                        <th>Example</th>
                                                        <th>Example</th>
                                                        <th>Example</th>
                                                        <th>Example</th>
                                                        <th>Example</th>
                                                        <th class="text-center" style="width: 12%;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- TAB CONTENT --}}
                                    <div class="tab-pane" id="create_inquiry" role="tabpanel">
                                        <form id="newInquiryForm" class="needs-validation" novalidate>
                                            <div class="row g-3">
                                                <div class="col-md-12">
                                                    <label for="subject" class="form-label">Example Field</label>
                                                    <input type="text" class="form-control" id="" name="" placeholder="Example Field" required>
                                                    <div class="invalid-feedback">
                                                        Example error message
                                                    </div>
                                                </div>

                                                <div class="col-12 mt-4">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="ti ti-send me-1"></i> Example Button
                                                    </button>
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
    </div>
@endsection