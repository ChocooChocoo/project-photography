<div class="content-page">
    <div class="container-fluid">
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mb-1">Finance Procurement Queue</h4>
                        <p class="text-muted mb-0">Review submitted requests, generate purchase orders, record deliveries, and complete payment processing.</p>
                    </div>
                </div>
            </div>

            @foreach ([
                ['label' => 'Pending Review', 'value' => $requestSummary['pending_review'] ?? 0, 'class' => 'warning'],
                ['label' => 'Approved', 'value' => $requestSummary['approved'] ?? 0, 'class' => 'info'],
                ['label' => 'Ordered', 'value' => $requestSummary['ordered'] ?? 0, 'class' => 'primary'],
                ['label' => 'Delivered', 'value' => $requestSummary['delivered'] ?? 0, 'class' => 'secondary'],
                ['label' => 'Received', 'value' => $requestSummary['received'] ?? 0, 'class' => 'success'],
                ['label' => 'Payment Processing', 'value' => $requestSummary['payment_processing'] ?? 0, 'class' => 'dark'],
            ] as $summaryCard)
                <div class="col-sm-6 col-xl-2">
                    <div class="card">
                        <div class="card-body">
                            <span class="badge badge-soft-{{ $summaryCard['class'] }} mb-2">{{ $summaryCard['label'] }}</span>
                            <h3 class="mb-0">{{ $summaryCard['value'] }}</h3>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Procurement Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-centered table-hover mb-0">
                                <thead class="bg-light-subtle">
                                    <tr>
                                        <th>Reference</th>
                                        <th>Requester</th>
                                        <th>Studio</th>
                                        <th>Status</th>
                                        <th>Estimated Total</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($procurementRequests as $procurementRequest)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $procurementRequest->request_reference }}</div>
                                                <small class="text-muted">{{ $procurementRequest->required_date?->format('M d, Y') ?? 'No date' }}</small>
                                            </td>
                                            <td>{{ $procurementRequest->requester->full_name ?? 'N/A' }}</td>
                                            <td>{{ $procurementRequest->studio->studio_name ?? 'N/A' }}</td>
                                            <td><span class="badge badge-soft-primary">{{ $procurementRequest->status_label }}</span></td>
                                            <td>PHP {{ number_format((float) $procurementRequest->estimated_total, 2) }}</td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <button type="button" class="dropdown-item finance-view" data-id="{{ $procurementRequest->id }}">
                                                            <i class="ti ti-eye me-2"></i>View
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">No procurement records available.</td>
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

<div class="modal fade" id="financeDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Procurement Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="financeDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer" id="financeDetailsFooter">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="purchaseOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="purchaseOrderForm" enctype="multipart/form-data">
                    @csrf
                    <div id="purchaseOrderItems"></div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6"><label class="form-label">Supplier Name</label><input type="text" class="form-control" name="supplier_name" required></div>
                        <div class="col-md-6"><label class="form-label">Supplier Email</label><input type="email" class="form-control" name="supplier_email"></div>
                        <div class="col-md-6"><label class="form-label">Supplier Contact Number</label><input type="text" class="form-control" name="supplier_contact_number"></div>
                        <div class="col-md-6"><label class="form-label">Order Date</label><input type="date" class="form-control" name="order_date" value="{{ now()->toDateString() }}" required></div>
                        <div class="col-md-12"><label class="form-label">Supplier Address</label><textarea class="form-control" name="supplier_address" rows="2"></textarea></div>
                        <div class="col-md-12"><label class="form-label">Delivery Address</label><textarea class="form-control" name="delivery_address" rows="2" required></textarea></div>
                        <div class="col-md-6"><label class="form-label">Payment Terms</label><input type="text" class="form-control" name="payment_terms" placeholder="Net 30 days" required></div>
                        <div class="col-md-6"><label class="form-label">PO Attachments</label><input type="file" class="form-control" name="purchase_order_attachments[]" multiple></div>
                        <div class="col-md-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3"></textarea></div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">Generate PO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deliveryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="deliveryForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3"><label class="form-label">Delivered At</label><input type="date" class="form-control" name="delivered_at" value="{{ now()->toDateString() }}" required></div>
                    <div class="mb-3"><label class="form-label">Delivery Note</label><textarea class="form-control" name="delivery_note" rows="3"></textarea></div>
                    <div class="mb-3"><label class="form-label">Delivery Receipt Files</label><input type="file" class="form-control" name="delivery_receipt_files[]" multiple required></div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Save Delivery</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Invoice Reference</label><input type="text" class="form-control" name="invoice_reference" required></div>
                        <div class="col-md-6"><label class="form-label">Invoice Amount</label><input type="number" step="0.01" class="form-control" name="invoice_amount" required></div>
                        <div class="col-md-6"><label class="form-label">Invoice Date</label><input type="date" class="form-control" name="invoice_date" value="{{ now()->toDateString() }}" required></div>
                        <div class="col-md-6"><label class="form-label">Payment Reference</label><input type="text" class="form-control" name="payment_reference"></div>
                        <div class="col-md-6"><label class="form-label">Supplier Invoice Files</label><input type="file" class="form-control" name="supplier_invoice_files[]" multiple required></div>
                        <div class="col-md-6"><label class="form-label">Payment Proof Files</label><input type="file" class="form-control" name="payment_proof_files[]" multiple required></div>
                        <div class="col-md-12"><label class="form-label">Payment Note</label><textarea class="form-control" name="payment_note" rows="3"></textarea></div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">Start Payment Processing</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="financeReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="financeReviewModalTitle">Finance Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="financeReviewForm">
                    @csrf
                    <input type="hidden" id="financeReviewAction" name="action">
                    <div class="mb-0">
                        <label for="financeReviewNote" class="form-label">Note</label>
                        <textarea class="form-control" id="financeReviewNote" name="note" rows="4" placeholder="Enter your review note"></textarea>
                        <small class="text-muted" id="financeReviewNoteHelp">A note is optional for approval and required for return or rejection.</small>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="financeReviewSubmitBtn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        let selectedRequestId = null;
        let selectedRequestData = null;
        let financeReviewAction = null;
        let reopenFinanceDetailsModal = false;
        let financeReviewSubmitting = false;
        let pendingFinanceActionModal = null;
        let reopenFinanceDetailsFromActionModal = false;
        let financeActionSubmitting = false;

        function showError(xhr, title = 'Action Failed') {
            const errors = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join('\n') : (xhr.responseJSON?.message || 'Unable to process the request.');

            Swal.fire({
                icon: 'error',
                title: title,
                text: errors,
                confirmButtonColor: '#3475db'
            });
        }

        function renderDetails(data) {
            const itemsHtml = data.items.map((item) => `
                <tr>
                    <td>${item.item_name}</td>
                    <td>${item.quantity} ${item.unit_of_measure}</td>
                    <td>${item.category}</td>
                    <td>PHP ${Number(item.estimated_total_cost).toFixed(2)}</td>
                    <td>${item.approved_total_cost !== null ? `PHP ${Number(item.approved_total_cost).toFixed(2)}` : 'Pending'}</td>
                </tr>
            `).join('');

            $('#financeDetailsContent').html(`
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between gap-3 mb-3">
                                <div>
                                    <h5 class="mb-1">${data.request_reference}</h5>
                                    <p class="text-muted mb-0">${data.requester_name} - ${data.studio_name}</p>
                                </div>
                                <span class="badge badge-soft-primary align-self-start">${data.status_display}</span>
                            </div>
                            <p>${data.purpose || 'No purpose provided.'}</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-centered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Category</th>
                                            <th>Estimated</th>
                                            <th>Approved</th>
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
                            <p class="mb-1"><strong>Finance Note:</strong> ${data.finance_review_note || 'None'}</p>
                            <p class="mb-0"><strong>Owner Note:</strong> ${data.owner_review_note || 'None'}</p>
                        </div>
                    </div>
                </div>
            `);

            let footerButtons = '<button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>';

            if (data.permissions.can_finance_review) {
                footerButtons += '<button type="button" class="btn btn-success finance-review-action" data-action="approve">Approve for Owner</button>';
                footerButtons += '<button type="button" class="btn btn-warning finance-review-action" data-action="return">Return</button>';
                footerButtons += '<button type="button" class="btn btn-danger finance-review-action" data-action="reject">Reject</button>';
            }

            if (data.permissions.can_generate_po) {
                footerButtons += '<button type="button" class="btn btn-primary" id="openPurchaseOrderModalBtn">Generate PO</button>';
            }

            if (data.permissions.can_record_delivery) {
                footerButtons += '<button type="button" class="btn btn-info" id="openDeliveryModalBtn">Record Delivery</button>';
            }

            if (data.permissions.can_record_payment) {
                footerButtons += '<button type="button" class="btn btn-dark" id="openPaymentModalBtn">Record Payment</button>';
            }

            if (data.permissions.can_complete_payment) {
                footerButtons += '<button type="button" class="btn btn-success" id="completeProcurementBtn">Complete</button>';
            }

            $('#financeDetailsFooter').html(footerButtons);
        }

        function openFinanceActionModal(modalSelector) {
            pendingFinanceActionModal = modalSelector;
            reopenFinanceDetailsFromActionModal = true;
            financeActionSubmitting = false;
            $('#financeDetailsModal').modal('hide');
        }

        function resetFinanceActionModalState() {
            pendingFinanceActionModal = null;
            reopenFinanceDetailsFromActionModal = false;
            financeActionSubmitting = false;
        }

        function loadDetails(id) {
            $.get(`${@json($showRouteBase)}/${id}`, function (response) {
                selectedRequestId = id;
                selectedRequestData = response.data;
                renderDetails(selectedRequestData);
                $('#financeDetailsModal').modal('show');
            });
        }

        $('.finance-view').on('click', function () {
            loadDetails($(this).data('id'));
        });

        $(document).on('click', '.finance-review-action', function () {
            financeReviewAction = $(this).data('action');
            const requiresNote = financeReviewAction !== 'approve';

            $('#financeReviewAction').val(financeReviewAction);
            $('#financeReviewNote').val('');
            $('#financeReviewModalTitle').text(`Finance ${financeReviewAction.charAt(0).toUpperCase()}${financeReviewAction.slice(1)}`);
            $('#financeReviewNote').attr('placeholder', requiresNote ? 'Enter the reason for this action' : 'Optional approval note');
            $('#financeReviewNoteHelp').text(requiresNote ? 'A note is required for this action.' : 'A note is optional for approval.');
            $('#financeReviewSubmitBtn').text(financeReviewAction === 'approve' ? 'Approve' : financeReviewAction === 'return' ? 'Return' : 'Reject');
            reopenFinanceDetailsModal = true;
            financeReviewSubmitting = false;
            $('#financeDetailsModal').modal('hide');
        });

        $('#financeReviewForm').on('submit', function (event) {
            event.preventDefault();

            const note = ($('#financeReviewNote').val() || '').trim();
            const requiresNote = financeReviewAction !== 'approve';

            if (requiresNote && !note) {
                showError({
                    responseJSON: {
                        message: 'A note is required for this action.'
                    }
                }, 'Finance Review Failed');
                return;
            }

            financeReviewSubmitting = true;
            reopenFinanceDetailsModal = false;

            $.post(`${@json($reviewRouteBase)}/${selectedRequestId}/review`, {
                _token: '{{ csrf_token() }}',
                action: financeReviewAction,
                note: note
            }).done(function (response) {
                $('#financeReviewModal').modal('hide');

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => window.location.reload());
            }).fail(function (xhr) {
                financeReviewSubmitting = false;
                reopenFinanceDetailsModal = true;
                showError(xhr, 'Finance Review Failed');
            });
        });

        $('#financeDetailsModal').on('hidden.bs.modal', function () {
            if (reopenFinanceDetailsModal) {
                $('#financeReviewModal').modal('show');
                return;
            }

            if (pendingFinanceActionModal) {
                $(pendingFinanceActionModal).modal('show');
            }
        });

        $('#financeReviewModal').on('hidden.bs.modal', function () {
            if (reopenFinanceDetailsModal && !financeReviewSubmitting) {
                reopenFinanceDetailsModal = false;
                $('#financeDetailsModal').modal('show');
                return;
            }

            reopenFinanceDetailsModal = false;
            financeReviewSubmitting = false;
        });

        $(document).on('click', '#openPurchaseOrderModalBtn', function () {
            const itemsHtml = selectedRequestData.items.map((item, index) => `
                <div class="border rounded p-3 mb-3">
                    <input type="hidden" name="items[${index}][procurement_request_item_id]" value="${item.id}">
                    <div class="d-flex justify-content-between mb-2">
                        <h6 class="mb-0">${item.item_name}</h6>
                        <small class="text-muted">${item.quantity} ${item.unit_of_measure}</small>
                    </div>
                    <label class="form-label">Approved Unit Cost</label>
                    <input type="number" step="0.01" class="form-control" name="items[${index}][approved_unit_cost]" value="${item.approved_unit_cost ?? item.estimated_unit_cost}" required>
                </div>
            `).join('');

            $('#purchaseOrderItems').html(itemsHtml);
            openFinanceActionModal('#purchaseOrderModal');
        });

        $('#purchaseOrderForm').on('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(this);

            $.ajax({
                url: `${@json($purchaseOrderRouteBase)}/${selectedRequestId}/purchase-order`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'Accept': 'application/json' },
                success: function (response) {
                    financeActionSubmitting = true;
                    reopenFinanceDetailsFromActionModal = false;
                    $('#purchaseOrderModal').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'PO Generated',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => window.location.reload());
                },
                error: function (xhr) { showError(xhr, 'Purchase Order Failed'); }
            });
        });

        $(document).on('click', '#openDeliveryModalBtn', function () {
            openFinanceActionModal('#deliveryModal');
        });

        $('#deliveryForm').on('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(this);

            $.ajax({
                url: `${@json($deliveryRouteBase)}/${selectedRequestId}/delivery`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'Accept': 'application/json' },
                success: function (response) {
                    financeActionSubmitting = true;
                    reopenFinanceDetailsFromActionModal = false;
                    $('#deliveryModal').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'Delivery Saved',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => window.location.reload());
                },
                error: function (xhr) { showError(xhr, 'Delivery Failed'); }
            });
        });

        $(document).on('click', '#openPaymentModalBtn', function () {
            if (selectedRequestData.purchase_order) {
                $('#paymentForm [name="invoice_amount"]').val(selectedRequestData.purchase_order.total_amount.replace(/,/g, ''));
            }

            openFinanceActionModal('#paymentModal');
        });

        $('#paymentForm').on('submit', function (event) {
            event.preventDefault();
            const formData = new FormData(this);

            $.ajax({
                url: `${@json($paymentRouteBase)}/${selectedRequestId}/payment`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'Accept': 'application/json' },
                success: function (response) {
                    financeActionSubmitting = true;
                    reopenFinanceDetailsFromActionModal = false;
                    $('#paymentModal').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'Payment Started',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => window.location.reload());
                },
                error: function (xhr) { showError(xhr, 'Payment Failed'); }
            });
        });

        $('#purchaseOrderModal, #deliveryModal, #paymentModal').on('hidden.bs.modal', function () {
            if (reopenFinanceDetailsFromActionModal && !financeActionSubmitting) {
                reopenFinanceDetailsFromActionModal = false;
                pendingFinanceActionModal = null;
                $('#financeDetailsModal').modal('show');
                return;
            }

            resetFinanceActionModalState();
        });

        $(document).on('click', '#completeProcurementBtn', function () {
            Swal.fire({
                icon: 'question',
                title: 'Complete Procurement?',
                showCancelButton: true,
                confirmButtonColor: '#3475db'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                $.post(`${@json($completeRouteBase)}/${selectedRequestId}/complete`, {
                    _token: '{{ csrf_token() }}'
                }).done(function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Completed',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => window.location.reload());
                }).fail(function (xhr) {
                    showError(xhr, 'Completion Failed');
                });
            });
        });
    });
</script>
