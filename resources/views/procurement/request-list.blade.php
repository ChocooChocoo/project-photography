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

            @foreach ([
                ['label' => 'Draft', 'value' => $requestSummary['draft'] ?? 0, 'class' => 'secondary'],
                ['label' => 'Pending Finance', 'value' => $requestSummary['pending'] ?? 0, 'class' => 'warning'],
                ['label' => 'Returned', 'value' => $requestSummary['returned'] ?? 0, 'class' => 'info'],
                ['label' => 'Completed', 'value' => $requestSummary['completed'] ?? 0, 'class' => 'success'],
            ] as $summaryCard)
                <div class="col-sm-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <span class="badge badge-soft-{{ $summaryCard['class'] }} mb-3">{{ $summaryCard['label'] }}</span>
                            <h3 class="mb-0">{{ $summaryCard['value'] }}</h3>
                        </div>
                    </div>
                </div>
            @endforeach

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
                                            <td><span class="badge badge-soft-primary">{{ $procurementRequest->status_label }}</span></td>
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
                        <button type="submit" class="btn btn-success">Confirm Receipt</button>
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

            return auditTrails.map((audit) => `
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h6 class="mb-1">${audit.action}</h6>
                            <p class="mb-1 text-muted">${audit.note || 'No remarks provided.'}</p>
                            <small class="text-muted">${audit.actor_name}</small>
                        </div>
                        <small class="text-muted text-nowrap">${audit.created_at}</small>
                    </div>
                </div>
            `).join('');
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
                                    <span class="badge badge-soft-primary align-self-start">${data.status_display}</span>
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

                data.items.forEach((item, index) => {
                    const equipmentFields = item.category === 'equipment'
                        ? `
                            <div class="col-md-6"><label class="form-label">Serial Number</label><input type="text" class="form-control" name="items[${index}][serial_number]"></div>
                            <div class="col-md-6"><label class="form-label">Acquisition Cost</label><input type="number" step="0.01" class="form-control" name="items[${index}][acquisition_cost]" value="${item.approved_unit_cost || item.estimated_unit_cost}"></div>
                            <div class="col-md-6"><label class="form-label">Asset Location</label><input type="text" class="form-control" name="items[${index}][asset_location]"></div>
                            <div class="col-md-6"><label class="form-label">Warranty Expiry</label><input type="date" class="form-control" name="items[${index}][warranty_expires_at]"></div>
                        `
                        : `
                            <div class="col-md-6"><label class="form-label">Reorder Threshold</label><input type="number" step="0.01" class="form-control" name="items[${index}][reorder_threshold]" value="1"></div>
                        `;

                    html += `
                        <div class="border rounded p-3 mb-3">
                            <input type="hidden" name="items[${index}][procurement_request_item_id]" value="${item.id}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1">${item.item_name}</h6>
                                    <small class="text-muted">${item.category} - ${item.quantity} ${item.unit_of_measure}</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Received Quantity</label>
                                    <input type="number" step="0.01" class="form-control" name="items[${index}][received_quantity]" value="${item.quantity}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Condition Notes</label>
                                    <input type="text" class="form-control" name="items[${index}][condition_notes]" placeholder="Optional condition notes">
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

        $('#confirmReceiptForm').on('submit', function (event) {
            event.preventDefault();

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
