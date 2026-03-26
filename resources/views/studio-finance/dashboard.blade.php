@extends('layouts.studio-finance.app')
@section('title', 'Finance Dashboard')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="page-title-head d-flex align-items-center">
                <div class="flex-grow-1">
                    <h4 class="fs-xl fw-bold m-0">Dashboard</h4>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="avatar-lg bg-soft-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                                <i class="ti ti-building-bank fs-1 text-primary"></i>
                            </div>
                            <h5 class="mb-2">Finance module is ready</h5>
                            <p class="text-muted mb-0">This finance page is currently set up and routed to the dashboard.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
