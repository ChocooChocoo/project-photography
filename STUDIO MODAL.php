@extends('layouts.owner.app')
@section('title', 'Pending Registration')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="5" class="card">
                        <div class="card-header">
                            <h5 class="card-title">List of Studios</h5>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search...">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                                <button class="btn btn-danger d-none">Delete</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th class="ps-3" style="width: 1%;">
                                            <input data-table-select-all class="form-check-input form-check-input-light fs-14 mt-0" type="checkbox" value="option">
                                        </th>
                                        <th data-table-sort>Studio Name</th>
                                        <th data-table-sort>Studio Type</th>
                                        <th data-table-sort>Role</th>
                                        <th data-table-sort>Status</th>
                                        <th data-table-sort>Registration Date</th>
                                        <th data-table-sort>Approved Date</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="ps-3">
                                            <input class="form-check-input form-check-input-light fs-14 product-item-check mt-0" type="checkbox" value="option">
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <div class="avatar-lg me-1">
                                                    <img src="{{ asset('assets/images/products/1.png') }}" alt="Product" class="img-fluid rounded">
                                                </div>
                                                <div>
                                                    <h5 class="mb-1">
                                                        <a href="ecommerce-product-details.html" class="link-reset">Luxe Lens Co.</a>
                                                    </h5>
                                                    <p class="mb-0 fs-xxs">
                                                        <span class="fw-medium">UUID:</span>
                                                        <span class="text-muted">d3e26d71-ffb1-4f29-b3b7-b480f1e55c82</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Photography Studio</td>
                                        <td>Studio Owner</td>
                                        <td><span class="badge badge-soft-warning fs-8 px-1 w-100">PENDING</span></td>
                                        <td>April 12, 2026</td>
                                        <td>PENDING</td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="#" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#studioModal"><i class="ti ti-eye fs-lg"></i></a>
                                                <a href="#" class="btn btn-sm"><i class="ti ti-edit fs-lg"></i></a>
                                                <a href="#" class="btn btn-sm"><i class="ti ti-trash fs-lg"></i></a>
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

    {{-- Modal --}}
    <div class="modal fade" id="studioModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="studioModalLabel">
                        Studio Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-lg-8">
                            <div class="d-flex align-items-center flex-column flex-md-row">
                                <div class="flex-shrink-0 mb-3 mb-md-0">
                                    <img src="{{ asset('assets/images/sellers/7.png') }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="Studio Logo">
                                </div>
                                
                                <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                    <h2 class="mb-1 h3 h3-md">Luxe Lens Photography Studio</h2>
                                    <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                        <span class="badge badge-soft-success p-1">Pending Studio Status</span>
                                    </div>
                                    
                                    <p class="text-muted mb-0">
                                        <i class="ti ti-map-pin me-1"></i> Dasmariñas City, Cavite | Established: 2018
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">STUDIO IDENTIFICATION INFORMATION</h5>
                                {{-- Studio Name --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="building" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Studio Name</label>
                                            <p class="mb-0 fw-medium">Luxe Lens Co.</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Studio Type --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="briefcase" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Studio Type</label>
                                            <p class="mb-0 fw-medium">Photography Studio</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Year Established --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="calendar" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Year Established</label>
                                            <p class="mb-0 fw-medium">2023</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Studio Description --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="file-text" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Studio Description</label>
                                            <p class="mb-0 fw-medium">Professional photography studio specializing in weddings, events, and portraits with high-quality equipment and experienced photographers.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">STUDIO OWNER INFORMATION</h5>
                                {{-- Owner Name --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="user" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Owner Name</label>
                                            <p class="mb-0 fw-medium">Camden Melton</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Owner UUID --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="key-round" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">UUID</label>
                                            <p class="mb-0 fw-medium text-truncate">d3e26d71-ffb1-4f29-b3b7-b480f1e55c82</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Email Address --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="mail" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Email Address</label>
                                            <p class="mb-0 fw-medium">john.alexander@example.com</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Mobile Number --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="phone" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Mobile Number</label>
                                            <p class="mb-0 fw-medium">+(63) 912 345 6789</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Account Status --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="shield-check" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Account Status</label>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span class="badge badge-soft-success px-2 fw-medium">Verified</span>
                                                <span class="text-muted small">Email verified on Jan 15, 2024</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- User Role --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="user-star" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">User Role</label>
                                            <p class="mb-0 fw-medium">Studio Owner</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">STUDIO LOCATION INFORMATION</h5>
                                {{-- Province --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="map" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Province</label>
                                            <p class="mb-0 fw-medium">Cavite</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Municipality --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="map-pin" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Municipality</label>
                                            <p class="mb-0 fw-medium">Dasmarinas</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Barangay --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="navigation" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Barangay</label>
                                            <p class="mb-0 fw-medium">Barangay Salawag</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- ZIP Code --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="hash" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">ZIP Code</label>
                                            <p class="mb-0 fw-medium">4114</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Street Address --}}
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="home" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Street Address</label>
                                            <p class="mb-0 fw-medium">Blk. 1, 2 & 3 Dublin St. Phase II</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">STUDIO CONTACT INFORMATION</h5>
                                {{-- Studio Contact Number --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="phone" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Studio Contact Number</label>
                                            <p class="mb-0 fw-medium">+(63) 000 000 0000</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Studio Email Address --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="mail" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Studio Email Address</label>
                                            <p class="mb-0 fw-medium">co.luxelens@gmail.com</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Social Media Links --}}
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="link" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Social Media Links</label>
                                            <p class="mb-0 fw-medium">
                                                <a href="https://www.facebook.com/co.luxelens" target="_blank" class="text-primary text-decoration-none">
                                                    https://www.facebook.com/co.luxelens
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">SERVICE AND BUSINESS INFORMATION</h5>
                                {{-- Photography Categories --}}
                                <div class="col-12">
                                    <label class="text-muted small mb-1">Photography Categories</label>
                                    <div class="row g-2">
                                        <div class="col">
                                            <div class="list-group">
                                                {{-- Wedding Photography Category --}}
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-check text-success me-2 mt-1"></i>
                                                        <div>
                                                            <h5 class="mb-1 fw-semibold">Wedding Photography</h5>
                                                            <p class="text-muted mb-0">
                                                                {{-- It is blank upon registration of studio --}}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                                
                                                {{-- Portrait Photography Category --}}
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-check text-success me-2 mt-1"></i>
                                                        <div>
                                                            <h5 class="mb-1 fw-semibold">Portrait Photography</h5>
                                                            <p class="text-muted mb-0">
                                                                {{-- It is blank upon registration of studio --}}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                                
                                                {{-- Food Photography Category --}}
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-check text-success me-2 mt-1"></i>
                                                        <div>
                                                            <h5 class="mb-1 fw-semibold">Food Photography</h5>
                                                            <p class="text-muted mb-0">
                                                                {{-- It is blank upon registration of studio --}}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="list-group">
                                                {{-- Wedding Photography Category --}}
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-check text-success me-2 mt-1"></i>
                                                        <div>
                                                            <h5 class="mb-1 fw-semibold">Wedding Photography</h5>
                                                            <p class="text-muted mb-0">
                                                                {{-- It is blank upon registration of studio --}}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                                
                                                {{-- Portrait Photography Category --}}
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-check text-success me-2 mt-1"></i>
                                                        <div>
                                                            <h5 class="mb-1 fw-semibold">Portrait Photography</h5>
                                                            <p class="text-muted mb-0">
                                                                {{-- It is blank upon registration of studio --}}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                                
                                                {{-- Food Photography Category --}}
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-check text-success me-2 mt-1"></i>
                                                        <div>
                                                            <h5 class="mb-1 fw-semibold">Food Photography</h5>
                                                            <p class="text-muted mb-0">
                                                                {{-- It is blank upon registration of studio --}}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Service Coverage Area --}}
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="map-pin" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Service Coverage Area</label>
                                            <div class="d-flex flex-wrap gap-1">
                                                <span class="badge badge-soft-secondary fs-6 p-1 fw-medium">City of General Trias</span>
                                                <span class="badge badge-soft-secondary fs-6 p-1 fw-medium">City of Dasmarinas</span>
                                                <span class="badge badge-soft-secondary fs-6 p-1 fw-medium">City of Imus</span>
                                                <span class="badge badge-soft-secondary fs-6 p-1 fw-medium">City of Trece Martires</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Starting Price --}}
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="tag" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Starting Price</label>
                                            <p class="mb-0 fw-medium">PHP 5,000.00</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">STUDIO CONTACT INFORMATION</h5>
                                {{-- Operating Days --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="calendar-days" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Operating Days</label>
                                            <p class="mb-0 fw-medium">Monday – Sunday</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Operating Hours --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="clock" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Operating Hours</label>
                                            <p class="mb-0 fw-medium">9:00 AM – 7:00 PM</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Maximum Bookings Per Day --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="users" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Maximum Bookings Per Day</label>
                                            <p class="mb-0 fw-medium">5</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Advance Booking Requirement --}}
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="alert-circle" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Advance Booking Requirement</label>
                                            <p class="mb-0 fw-medium">At least 3 days before event</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">VERIFICATION DOCUMENTS</h5>
                                {{-- Valid ID --}}
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="id-card" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Valid ID</label>
                                            <p class="mb-0 fw-medium">
                                                <a href="#" class="text-primary text-decoration-none">
                                                    mark_reyes_id.jpg
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Business Permit --}}
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="file-check" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Business Permit</label>
                                            <p class="mb-0 fw-medium">
                                                <a href="#" class="text-primary text-decoration-none">
                                                    cavitelens_business_permit.pdf
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- DTI Registration --}}
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i data-lucide="file-text" class="fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">DTI Registration</label>
                                            <p class="mb-0 fw-medium">
                                                <a href="#" class="text-primary text-decoration-none">
                                                    cavitelens_dti.pdf
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Freelancer Modal Example --}}
    <div class="modal fade" id="pendingStudio" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="pendingStudioLabel">
                        Studio Registration Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        {{-- Left Side: Studio Information --}}
                        <div class="col-md-4 col-lg-3">
                            <div class="text-center">
                                <div class="position-relative d-inline-block mb-3">
                                    <img src="{{ asset('assets/images/sellers/7.png') }}" alt="avatar" 
                                        class="rounded-circle border border-4 border-white shadow-sm" width="150" height="150">
                                    <span class="position-absolute bottom-0 end-0 bg-success border border-3 border-white rounded-circle p-1">
                                        <span class="visually-hidden">Active</span>
                                    </span>
                                </div>
                                <h4 class="mb-1 fw-semibold">Camden Photography</h4>
                                <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                                    <span class="badge badge-soft-primary fw-medium px-2">FREELANCE PHOTOGRAPHER</span>
                                </div>

                                <hr class="my-3 border-dashed">
                                
                                {{-- Quick Stats Section --}}
                                <div class="mt-2">
                                    <h6 class="mb-3 fw-semibold text-uppercase small text-primary">Business Overview</h6>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light-primary rounded-3">
                                                <h5 class="mb-0 fw-medium text-primary">3</h5>
                                                <p class="small text-muted mb-0">Service</p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light-success rounded-3">
                                                <h5 class="mb-0 fw-medium text-success">PHP 5,000.00</h5>
                                                <p class="small text-muted mb-0">Starting Price</p>
                                            </div>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <div class="text-center p-2 bg-light-info rounded-3">
                                                <h5 class="mb-0 fw-medium text-info">6</h5>
                                                <p class="small text-muted mb-0">Packages</p>
                                            </div>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <div class="text-center p-2 bg-light-warning rounded-3">
                                                <h5 class="mb-0 fw-medium text-warning">5</h5>
                                                <p class="small text-muted mb-0">Max Client Daily</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Right Side: Studio Details --}}
                        <div class="col-md-8 col-lg-9">
                            {{-- Owner Information --}}
                            <div class="card border-0 shadow-none mb-4">
                                <div class="card-body p-0">
                                    <h6 class="card-title mb-3 fw-semibold text-uppercase small text-primary">
                                        Owner Information
                                    </h6>
                                    <div class="row g-3">
                                        {{-- Owner Name --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="user" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Owner Name</label>
                                                    <p class="mb-0 fw-medium">Camden Melton</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Owner UUID --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="key-round" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">UUID</label>
                                                    <p class="mb-0 fw-medium text-truncate">d3e26d71-ffb1-4f29-b3b7-b480f1e55c82</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Email Address --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="mail" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Email Address</label>
                                                    <p class="mb-0 fw-medium">john.alexander@example.com</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Mobile Number --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="phone" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Mobile Number</label>
                                                    <p class="mb-0 fw-medium">+(63) 912 345 6789</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Account Status --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="shield-check" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Account Status</label>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <span class="badge badge-soft-success px-2 fw-medium">Verified</span>
                                                        <span class="text-muted small">Email verified on Jan 15, 2024</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- User Role --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="user-star" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">User Role</label>
                                                    <p class="mb-0 fw-medium">Freelance Photographer</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Years of Experience --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="calendar-clock" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Years of Experience</label>
                                                    <p class="mb-0 fw-medium">5 Years Experience</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Professional Bio --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="info" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Professional Bio</label>
                                                    <p class="mb-0 fw-medium">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Location Information --}}
                            <div class="card border-0 shadow-none mb-4">
                                <div class="card-body p-0">
                                    <h6 class="card-title mb-3 fw-semibold text-uppercase small text-primary">
                                        Location Information
                                    </h6>
                                    <div class="row g-3">
                                        {{-- Province --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="map" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Province</label>
                                                    <p class="mb-0 fw-medium">Cavite</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Municipality --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="map-pin" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Municipality</label>
                                                    <p class="mb-0 fw-medium">Dasmarinas</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Barangay --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="navigation" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Barangay</label>
                                                    <p class="mb-0 fw-medium">Barangay Salawag</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ZIP Code --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="hash" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">ZIP Code</label>
                                                    <p class="mb-0 fw-medium">4114</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Street Address --}}
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="home" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Street Address</label>
                                                    <p class="mb-0 fw-medium">Blk. 1, 2 & 3 Dublin St. Phase II</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Services & Pricing --}}
                            <div class="card border-0 shadow-none mb-4">
                                <div class="card-body p-0">
                                    <h6 class="card-title mb-3 fw-semibold text-uppercase small text-primary">
                                        Services & Pricing
                                    </h6>
                                    <div class="row g-3">
                                        {{-- Photography Service Categories --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="camera" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Photography Service Categories</label>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span class="badge badge-soft-secondary px-2 fw-medium">Wedding Photography</span>
                                                        <span class="badge badge-soft-secondary px-2 fw-medium">Event Photography</span>
                                                        <span class="badge badge-soft-secondary px-2 fw-medium">Portrait Photography</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Additional Packages --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="package" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Additional Packages</label>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span class="badge badge-soft-secondary px-2 fw-medium">Drone Photography Add-on</span>
                                                        <span class="badge badge-soft-secondary px-2 fw-medium">Premium Albums</span>
                                                        <span class="badge badge-soft-secondary px-2 fw-medium">Extended Hours Package</span>
                                                        <span class="badge badge-soft-secondary px-2 fw-medium">RAW Files Add-on</span>
                                                        <span class="badge badge-soft-secondary px-2 fw-medium">Wall Art Collection</span>
                                                        <span class="badge badge-soft-secondary px-2 fw-medium">Portrait Photography</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Starting Price --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="tag" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Starting Price</label>
                                                    <p class="mb-0 fw-medium">PHP 5,000.00</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Availability & Schedule --}}
                            <div class="card border-0 shadow-none mb-4">
                                <div class="card-body p-0">
                                    <h6 class="card-title mb-3 fw-semibold text-uppercase small text-primary">
                                        Availability & Schedule
                                    </h6>
                                    <div class="row g-3">
                                        {{-- Operating Days --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="calendar-days" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Operating Days</label>
                                                    <p class="mb-0 fw-medium">Monday – Sunday</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Operating Hours --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="clock" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Operating Hours</label>
                                                    <p class="mb-0 fw-medium">9:00 AM – 7:00 PM</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Maximum Bookings Per Day --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="users" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Maximum Bookings Per Day</label>
                                                    <p class="mb-0 fw-medium">5</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Advance Booking Requirement --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="alert-circle" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Advance Booking Requirement</label>
                                                    <p class="mb-0 fw-medium">At least 3 days before event</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Portfolio / Sample Works --}}
                            <div class="card border-0 shadow-none mt-4">
                                <div class="card-body p-0">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title mb-0 fw-semibold text-uppercase small text-primary">
                                            Portfolio / Sample Works
                                        </h6>
                                    </div>
                                    
                                    {{-- Uploaded Sample Photos --}}
                                    <div class="row g-3 mb-4">
                                        <div class="col-12">
                                            <label class="text-muted small mb-2 d-block">Sample Photos</label>
                                            <div class="row g-2">
                                                {{-- Sample Photo 1 --}}
                                                <div class="col-6 col-md-4 col-lg-3">
                                                    <div class="position-relative border rounded overflow-hidden">
                                                        <img src="{{ asset('assets/images/landing-cta.jpg') }}" 
                                                            class="img-fluid w-100" 
                                                            alt="Wedding Photography Sample">
                                                    </div>
                                                </div>
                                                
                                                {{-- Sample Photo 2 --}}
                                                <div class="col-6 col-md-4 col-lg-3">
                                                    <div class="position-relative border rounded overflow-hidden">
                                                        <img src="{{ asset('assets/images/landing-cta.jpg') }}" class="img-fluid w-100" alt="Portrait Photography Sample">
                                                    </div>
                                                </div>
                                                
                                                {{-- Sample Photo 3 --}}
                                                <div class="col-6 col-md-4 col-lg-3">
                                                    <div class="position-relative border rounded overflow-hidden">
                                                        <img src="{{ asset('assets/images/landing-cta.jpg') }}" class="img-fluid w-100" alt="Portrait Photography Sample">
                                                    </div>
                                                </div>
                                                
                                                {{-- Sample Photo 4 --}}
                                                <div class="col-6 col-md-4 col-lg-3">
                                                    <div class="position-relative border rounded overflow-hidden">
                                                        <img src="{{ asset('assets/images/landing-cta.jpg') }}" class="img-fluid w-100" alt="Portrait Photography Sample">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <a href="" class="fs-8 text-primary text-decoration-none">
                                                    <i data-lucide="eye" class="me-1"></i> View all 12 sample photos
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Verification Documents --}}
                            <div class="card border-0 shadow-none">
                                <div class="card-body p-0">
                                    <h6 class="card-title mb-3 fw-semibold text-uppercase small text-primary">
                                        Verification Documents
                                    </h6>
                                    <div class="row g-3">
                                        {{-- Valid ID --}}
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="id-card" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Valid ID</label>
                                                    <p class="mb-0 fw-medium">
                                                        <a href="#" class="text-primary text-decoration-none">
                                                            melton_camden.jpg
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Template Modal --}}
    <div class="modal fade" id="" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="">
                        Sample Text
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-lg-8">
                            <div class="d-flex align-items-center flex-column flex-md-row">
                                <div class="flex-shrink-0 mb-3 mb-md-0">
                                    <img src="" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="Sample Text">
                                </div>
                            
                                <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                    <h2 class="mb-1 h3 h3-md">Sample Text</h2>
                                    <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                        <span class="badge badge-soft-success p-1">Sample Text</span>
                                    </div>
                                
                                    <p class="text-muted mb-0">
                                        <i class="ti ti-star me-1"></i> Sample Text | Sample Text: Sample Text
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">Sample Text</h5>
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-star fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Sample Text</label>
                                            <p class="mb-0 fw-medium">Sample Text</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-star fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Sample Text</label>
                                            <p class="mb-0 fw-medium">Sample Text</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-star fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Sample Text</label>
                                            <p class="mb-0 fw-medium">Sample Text</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-star fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Sample Text</label>
                                            <p class="mb-0 fw-medium">Sample Text</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">Sample Text</h5>
                                <!-- Repeated structure for owner fields -->
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-star fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Sample Text</label>
                                            <p class="mb-0 fw-medium">Sample Text</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- ... repeat similar blocks for all owner fields ... -->
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">Sample Text</h5>
                                <!-- Location fields with ti ti-star -->
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-star fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Sample Text</label>
                                            <p class="mb-0 fw-medium">Sample Text</p>
                                        </div>
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


