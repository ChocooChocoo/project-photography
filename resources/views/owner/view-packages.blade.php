@extends('layouts.owner.app')
@section('title', 'View Packages')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="10" class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">List of Packages</h4>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search services...">
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
                                        <th data-table-sort>Studio</th>
                                        <th data-table-sort>Category</th>
                                        <th data-table-sort>Package Name</th>
                                        <th data-table-sort>Location</th>
                                        <th data-table-sort>Time Customization</th>
                                        <th data-table-sort>Duration</th>
                                        <th data-table-sort>Price</th>
                                        <th data-table-sort>Online Gallery</th>
                                        <th data-table-sort>Photographers</th>
                                        <th data-table-sort>Max Photos</th>
                                        <th data-table-sort data-column="status">Status</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($packages as $package)
                                        <tr>
                                            <td>{{ $package->studio->studio_name ?? 'N/A' }}</td>
                                            <td>{{ $package->category->category_name ?? 'N/A' }}</td>
                                            <td>{{ $package->package_name }}</td>
                                            <td>
                                                @php
                                                    $locations = is_array($package->package_location) ? $package->package_location : (array)json_decode($package->package_location ?? '[]', true);
                                                @endphp
                                                
                                                @if(in_array('In-Studio', $locations))
                                                    <span class="badge badge-soft-primary fs-8 px-1 d-inline-block mb-1 w-100">
                                                        <i class="ti ti-building me-1"></i> In-Studio
                                                    </span>
                                                @endif
                                                
                                                @if(in_array('On-Location', $locations))
                                                    <span class="badge badge-soft-info fs-8 px-1 d-inline-block {{ in_array('In-Studio', $locations) ? 'mt-1' : '' }} w-100">
                                                        <i class="ti ti-map-pin me-1"></i> On-Location
                                                    </span>
                                                @endif
                                                
                                                @if(empty($locations))
                                                    <span class="badge badge-soft-secondary fs-8 px-1 w-100">
                                                        <i class="ti ti-minus me-1"></i> Not specified
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($package->allow_time_customization)
                                                    <span class="badge badge-soft-success fs-8 px-1 w-100" title="Clients can customize duration">
                                                        <i class="ti ti-clock-edit me-1"></i> Flexible
                                                    </span>
                                                @else
                                                    <span class="badge badge-soft-secondary fs-8 px-1 w-100" title="Fixed duration only">
                                                        <i class="ti ti-clock me-1"></i> Fixed
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($package->duration)
                                                    {{ $package->duration }} hours
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <!-- ==== End: Time Customization Display ==== -->
                                            <td>PHP {{ number_format($package->package_price, 2) }}</td>
                                            <td>
                                                @if($package->online_gallery)
                                                    <span class="badge badge-soft-success fs-8 px-1 w-100">
                                                        <i class="ti ti-check me-1"></i> Yes
                                                    </span>
                                                @else
                                                    <span class="badge badge-soft-secondary fs-8 px-1 w-100">
                                                        <i class="ti ti-x me-1"></i> No
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-semibold">{{ $package->photographer_count ?? 0 }}</span>
                                                <small class="text-muted d-block">photographer(s)</small>
                                            </td>
                                            <td>{{ $package->maximum_edited_photos }}</td>
                                            <td>
                                                @if($package->status == 'active')
                                                    <span class="badge badge-soft-success fs-8 px-1 w-100">ACTIVE</span>
                                                @else
                                                    <span class="badge badge-soft-danger fs-8 px-1 w-100">INACTIVE</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <button class="btn btn-sm">
                                                        <i class="ti ti-edit fs-lg"></i>
                                                    </button>
                                                    <button class="btn btn-sm">
                                                        <i class="ti ti-trash fs-lg"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div data-table-pagination-info="packages"></div>
                                <div data-table-pagination></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection