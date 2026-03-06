@extends('layouts.studio-hr.app')
@section('title', 'Employees Payroll Settings')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title">Employees Payroll Settings</h5>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <form id="filterForm">
                                        <input type="search" class="form-control" placeholder="Search employees..." id="searchInput">
                                        <i data-lucide="search" class="app-search-icon text-muted"></i>
                                    </form>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">
                                    <i class="ti ti-filter me-1"></i>Filter By:
                                </span>

                                {{-- Studio Filter --}}
                                <div class="app-filter">
                                    <select class="me-0 form-select form-control" id="studioFilter">
                                        <option value="">All Studios</option>
                                        @foreach($studios as $studio)
                                            <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Payroll Basis Filter --}}
                                <div class="app-filter">
                                    <select class="me-0 form-select form-control" id="basisFilter">
                                        <option value="">All Types</option>
                                        <option value="attendance_only">Attendance Only</option>
                                        <option value="booking_and_attendance">Booking + Attendance</option>
                                    </select>
                                </div>

                                {{-- Status Filter --}}
                                <div class="app-filter">
                                    <select class="me-0 form-select form-control" id="statusFilter">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>Studio</th>
                                        <th>Employee</th>
                                        <th>Payroll Type</th>
                                        <th>Monthly Base</th>
                                        <th>Allowances</th>
                                        <th>Deductions</th>
                                        <th>Net Monthly</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="payrollTableBody">
                                    @forelse($payrollSettings as $payroll)
                                        @php
                                            $statusBadgeClass = $payroll->is_active ? 'badge-soft-success' : 'badge-soft-secondary';
                                            $statusText = $payroll->is_active ? 'ACTIVE' : 'INACTIVE';
                                            $basisBadgeClass = $payroll->payroll_basis === 'booking_and_attendance' ? 'badge-soft-info' : 'badge-soft-primary';
                                        @endphp
                                        <tr data-payroll-id="{{ $payroll->id }}">
                                            <td>
                                                <div class="d-flex">
                                                    <div>
                                                        <h5 class="mb-1">
                                                            <a href="#" class="link-reset">{{ $payroll->studio->studio_name ?? 'N/A' }}</a>
                                                        </h5>
                                                        <p class="mb-0 fs-xxs">
                                                            <span class="fw-medium">ID:</span>
                                                            <span class="text-muted">{{ $payroll->studio->id ?? 'N/A' }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">
                                                            <a href="javascript:void(0)" class="link-reset view-payroll" data-id="{{ $payroll->id }}">
                                                                {{ $payroll->employee->full_name ?? 'N/A' }}
                                                            </a>
                                                        </h5>
                                                        <p class="mb-0 fs-xxs">
                                                            <span class="fw-medium">{{ $payroll->employee->email ?? 'N/A' }}</span>
                                                        </p>
                                                        <p class="mb-0 fs-xxs">
                                                            <span class="badge badge-soft-secondary">{{ $payroll->employee->role ?? 'N/A' }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge {{ $basisBadgeClass }} fs-8 px-2">
                                                    {{ $payroll->payroll_basis_display }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($payroll->monthly_salary)
                                                    ₱{{ number_format($payroll->monthly_salary, 2) }}
                                                @elseif($payroll->daily_rate)
                                                    ₱{{ number_format($payroll->daily_rate, 2) }}/day
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>₱{{ number_format($payroll->total_allowances, 2) }}</td>
                                            <td>₱{{ number_format($payroll->total_deductions, 2) }}</td>
                                            <td><strong>₱{{ number_format($payroll->base_monthly_net, 2) }}</strong></td>
                                            <td>{{ $payroll->payment_schedule_display }}</td>
                                            <td>
                                                <span class="badge {{ $statusBadgeClass }} fs-8 px-2 w-100 toggle-status" 
                                                      data-id="{{ $payroll->id }}" 
                                                      data-current="{{ $payroll->is_active ? 'active' : 'inactive' }}"
                                                      style="cursor: pointer;">
                                                    {{ $statusText }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <a href="javascript:void(0)" class="btn btn-sm view-payroll" data-id="{{ $payroll->id }}" title="View Details">
                                                        <i class="ti ti-eye fs-lg"></i>
                                                    </a>
                                                    <a href="{{ route('studio-hr.payroll-settings.edit', $payroll->id) }}" class="btn btn-sm" title="Edit">
                                                        <i class="ti ti-edit fs-lg"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm delete-payroll" data-id="{{ $payroll->id }}" data-name="{{ $payroll->employee->full_name ?? 'this employee' }}" title="Delete">
                                                        <i class="ti ti-trash fs-lg"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="dbEmptyRow">
                                            <td colspan="10" class="text-center py-4">
                                                <i class="ti ti-currency-peso fs-1 text-muted"></i>
                                                <p class="mt-2">No payroll settings found.</p>
                                                <a href="{{ route('studio-hr.payroll-settings.create') }}" class="btn btn-primary btn-sm mt-2">
                                                    <i class="ti ti-plus me-1"></i> Setup First Payroll
                                                </a>
                                            </td>
                                        </tr>
                                    @endforelse

                                    <tr id="noResultsRow" style="display: none;">
                                        <td colspan="10" class="text-center py-4">
                                            <i class="ti ti-filter-off fs-1 text-muted"></i>
                                            <p class="mt-2">No payroll settings match the selected filters.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div data-table-pagination-info="payroll"></div>
                                <div data-table-pagination>
                                    {{ $payrollSettings->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- View Payroll Modal --}}
    <div class="modal fade" id="viewPayrollModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="viewPayrollModalLabel">
                        Payroll Settings Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="modalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading payroll details...</p>
                    </div>
                    
                    <div id="modalContent" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    @php
        $payrollSettingsArray = $payrollSettings->map(function($payroll) {
            return [
                'id' => $payroll->id,
                'studio_id' => $payroll->studio_id,
                'studio_name' => $payroll->studio->studio_name ?? 'N/A',
                'employee_name' => $payroll->employee->full_name ?? 'N/A',
                'employee_email' => $payroll->employee->email ?? 'N/A',
                'payroll_basis' => $payroll->payroll_basis,
                'is_active' => $payroll->is_active ? 'active' : 'inactive',
            ];
        })->values()->toArray();
    @endphp
    
    <script>
        const allPayrollSettings = @json($payrollSettingsArray);

        $(document).ready(function() {
            // ==================== CLIENT-SIDE FILTERING ====================
            function applyFilters() {
                const selectedStudio = $('#studioFilter').val();
                const selectedBasis = $('#basisFilter').val();
                const selectedStatus = $('#statusFilter').val();
                const searchTerm = $('#searchInput').val().toLowerCase().trim();

                const filtered = allPayrollSettings.filter(function(item) {
                    const matchesStudio = !selectedStudio || String(item.studio_id) === String(selectedStudio);
                    const matchesBasis = !selectedBasis || item.payroll_basis === selectedBasis;
                    const matchesStatus = !selectedStatus || item.is_active === selectedStatus;
                    const matchesSearch = !searchTerm ||
                        (item.employee_name && item.employee_name.toLowerCase().includes(searchTerm)) ||
                        (item.studio_name && item.studio_name.toLowerCase().includes(searchTerm)) ||
                        (item.employee_email && item.employee_email.toLowerCase().includes(searchTerm));

                    return matchesStudio && matchesBasis && matchesStatus && matchesSearch;
                });

                const matchedIds = new Set(filtered.map(function(item) { return item.id; }));

                let visibleCount = 0;

                $('#payrollTableBody tr[data-payroll-id]').each(function() {
                    const rowId = parseInt($(this).data('payroll-id'));
                    if (matchedIds.has(rowId)) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });

                $('#noResultsRow').toggle(visibleCount === 0);
                $('#dbEmptyRow').toggle(visibleCount === 0 && allPayrollSettings.length === 0);
            }

            $('#studioFilter, #basisFilter, #statusFilter').on('change', applyFilters);
            $('#searchInput').on('input', applyFilters);
            
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                applyFilters();
            });

            // ==================== VIEW PAYROLL DETAILS ====================
            $(document).on('click', '.view-payroll', function() {
                const payrollId = $(this).data('id');
                
                $('#modalLoading').show();
                $('#modalContent').hide().html('');
                $('#viewPayrollModal').modal('show');
                
                $.ajax({
                    url: '/studio-hr/payroll-settings/' + payrollId,
                    method: 'GET',
                    success: function(response) {
                        $('#modalLoading').hide();
                        
                        if (response.success) {
                            const p = response.data;
                            
                            // Status badge
                            const statusBadgeClass = p.is_active ? 'badge-soft-success' : 'badge-soft-secondary';
                            const statusText = p.is_active ? 'ACTIVE' : 'INACTIVE';
                            
                            // Basis badge
                            const basisBadgeClass = p.payroll_basis === 'booking_and_attendance' ? 'badge-soft-info' : 'badge-soft-primary';
                            
                            // Format custom allowances
                            let customAllowancesHtml = '';
                            if (p.custom_allowances && p.custom_allowances.length > 0) {
                                p.custom_allowances.forEach(function(item) {
                                    customAllowancesHtml += `
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i class="ti ti-star fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">${item.name}</label>
                                                    <p class="mb-0 fw-medium">₱${parseFloat(item.amount).toFixed(2)}</p>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                });
                            }
                            
                            // Format custom deductions
                            let customDeductionsHtml = '';
                            if (p.custom_deductions && p.custom_deductions.length > 0) {
                                p.custom_deductions.forEach(function(item) {
                                    customDeductionsHtml += `
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i class="ti ti-star fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">${item.name}</label>
                                                    <p class="mb-0 fw-medium">₱${parseFloat(item.amount).toFixed(2)}</p>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                });
                            }
                            
                            const content = `
                                <div class="row align-items-center mb-4">
                                    <div class="col-12">
                                        <div class="d-flex align-items-center flex-column flex-md-row">
                                            <div class="flex-grow-1 text-center text-md-start">
                                                <h2 class="mb-1 h3">${p.employee.full_name}</h2>
                                                <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                                    <span class="badge ${basisBadgeClass} p-1 me-2">${p.payroll_basis_display}</span>
                                                    <span class="badge ${statusBadgeClass} p-1">${statusText}</span>
                                                </div>
                                            
                                                <p class="text-muted mb-0">
                                                    <i class="ti ti-building me-1"></i> ${p.studio_name} | 
                                                    <i class="ti ti-mail me-1"></i> ${p.employee.email}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col">
                                        {{-- SALARY INFORMATION --}}
                                        <div class="row g-2 mb-3">
                                            <h5 class="card-title text-primary">Salary Information</h5>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-currency-peso fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Monthly Salary</label>
                                                        <p class="mb-0 fw-medium">${p.monthly_salary ? '₱' + parseFloat(p.monthly_salary).toFixed(2) : 'N/A'}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-calendar-stats fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Daily Rate</label>
                                                        <p class="mb-0 fw-medium">${p.daily_rate ? '₱' + parseFloat(p.daily_rate).toFixed(2) : 'N/A'}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-clock-hour-4 fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Hourly Rate</label>
                                                        <p class="mb-0 fw-medium">${p.hourly_rate ? '₱' + parseFloat(p.hourly_rate).toFixed(2) : 'N/A'}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            ${p.per_booking_rate ? `
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-calendar-check fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Per Booking Rate</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.per_booking_rate).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            ` : ''}
                                            ${p.booking_commission_percentage ? `
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-percentage fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Commission Percentage</label>
                                                        <p class="mb-0 fw-medium">${parseFloat(p.booking_commission_percentage).toFixed(2)}%</p>
                                                    </div>
                                                </div>
                                            </div>
                                            ` : ''}
                                        </div>

                                        {{-- ALLOWANCES --}}
                                        <div class="row g-2 mb-3">
                                            <h5 class="card-title text-success">Allowances</h5>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-success rounded-circle p-2">
                                                            <i class="ti ti-bowl fs-20 text-success"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Rice Allowance</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.rice_allowance).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-success rounded-circle p-2">
                                                            <i class="ti ti-shirt fs-20 text-success"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Clothing Allowance</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.clothing_allowance).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-success rounded-circle p-2">
                                                            <i class="ti ti-wash fs-20 text-success"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Laundry Allowance</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.laundry_allowance).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-success rounded-circle p-2">
                                                            <i class="ti ti-bus fs-20 text-success"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Transportation Allowance</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.transportation_allowance).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-success rounded-circle p-2">
                                                            <i class="ti ti-tools-kitchen fs-20 text-success"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Meal Allowance</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.meal_allowance).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-success rounded-circle p-2">
                                                            <i class="ti ti-coins fs-20 text-success"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Other Allowances</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.other_allowances).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            ${customAllowancesHtml}
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-success rounded-circle p-2">
                                                            <i class="ti ti-sum fs-20 text-success"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1 fw-bold">Total Allowances</label>
                                                        <p class="mb-0 fw-bold text-success">₱${parseFloat(p.total_allowances).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- DEDUCTIONS --}}
                                        <div class="row g-2 mb-3">
                                            <h5 class="card-title text-danger">Deductions</h5>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-danger rounded-circle p-2">
                                                            <i class="ti ti-shield-lock fs-20 text-danger"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">SSS</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.sss_deduction).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-danger rounded-circle p-2">
                                                            <i class="ti ti-heartbeat fs-20 text-danger"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">PhilHealth</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.phic_deduction).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-danger rounded-circle p-2">
                                                            <i class="ti ti-home fs-20 text-danger"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Pag-IBIG</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.hdmf_deduction).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-danger rounded-circle p-2">
                                                            <i class="ti ti-file-invoice fs-20 text-danger"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Withholding Tax</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.tax_withholding).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-danger rounded-circle p-2">
                                                            <i class="ti ti-credit-card fs-20 text-danger"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">SSS Loan</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.sss_loan_deduction).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-danger rounded-circle p-2">
                                                            <i class="ti ti-home-2 fs-20 text-danger"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Pag-IBIG Loan</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.hdmf_loan_deduction).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-danger rounded-circle p-2">
                                                            <i class="ti ti-cash fs-20 text-danger"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Cash Advance</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.cash_advance_deduction).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-danger rounded-circle p-2">
                                                            <i class="ti ti-minus fs-20 text-danger"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Other Deductions</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.other_deductions).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            ${customDeductionsHtml}
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-danger rounded-circle p-2">
                                                            <i class="ti ti-sum fs-20 text-danger"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1 fw-bold">Total Deductions</label>
                                                        <p class="mb-0 fw-bold text-danger">₱${parseFloat(p.total_deductions).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- PAYMENT & SCHEDULE --}}
                                        <div class="row g-2 mb-3">
                                            <h5 class="card-title text-info">Payment & Schedule</h5>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-info rounded-circle p-2">
                                                            <i class="ti ti-calendar fs-20 text-info"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Payment Schedule</label>
                                                        <p class="mb-0 fw-medium">${p.payment_schedule_display}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-info rounded-circle p-2">
                                                            <i class="ti ti-building-bank fs-20 text-info"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Bank</label>
                                                        <p class="mb-0 fw-medium">${p.bank_name || 'N/A'}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-info rounded-circle p-2">
                                                            <i class="ti ti-numbers fs-20 text-info"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Account Number</label>
                                                        <p class="mb-0 fw-medium">${p.bank_account_number || 'N/A'}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-info rounded-circle p-2">
                                                            <i class="ti ti-user fs-20 text-info"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Account Name</label>
                                                        <p class="mb-0 fw-medium">${p.bank_account_name || 'N/A'}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-info rounded-circle p-2">
                                                            <i class="ti ti-credit-card fs-20 text-info"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Payment Method</label>
                                                        <p class="mb-0 fw-medium">${p.payment_method ? p.payment_method.replace('_', ' ').toUpperCase() : 'N/A'}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- SUMMARY --}}
                                        <div class="row g-2 mb-3">
                                            <h5 class="card-title text-warning">Summary</h5>
                                            <div class="col-12 col-md-3">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-warning rounded-circle p-2">
                                                            <i class="ti ti-currency-peso fs-20 text-warning"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Base Monthly</label>
                                                        <p class="mb-0 fw-medium">₱${p.monthly_salary ? parseFloat(p.monthly_salary).toFixed(2) : '0.00'}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-warning rounded-circle p-2">
                                                            <i class="ti ti-plus fs-20 text-warning"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">+ Allowances</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.total_allowances).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-warning rounded-circle p-2">
                                                            <i class="ti ti-minus fs-20 text-warning"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">- Deductions</label>
                                                        <p class="mb-0 fw-medium">₱${parseFloat(p.total_deductions).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-warning rounded-circle p-2">
                                                            <i class="ti ti-sum fs-20 text-warning"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1 fw-bold">NET MONTHLY</label>
                                                        <p class="mb-0 fw-bold text-warning">₱${parseFloat(p.base_monthly_net).toFixed(2)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- NOTES --}}
                                        ${p.notes ? `
                                        <div class="row g-2 mb-3">
                                            <div class="col-12">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-secondary rounded-circle p-2">
                                                            <i class="ti ti-notes fs-20 text-secondary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Notes</label>
                                                        <p class="mb-0 fw-medium">${p.notes}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        ` : ''}

                                        {{-- CREATED INFO --}}
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-secondary rounded-circle p-2">
                                                            <i class="ti ti-calendar-time fs-20 text-secondary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Created</label>
                                                        <p class="mb-0 fw-medium">${p.created_at} by ${p.created_by}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            $('#modalContent').html(content).show();
                        } else {
                            $('#modalContent').html(`
                                <div class="text-center py-4">
                                    <i class="ti ti-alert-circle fs-1 text-danger"></i>
                                    <p class="mt-2 text-danger">Failed to load payroll details.</p>
                                </div>
                            `).show();
                        }
                    },
                    error: function() {
                        $('#modalLoading').hide();
                        $('#modalContent').html(`
                            <div class="text-center py-4">
                                <i class="ti ti-alert-circle fs-1 text-danger"></i>
                                <p class="mt-2 text-danger">An error occurred while loading payroll details.</p>
                            </div>
                        `).show();
                    }
                });
            });

            // ==================== TOGGLE STATUS ====================
            $(document).on('click', '.toggle-status', function() {
                const payrollId = $(this).data('id');
                const currentStatus = $(this).data('current');
                const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                const $badge = $(this);
                
                Swal.fire({
                    icon: 'question',
                    title: 'Toggle Status',
                    text: `Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this payroll setting?`,
                    showCancelButton: true,
                    confirmButtonColor: newStatus === 'active' ? '#28a745' : '#dc3545',
                    confirmButtonText: newStatus === 'active' ? 'Yes, activate' : 'Yes, deactivate',
                    cancelButtonColor: '#3475db',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/studio-hr/payroll-settings/' + payrollId + '/status',
                            method: 'PUT',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: {
                                is_active: newStatus === 'active' ? 1 : 0
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true,
                                        didClose: () => {
                                            window.location.reload();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: response.message,
                                        confirmButtonColor: '#3475db'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Failed to update status.',
                                    confirmButtonColor: '#3475db'
                                });
                            }
                        });
                    }
                });
            });

            // ==================== DELETE PAYROLL ====================
            $(document).on('click', '.delete-payroll', function() {
                const payrollId = $(this).data('id');
                const employeeName = $(this).data('name');
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Delete Payroll Settings',
                    html: `Are you sure you want to delete payroll settings for <strong>${employeeName}</strong>? This action cannot be undone.`,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonColor: '#3475db',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/studio-hr/payroll-settings/' + payrollId,
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true,
                                        didClose: () => {
                                            window.location.reload();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: response.message,
                                        confirmButtonColor: '#3475db'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Failed to delete payroll settings.',
                                    confirmButtonColor: '#3475db'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection