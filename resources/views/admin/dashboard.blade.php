@extends('layouts.admin.app')
@section('title', 'Admin Panel')
@section('content')
        <div class="content-page">
            <div class="container-fluid">              
                <div class="page-title-head d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="fs-xl fw-bold m-0">Dashboard</h4>
                    </div>
                </div>          

                <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1">
                    <!-- Total Sales Widget -->
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                                            <i class="ti ti-credit-card"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-normal">$<span data-target="124.7">0</span>K</h3>
                                        <p class="mb-0 text-muted"><span>Total Sales</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Placed Widget -->
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-24">
                                            <i class="ti ti-shopping-cart"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-normal"><span data-target="2358">0</span></h3>
                                        <p class="mb-0 text-muted"><span>Orders Placed</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Customers Widget -->
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-24">
                                            <i class="ti ti-users"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-normal"><span data-target="839">0</span></h3>
                                        <p class="mb-0 text-muted"><span>Active Customers</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Refund Requests Widget -->
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-24">
                                            <i class="ti ti-rotate-clockwise-2"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-normal"><span data-target="41">0</span></h3>
                                        <p class="mb-0 text-muted"><span>Refund Requests</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="row g-0">
                                    <div class="col-xxl-3 col-xl-6 order-xl-1 order-xxl-0">
                                        <div class="p-3 border-end border-dashed">
                                            <h4 class="card-title mb-0">Total Sales</h4>
                                            <p class="text-muted fs-xs">
                                                You have 21 pending orders awaiting fulfillment.
                                            </p>

                                            <div class="row mt-4">
                                                <div class="col-lg-12">
                                                    <div style="height: 300px;">
                                                        <canvas id="multi-pie-chart"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> <!-- end .p-4-->
                                        <hr class="d-xxl-none border-light m-0">
                                    </div> <!-- end col-->
                                    <div class="col-xxl-9 order-xl-3 order-xxl-1">
                                        <div class="px-4 py-3">
                                            <div class="d-flex justify-content-between mb-3">
                                                <h4 class="card-title">Sales Analytics</h4>
                                                <a href="#!" class="link-reset text-decoration-underline fw-semibold link-offset-3">View Reports <i class="ti ti-arrow-right"></i></a>
                                            </div>

                                            <div dir="ltr">
                                                <div class="mt-3" style="height: 330px;">
                                                    <canvas id="sales-analytics-chart"></canvas>
                                                </div>
                                            </div>
                                        </div> <!-- end .px-4-->
                                    </div> <!-- end col-->
                                    
                                </div> <!-- end row-->
                            </div> <!-- end card-body-->
                        </div> <!-- end card-->
                    </div> <!-- end col-->
                </div> 
            </div>
        </div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/plugins/chartjs/chart.umd.js') }}"></script>
    <script src="{{ asset('assets/js/pages/dashboard.js') }}"></script>
@endsection
