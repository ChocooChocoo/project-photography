<div class="content-page">
    <div class="container-fluid">
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <div>
                        <h4 class="mb-1">{{ $portalLabel }} Procurement Requests</h4>
                        <p class="text-muted mb-0">Track drafts, returned requests, deliveries, and completed procurements.</p>
                    </div>
                    <a href="{{ route($createRoute) }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> New Procurement Request
                    </a>
                </div>
            </div>

            <div class="col-12">
                <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 align-items-center mb-1">
                    @foreach ($requestWidgets as $summaryCard)
                        <div class="col">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="avatar avatar-lg flex-shrink-0">
                                            <span class="avatar-title bg-{{ $summaryCard['class'] }}-subtle text-{{ $summaryCard['class'] }} rounded fs-24">
                                                <i class="{{ $summaryCard['icon'] }}"></i>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <h4 class="mb-0">{{ $summaryCard['value'] }}</h4>
                                            <p class="mb-0 text-muted">{{ $summaryCard['label'] }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted fs-xs fw-semibold">{{ $summaryCard['progress_label'] }}</span>
                                            <span class="text-muted">{{ $summaryCard['progress'] }}%</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-{{ $summaryCard['class'] }}" style="width: {{ $summaryCard['progress'] }}%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Procurement Queue</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-centered table-hover mb-0">
                                <thead class="bg-light-subtle">
                                    <tr>
                                        <th>Reference</th>
                                        <th>Purpose</th>
                                        <th>Required Date</th>
                                        <th>Estimated Total</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($procurementRequests as $procurementRequest)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $procurementRequest->request_reference }}</div>
                                                <small class="text-muted">{{ $procurementRequest->created_at?->format('M d, Y h:i A') }}</small>
                                            </td>
                                            <td>{{ \Illuminate\Support\Str::limit($procurementRequest->purpose, 70) }}</td>
                                            <td>{{ $procurementRequest->required_date?->format('M d, Y') ?? 'N/A' }}</td>
                                            <td>PHP {{ number_format((float) $procurementRequest->estimated_total, 2) }}</td>
                                            <td><span class="badge badge-soft-{{ $procurementRequest->status_badge_class }}">{{ $procurementRequest->status_label }}</span></td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <button type="button" class="dropdown-item view-request" data-id="{{ $procurementRequest->id }}">
                                                            <i class="ti ti-eye me-2"></i>View
                                                        </button>
                                                        @if ($procurementRequest->canBeEditedByRequester())
                                                            <a href="{{ route($editRouteName, $procurementRequest->id) }}" class="dropdown-item">
                                                                <i class="ti ti-edit me-2"></i>Edit
                                                            </a>
                                                        @endif
                                                        @if ($procurementRequest->status === \App\Models\Procurement\ProcurementRequestModel::STATUS_DELIVERED)
                                                            <button type="button" class="dropdown-item confirm-receipt" data-id="{{ $procurementRequest->id }}">
                                                                <i class="ti ti-package me-2"></i>Confirm Receipt
                                                            </button>
                                                        @endif
                                                        @if ($procurementRequest->canBeCancelledByRequester())
                                                            <button type="button" class="dropdown-item text-danger cancel-request" data-id="{{ $procurementRequest->id }}" data-reference="{{ $procurementRequest->request_reference }}">
                                                                <i class="ti ti-trash me-2"></i>Delete
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">No procurement requests found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="requestDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Procurement Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requestDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmReceiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Procurement Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="confirmReceiptForm">
                    @csrf
                    <div id="confirmReceiptContent"></div>
                    <div class="mt-3">
                        <label class="form-label">Receipt Note</label>
                        <textarea class="form-control" name="receipt_note" rows="3" placeholder="Optional receipt notes"></textarea>
                    </div>
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-success" id="confirmReceiptSubmitBtn">Confirm Receipt</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        let selectedRequestId = null;

        function buildDocuments(documents) {
            if (!documents || Object.keys(documents).length === 0) {
                return '<p class="text-muted mb-0">No documents uploaded yet.</p>';
            }

            let html = '';

            Object.entries(documents).forEach(([type, files]) => {
                html += `<div class="mb-3"><h6 class="text-uppercase small text-muted">${type.replaceAll('_', ' ')}</h6><ul class="mb-0">`;
                files.forEach((file) => {
                    html += `<li><a href="${file.file_url}" target="_blank">${file.file_name}</a></li>`;
                });
                html += '</ul></div>';
            });

            return html;
        }

        function buildTimeline(auditTrails) {
            if (!auditTrails || !auditTrails.length) {
                return '<p class="text-muted mb-0">No audit trail available.</p>';
            }

            return `
                <div class="timeline timeline-icon-based">
                    ${auditTrails.map((audit) => `
                        <div class="timeline-item d-flex align-items-stretch">
                            <div class="timeline-time pe-3 text-muted">${audit.created_at_display || audit.created_at}</div>
                            <div class="timeline-dot ${audit.dot_class}">
                                <i class="${audit.icon} fs-xl ${audit.icon_class}"></i>
                            </div>
                            <div class="timeline-content ps-3 ${audit === auditTrails[auditTrails.length - 1] ? '' : 'pb-4'}">
                                <h5 class="mb-1">${audit.title}</h5>
                                <p class="mb-1 text-muted">${audit.description || 'No remarks provided.'}</p>
                                <span class="text-primary fw-semibold">By ${audit.actor_name}</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function buildDefectReturns(defectReturns) {
            if (!defectReturns || !defectReturns.length) {
                return '';
            }

            return `
                <div class="border rounded p-3">
                    <h6 class="mb-3">Defect Return Tracking</h6>
                    ${defectReturns.map((defectReturn) => `
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between gap-3 mb-2">
                                <div>
                                    <h6 class="mb-1">${defectReturn.item_name}</h6>
                                    <small class="text-muted">${defectReturn.reason_label}</small>
                                </div>
                                <span class="badge badge-soft-${defectReturn.status_badge_class} align-self-start">${defectReturn.status_display}</span>
                            </div>
                            <p class="mb-1"><strong>Qty:</strong> ${defectReturn.reported_quantity}</p>
                            <p class="mb-1"><strong>Requester Note:</strong> ${defectReturn.requester_note || 'None'}</p>
                            <p class="mb-1"><strong>Finance Note:</strong> ${defectReturn.finance_note || 'None'}</p>
                            <p class="mb-0"><strong>Replacement Delivered:</strong> ${defectReturn.replacement_delivered_at || 'Pending'}</p>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function setButtonLoading($button, loadingText) {
            if (!$button || !$button.length) {
                return;
            }

            if (!$button.data('original-html')) {
                $button.data('original-html', $button.html());
            }

            $button.prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${loadingText}
            `);
        }

        function resetButtonLoading($button) {
            if (!$button || !$button.length) {
                return;
            }

            $button.prop('disabled', false);

            if ($button.data('original-html')) {
                $button.html($button.data('original-html'));
            }
        }

        function loadRequestDetails(id, callback) {
            $('#requestDetailsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');

            $.get(`${@json($showRouteBase)}/${id}`, function (response) {
                const data = response.data;
                const itemsHtml = data.items.map((item) => `
                    <tr>
                        <td>${item.item_name}</td>
                        <td>${item.category}</td>
                        <td>${item.quantity} ${item.unit_of_measure}</td>
                        <td>PHP ${Number(item.estimated_unit_cost).toFixed(2)}</td>
                        <td>PHP ${Number(item.estimated_total_cost).toFixed(2)}</td>
                    </tr>
                `).join('');

                $('#requestDetailsContent').html(`
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                                    <div>
                                        <h5 class="mb-1">${data.request_reference}</h5>
                                        <p class="text-muted mb-0">${data.purpose || 'No purpose provided.'}</p>
                                    </div>
                                    <span class="badge badge-soft-${data.status_badge_class} align-self-start">${data.status_display}</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-centered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Category</th>
                                                <th>Qty</th>
                                                <th>Unit Cost</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>${itemsHtml}</tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="border rounded p-3 mb-3">
                                <h6 class="mb-3">Summary</h6>
                                <p class="mb-1"><strong>Required Date:</strong> ${data.required_date_display}</p>
                                <p class="mb-1"><strong>Estimated Total:</strong> PHP ${data.estimated_total}</p>
                                <p class="mb-1"><strong>Approved Total:</strong> PHP ${data.approved_total}</p>
                                <p class="mb-0"><strong>Inventory Bypass Reason:</strong> ${data.inventory_bypass_reason || 'None'}</p>
                            </div>
                            <div class="border rounded p-3">
                                <h6 class="mb-3">Documents</h6>
                                ${buildDocuments(data.documents)}
                            </div>
                        </div>
                        ${data.open_defect_returns && data.open_defect_returns.length ? `
                            <div class="col-12">
                                ${buildDefectReturns(data.open_defect_returns)}
                            </div>
                        ` : ''}
                        <div class="col-12">
                            <div class="border rounded p-3">
                                <h6 class="mb-3">Timeline</h6>
                                ${buildTimeline(data.audit_trails)}
                            </div>
                        </div>
                    </div>
                `);

                if (typeof callback === 'function') {
                    callback(data);
                }
            });
        }

        $('.view-request').on('click', function () {
            const id = $(this).data('id');
            selectedRequestId = id;
            loadRequestDetails(id);
            $('#requestDetailsModal').modal('show');
        });

        $('.cancel-request').on('click', function () {
            const id = $(this).data('id');
            const reference = $(this).data('reference');

            Swal.fire({
                icon: 'warning',
                title: 'Cancel Request?',
                html: `Cancel <strong>${reference}</strong>?`,
                showCancelButton: true,
                confirmButtonColor: '#3475db',
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                $.post(`${@json($cancelRouteBase)}/${id}/cancel`, {
                    _token: '{{ csrf_token() }}'
                }).done(function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelled',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => window.location.reload());
                }).fail(function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Cancel',
                        text: xhr.responseJSON?.message || 'Cancellation failed.',
                        confirmButtonColor: '#3475db'
                    });
                });
            });
        });

        $('.confirm-receipt').on('click', function () {
            const id = $(this).data('id');
            selectedRequestId = id;

            loadRequestDetails(id, function (data) {
                let html = '';
                const defectReasonOptions = data.defect_reason_options || [];
                const itemsForConfirmation = data.open_defect_returns && data.open_defect_returns.length
                    ? data.items.filter((item) => item.has_open_return)
                    : data.items;

                itemsForConfirmation.forEach((item, index) => {
                    const canMarkDefective = !item.has_open_return;
                    const reasonOptionsHtml = defectReasonOptions.map((option) => `
                        <option value="${option.code}">${option.label}</option>
                    `).join('');
                    const equipmentFields = item.category === 'equipment'
                        ? `
                            <div class="receipt-accept-fields row g-3">
                                <div class="col-md-6"><label class="form-label">Serial Number</label><input type="text" class="form-control" name="items[${index}][serial_number]"></div>
                                <div class="col-md-6"><label class="form-label">Acquisition Cost</label><input type="number" step="0.01" class="form-control" name="items[${index}][acquisition_cost]" value="${item.approved_unit_cost || item.estimated_unit_cost}"></div>
                                <div class="col-md-6"><label class="form-label">Asset Location</label><input type="text" class="form-control" name="items[${index}][asset_location]"></div>
                                <div class="col-md-6"><label class="form-label">Warranty Expiry</label><input type="date" class="form-control" name="items[${index}][warranty_expires_at]"></div>
                            </div>
                        `
                        : `
                            <div class="receipt-accept-fields row g-3">
                                <div class="col-md-6"><label class="form-label">Reorder Threshold</label><input type="number" step="0.01" class="form-control" name="items[${index}][reorder_threshold]" value="1"></div>
                            </div>
                        `;

                    html += `
                        <div class="border rounded p-3 mb-3">
                            <input type="hidden" name="items[${index}][procurement_request_item_id]" value="${item.id}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1">${item.item_name}</h6>
                                    <small class="text-muted">${item.category} - ${(item.open_return_reported_quantity || item.quantity)} ${item.unit_of_measure}</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label d-block">Receipt Decision</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input receipt-action-toggle" type="radio" name="items[${index}][receipt_action]" id="items_${index}_accepted" value="accepted" checked data-index="${index}">
                                            <label class="form-check-label" for="items_${index}_accepted">Accept</label>
                                        </div>
                                        ${canMarkDefective ? `
                                            <div class="form-check">
                                                <input class="form-check-input receipt-action-toggle" type="radio" name="items[${index}][receipt_action]" id="items_${index}_defective" value="defective" data-index="${index}">
                                                <label class="form-check-label" for="items_${index}_defective">Defective</label>
                                            </div>
                                        ` : '<span class="badge badge-soft-info">Replacement item awaiting acceptance</span>'}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Received Quantity</label>
                                    <input type="number" step="0.01" class="form-control" name="items[${index}][received_quantity]" value="${item.open_return_reported_quantity || item.quantity}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Condition Notes</label>
                                    <input type="text" class="form-control" name="items[${index}][condition_notes]" placeholder="Optional condition notes">
                                </div>
                                <div class="defect-fields row g-3 d-none" data-index="${index}">
                                    <div class="col-md-6">
                                        <label class="form-label">Defect Reason</label>
                                        <select class="form-select defect-reason-select" name="items[${index}][defect_reason_code]" data-index="${index}">
                                            <option value="">Select a defect reason</option>
                                            ${reasonOptionsHtml}
                                        </select>
                                    </div>
                                    <div class="col-md-6 defect-other-reason d-none" data-index="${index}">
                                        <label class="form-label">Manual Defect Reason</label>
                                        <input type="text" class="form-control" name="items[${index}][defect_reason_other]" placeholder="Provide the manual defect reason">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Defect Note</label>
                                        <textarea class="form-control" name="items[${index}][defect_note]" rows="2" placeholder="Describe the issue found on delivery"></textarea>
                                    </div>
                                </div>
                                ${equipmentFields}
                            </div>
                        </div>
                    `;
                });

                $('#confirmReceiptContent').html(html);
                $('#confirmReceiptModal').modal('show');
            });
        });

        $(document).on('change', '.receipt-action-toggle', function () {
            const index = $(this).data('index');
            const action = $(this).val();
            const $card = $(this).closest('.border.rounded.p-3.mb-3');

            if (action === 'defective') {
                $card.find('.defect-fields[data-index="' + index + '"]').removeClass('d-none');
                $card.find('.receipt-accept-fields').addClass('d-none');
                return;
            }

            $card.find('.defect-fields[data-index="' + index + '"]').addClass('d-none');
            $card.find('.receipt-accept-fields').removeClass('d-none');
            $card.find('.defect-other-reason[data-index="' + index + '"]').addClass('d-none');
        });

        $(document).on('change', '.defect-reason-select', function () {
            const index = $(this).data('index');
            const isOther = $(this).val() === 'other';

            $('.defect-other-reason[data-index="' + index + '"]').toggleClass('d-none', !isOther);
        });

        $('#confirmReceiptForm').on('submit', function (event) {
            event.preventDefault();
            const $submitButton = $('#confirmReceiptSubmitBtn');
            setButtonLoading($submitButton, 'Submitting...');

            $.ajax({
                url: `${@json($confirmReceiptRouteBase)}/${selectedRequestId}/confirm-receipt`,
                method: 'POST',
                data: $(this).serialize(),
                headers: {
                    'Accept': 'application/json'
                },
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Receipt Confirmed',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => window.location.reload());
                },
                error: function (xhr) {
                    resetButtonLoading($submitButton);
                    const errors = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join('\n') : (xhr.responseJSON?.message || 'Receipt confirmation failed.');

                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Confirm Receipt',
                        text: errors,
                        confirmButtonColor: '#3475db'
                    });
                }
            });
        });
    });
</script>
