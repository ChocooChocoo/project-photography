@extends('layouts.owner.app')
@section('title', 'Manage Discounts')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Discount Rules</h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-3">
                                <li class="nav-item">
                                    <a href="#view-discounts" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                        View Discount Rules
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#create-discounts" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        Create Discount Rule
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane show active" id="view-discounts">
                                    <div data-table data-table-rows-per-page="10" id="discountsTable">
                                        <div class="card-header border-light justify-content-between">
                                            <div class="d-flex gap-2">
                                                <div class="app-search">
                                                    <input type="search" class="form-control" placeholder="Search discount rules..." data-table-search>
                                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                    <tr class="text-uppercase fs-xxs">
                                                        <th data-table-sort>Studio</th>
                                                        <th data-table-sort>Rule Name</th>
                                                        <th data-table-sort>Percentage</th>
                                                        <th data-table-sort>Description</th>
                                                        <th data-table-sort>Status</th>
                                                        <th class="text-center" style="width: 1%;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($studios as $studio)
                                                        @forelse($studio->discountRules as $rule)
                                                        <tr>
                                                            <td>{{ $studio->studio_name }}</td>
                                                            <td class="fw-medium">{{ $rule->name }}</td>
                                                            <td>{{ rtrim(rtrim(number_format($rule->percentage, 2), '0'), '.') }}%</td>
                                                            <td>{{ $rule->description ?? '—' }}</td>
                                                            <td>
                                                                @if($rule->is_active)
                                                                    <span class="badge badge-soft-success fs-8 px-1 w-100">ACTIVE</span>
                                                                @else
                                                                    <span class="badge badge-soft-secondary fs-8 px-1 w-100">INACTIVE</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <div class="d-flex justify-content-center gap-1">
                                                                    <button type="button" class="btn btn-sm edit-discount" data-id="{{ $rule->id }}"
                                                                        data-studio-id="{{ $studio->id }}" data-name="{{ $rule->name }}"
                                                                        data-percentage="{{ $rule->percentage }}" data-description="{{ $rule->description }}"
                                                                        data-active="{{ $rule->is_active ? 1 : 0 }}" data-bs-toggle="modal" data-bs-target="#editDiscountModal"
                                                                        title="Edit">
                                                                        <i class="ti ti-edit fs-lg"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm delete-discount" data-id="{{ $rule->id }}" data-name="{{ $rule->name }}" title="Delete">
                                                                        <i class="ti ti-trash fs-lg"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        @empty
                                                        @endforelse
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="text-center py-4 text-muted">No studios registered yet.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="card-footer border-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div data-table-pagination-info="discounts"></div>
                                                <div data-table-pagination></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane" id="create-discounts">
                                    <h4 class="card-title text-primary mb-3">Create New Discount Rule</h4>
                                    <form id="createDiscountForm" class="needs-validation" novalidate>
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Studio <span class="text-danger">*</span></label>
                                                <select class="form-select" name="studio_id" required>
                                                    <option value="" disabled selected>Select a studio</option>
                                                    @foreach($studios as $studio)
                                                        <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback">
                                                    Please select a studio.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Rule Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="name" placeholder="e.g., Early Bird Special" maxlength="100" required>
                                                <div class="invalid-feedback">
                                                    Rule name is required.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Discount Percentage <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" name="percentage" placeholder="e.g., 10" step="0.01" min="0" max="100" required>
                                                    <span class="input-group-text">%</span>
                                                    <div class="invalid-feedback">
                                                        Please enter a percentage between 0 and 100.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Status</label>
                                                <div class="form-check form-switch mt-2">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="createRuleActive" value="1" checked>
                                                    <label class="form-check-label" for="createRuleActive">Active</label>
                                                </div>
                                                <div class="form-text">Inactive rules are hidden from clients.</div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="description" rows="3" maxlength="500" placeholder="Describe when this discount applies..."></textarea>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col">
                                                <button class="btn btn-primary" type="submit" id="createDiscountBtn">
                                                    <span id="createDiscountText">Create Discount Rule</span>
                                                    <span id="createDiscountSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
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

    {{-- Edit Discount Rule Modal --}}
    <div class="modal fade" id="editDiscountModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Edit Discount Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editDiscountForm" class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Studio <span class="text-danger">*</span></label>
                                <select class="form-select" name="studio_id" id="editRuleStudio" required>
                                    @foreach($studios as $studio)
                                        <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    Please select a studio.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rule Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="editRuleName" maxlength="100" required>
                                <div class="invalid-feedback">
                                    Rule name is required.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Discount Percentage <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="percentage" id="editRulePercentage" step="0.01" min="0" max="100" required>
                                    <span class="input-group-text">%</span>
                                    <div class="invalid-feedback">
                                        Please enter a percentage between 0 and 100.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="editRuleActive" value="1">
                                    <label class="form-check-label" for="editRuleActive">Active</label>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="editRuleDescription" rows="3" maxlength="500"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editDiscountBtn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            // Create discount rule
            $('#createDiscountForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#createDiscountBtn');
                const textSpan = $('#createDiscountText');
                const spinner = $('#createDiscountSpinner');

                if (!form[0].checkValidity()) {
                    form.addClass('was-validated');
                    return;
                }

                submitBtn.prop('disabled', true);
                textSpan.text('Saving...');
                spinner.removeClass('d-none');

                $.ajax({
                    url: '{{ route("owner.discounts.store") }}',
                    type: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred. Please try again.';
                        let errors = {};

                        if (xhr.status === 422) {
                            errors = xhr.responseJSON.errors;
                            errorMessage = 'Please fix the following errors:';

                            $('.is-invalid').removeClass('is-invalid');
                            $('.invalid-feedback').hide();

                            $.each(errors, function(field, messages) {
                                const input = form.find('[name="' + field + '"]');
                                if (input.length) {
                                    input.addClass('is-invalid');
                                    const feedback = input.closest('.mb-3').find('.invalid-feedback');
                                    if (feedback.length) {
                                        feedback.text(messages.join(', ')).show();
                                    }
                                }
                            });
                        } else if (xhr.responseJSON?.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        if (Object.keys(errors).length === 0) {
                            Swal.fire({
                                title: 'Error!',
                                html: errorMessage,
                                icon: 'error',
                                confirmButtonColor: '#DC3545'
                            });
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        textSpan.text('Create Discount Rule');
                        spinner.addClass('d-none');
                    }
                });
            });

            // Populate edit modal
            $(document).on('click', '.edit-discount', function() {
                const btn = $(this);
                $('#editRuleStudio').val(btn.data('studio-id'));
                $('#editRuleName').val(btn.data('name'));
                $('#editRulePercentage').val(btn.data('percentage'));
                $('#editRuleDescription').val(btn.data('description') || '');
                $('#editRuleActive').prop('checked', btn.data('active') === 1);
                $('#editDiscountForm').data('rule-id', btn.data('id'));
            });

            // Update discount rule
            $('#editDiscountForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const ruleId = form.data('rule-id');
                const submitBtn = $('#editDiscountBtn');

                if (!form[0].checkValidity()) {
                    form.addClass('was-validated');
                    return;
                }

                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

                $.ajax({
                    url: '{{ route("owner.discounts.update", ["rule" => "__RULE_ID__"]) }}'.replace('__RULE_ID__', ruleId),
                    type: 'POST',
                    data: form.serialize() + '&_method=PUT',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred. Please try again.';

                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join(', ');
                        } else if (xhr.responseJSON?.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            title: 'Error!',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonColor: '#DC3545'
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('Save Changes');
                    }
                });
            });

            // Delete discount rule
            $(document).on('click', '.delete-discount', function(e) {
                e.preventDefault();

                const ruleId = $(this).data('id');
                const ruleName = $(this).data('name');

                Swal.fire({
                    title: 'Delete Discount Rule?',
                    html: `Are you sure you want to delete <strong>${ruleName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DC3545',
                    cancelButtonColor: '#6C757D',
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("owner.discounts.destroy", ["rule" => "__RULE_ID__"]) }}'.replace('__RULE_ID__', ruleId),
                            type: 'POST',
                            data: {_method: 'DELETE'},
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonColor: response.alert_color || '#007BFF',
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true
                                    }).then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    title: 'Error!',
                                    text: xhr.responseJSON?.message || 'Failed to delete the discount rule.',
                                    icon: 'error',
                                    confirmButtonColor: '#DC3545'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
