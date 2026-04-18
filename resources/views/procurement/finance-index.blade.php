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
                                            <td><span class="badge badge-soft-{{ $procurementRequest->status_badge_class }}">{{ $procurementRequest->status_label }}</span></td>
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
                        <button type="submit" class="btn btn-primary" id="purchaseOrderSubmitBtn">Generate PO</button>
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
                        <button type="submit" class="btn btn-primary" id="deliverySubmitBtn">Save Delivery</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="card border shadow-none h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                        <div>
                                            <span class="badge badge-soft-success mb-2">Procurement Payment Statement</span>
                                            <h4 class="mb-1" id="paymentStatementReference">Procurement Request</h4>
                                            <p class="text-muted mb-0" id="paymentStatementSupplier">Supplier information will appear here.</p>
                                        </div>
                                        <div class="avatar avatar-lg flex-shrink-0">
                                            <span class="avatar-title bg-success-subtle text-success rounded fs-24">
                                                <i class="ti ti-file-invoice"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <div id="paymentStatementSummary"></div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label">Invoice Reference</label>
                                            <input type="text" class="form-control" name="invoice_reference" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Invoice Date</label>
                                            <input type="date" class="form-control" name="invoice_date" value="{{ now()->toDateString() }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Invoice Amount</label>
                                            <input type="number" step="0.01" class="form-control" name="invoice_amount" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Payment Reference</label>
                                            <input type="text" class="form-control" name="payment_reference">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Payment Note</label>
                                            <textarea class="form-control" name="payment_note" rows="4" placeholder="Add reconciliation notes, release details, or payment remarks"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="card border shadow-none mb-3">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Supplier & PO Snapshot</h5>
                                    <div id="paymentStatementMeta"></div>
                                </div>
                            </div>
                            <div class="card border shadow-none mb-0">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Supporting Documents</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Supplier Invoice Files</label>
                                        <input type="file" class="form-control" name="supplier_invoice_files[]" multiple required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Payment Proof Files</label>
                                        <input type="file" class="form-control" name="payment_proof_files[]" multiple required>
                                    </div>
                                    <div id="paymentStatementDocuments" class="small text-muted"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary" id="paymentSubmitBtn">Start Payment Processing</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="processReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Defect Return</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="processReturnForm" enctype="multipart/form-data">
                    @csrf
                    <div id="processReturnItems" class="mb-3"></div>
                    <div class="mb-3">
                        <label class="form-label">Finance Note</label>
                        <textarea class="form-control" name="finance_note" rows="3" placeholder="Explain the return handling or supplier coordination" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Return Support Files</label>
                        <input type="file" class="form-control" name="return_support_files[]" multiple>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Return Receipt Files</label>
                        <input type="file" class="form-control" name="return_receipt_files[]" multiple>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-warning" id="processReturnSubmitBtn">Start Return Processing</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="replacementDeliveryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Replacement Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="replacementDeliveryForm" enctype="multipart/form-data">
                    @csrf
                    <div id="replacementDeliveryItems" class="mb-3"></div>
                    <div class="mb-3">
                        <label class="form-label">Delivered At</label>
                        <input type="date" class="form-control" name="delivered_at" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delivery Note</label>
                        <textarea class="form-control" name="delivery_note" rows="3" placeholder="Optional replacement delivery note"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Replacement Delivery Receipt Files</label>
                        <input type="file" class="form-control" name="replacement_delivery_receipt_files[]" multiple required>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-danger" id="replacementDeliverySubmitBtn">Save Replacement Delivery</button>
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

        function buildPaymentStatement(data) {
            const paymentSummary = data.payment_summary || {};
            const purchaseOrder = data.purchase_order || {};
            const documentEntries = Object.entries(data.documents || {}).filter(([type]) => ['purchase_order_attachment', 'delivery_receipt', 'supplier_invoice', 'payment_proof'].includes(type));
            const documentsHtml = documentEntries.length
                ? documentEntries.map(([type, files]) => `
                    <div class="d-flex justify-content-between align-items-center border rounded px-3 py-2 mb-2">
                        <div>
                            <div class="fw-semibold">${type.replaceAll('_', ' ')}</div>
                            <small>${files.length} file(s)</small>
                        </div>
                        <i class="ti ti-paperclip text-muted"></i>
                    </div>
                `).join('')
                : '<p class="mb-0">No supporting documents uploaded yet.</p>';

            $('#paymentStatementReference').text(`${paymentSummary.request_reference || data.request_reference} Payment Statement`);
            $('#paymentStatementSupplier').text(paymentSummary.supplier_name
                ? `${paymentSummary.supplier_name}${paymentSummary.supplier_email ? ' • ' + paymentSummary.supplier_email : ''}`
                : 'Supplier information will appear here.');

            $('#paymentStatementSummary').html(`
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                                    <i class="ti ti-receipt-2"></i>
                                </span>
                            </div>
                            <div class="ms-3">
                                <label class="text-muted small mb-1">Purchase Order</label>
                                <p class="mb-0 fw-semibold">${paymentSummary.po_number || 'Pending PO'}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-title bg-success-subtle text-success rounded-circle fs-20">
                                    <i class="ti ti-cash-banknote"></i>
                                </span>
                            </div>
                            <div class="ms-3">
                                <label class="text-muted small mb-1">Approved Total</label>
                                <p class="mb-0 fw-semibold">PHP ${paymentSummary.approved_total || data.approved_total}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-20">
                                    <i class="ti ti-calendar-event"></i>
                                </span>
                            </div>
                            <div class="ms-3">
                                <label class="text-muted small mb-1">Invoice Date</label>
                                <p class="mb-0 fw-semibold">${paymentSummary.invoice_date_display || data.required_date_display || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-20">
                                    <i class="ti ti-building-bank"></i>
                                </span>
                            </div>
                            <div class="ms-3">
                                <label class="text-muted small mb-1">Payment Terms</label>
                                <p class="mb-0 fw-semibold">${paymentSummary.payment_terms || purchaseOrder.payment_terms || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            $('#paymentStatementMeta').html(`
                <div class="row g-3">
                    <div class="col-12">
                        <label class="text-muted small mb-1">Studio</label>
                        <p class="mb-0 fw-semibold">${data.studio_name}</p>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small mb-1">Requester</label>
                        <p class="mb-0 fw-semibold">${data.requester_name}</p>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small mb-1">Supplier Contact</label>
                        <p class="mb-0 fw-semibold">${paymentSummary.supplier_contact_number || purchaseOrder.supplier_contact_number || 'N/A'}</p>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small mb-1">Delivery Address</label>
                        <p class="mb-0 fw-semibold">${paymentSummary.delivery_address || purchaseOrder.delivery_address || 'N/A'}</p>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small mb-1">PO Note</label>
                        <p class="mb-0 fw-semibold">${purchaseOrder.notes || 'No purchase order note provided.'}</p>
                    </div>
                </div>
            `);

            $('#paymentStatementDocuments').html(documentsHtml);
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
                                <span class="badge badge-soft-${data.status_badge_class} align-self-start">${data.status_display}</span>
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
                        ${data.open_defect_returns && data.open_defect_returns.length ? `
                            <div class="border rounded p-3">
                                <h6 class="mb-3">Open Defect Returns</h6>
                                ${data.open_defect_returns.map((defectReturn) => `
                                    <div class="border rounded p-3 mb-2">
                                        <h6 class="mb-1">${defectReturn.item_name}</h6>
                                        <p class="mb-1"><strong>Reason:</strong> ${defectReturn.reason_label}</p>
                                        <p class="mb-1"><strong>Requester Note:</strong> ${defectReturn.requester_note || 'None'}</p>
                                        <p class="mb-0"><strong>Status:</strong> ${defectReturn.status_display}</p>
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                        ${data.purchase_order ? `
                            <div class="border rounded p-3">
                                <h6 class="mb-3">Purchase Order Snapshot</h6>
                                <p class="mb-1"><strong>PO Number:</strong> ${data.purchase_order.po_number || 'Pending'}</p>
                                <p class="mb-1"><strong>Supplier:</strong> ${data.purchase_order.supplier_name || 'N/A'}</p>
                                <p class="mb-1"><strong>Terms:</strong> ${data.purchase_order.payment_terms || 'N/A'}</p>
                                <p class="mb-0"><strong>Order Date:</strong> ${data.purchase_order.order_date_display || 'N/A'}</p>
                            </div>
                        ` : ''}
                    </div>
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h6 class="mb-3">Workflow Timeline</h6>
                            ${buildTimeline(data.audit_trails)}
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

            if (data.permissions.can_process_returns) {
                footerButtons += '<button type="button" class="btn btn-warning" id="openProcessReturnModalBtn">Process Return</button>';
            }

            if (data.permissions.can_record_replacement_delivery) {
                footerButtons += '<button type="button" class="btn btn-danger" id="openReplacementDeliveryModalBtn">Replacement Delivery</button>';
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

        function renderDefectReturnItems(targetSelector) {
            const defectReturns = selectedRequestData.open_defect_returns || [];

            $(targetSelector).html(defectReturns.map((defectReturn) => `
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h6 class="mb-1">${defectReturn.item_name}</h6>
                            <p class="mb-1 text-muted">${defectReturn.reason_label}</p>
                            <small class="text-muted">${defectReturn.requester_note || 'No requester note provided.'}</small>
                        </div>
                        <span class="badge badge-soft-${defectReturn.status_badge_class} align-self-start">${defectReturn.status_display}</span>
                    </div>
                </div>
            `).join(''));
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
            const $submitButton = $('#financeReviewSubmitBtn');

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
            setButtonLoading($submitButton, financeReviewAction === 'approve' ? 'Approving...' : financeReviewAction === 'return' ? 'Returning...' : 'Rejecting...');

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
                resetButtonLoading($submitButton);
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
            const $submitButton = $('#purchaseOrderSubmitBtn');
            setButtonLoading($submitButton, 'Generating...');

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
                error: function (xhr) {
                    resetButtonLoading($submitButton);
                    showError(xhr, 'Purchase Order Failed');
                }
            });
        });

        $(document).on('click', '#openDeliveryModalBtn', function () {
            openFinanceActionModal('#deliveryModal');
        });

        $(document).on('click', '#openProcessReturnModalBtn', function () {
            renderDefectReturnItems('#processReturnItems');
            openFinanceActionModal('#processReturnModal');
        });

        $(document).on('click', '#openReplacementDeliveryModalBtn', function () {
            renderDefectReturnItems('#replacementDeliveryItems');
            openFinanceActionModal('#replacementDeliveryModal');
        });

        $('#deliveryForm').on('submit', function (event) {
            event.preventDefault();
            const $submitButton = $('#deliverySubmitBtn');
            setButtonLoading($submitButton, 'Saving...');
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
                error: function (xhr) {
                    resetButtonLoading($submitButton);
                    showError(xhr, 'Delivery Failed');
                }
            });
        });

        $(document).on('click', '#openPaymentModalBtn', function () {
            buildPaymentStatement(selectedRequestData);

            $('#paymentForm [name="invoice_reference"]').val(selectedRequestData.payment_summary?.invoice_reference || '');
            $('#paymentForm [name="invoice_date"]').val(selectedRequestData.payment_summary?.invoice_date || '{{ now()->toDateString() }}');
            $('#paymentForm [name="payment_reference"]').val(selectedRequestData.payment_summary?.payment_reference || '');
            $('#paymentForm [name="payment_note"]').val(selectedRequestData.payment_summary?.payment_note || '');
            $('#paymentForm [name="invoice_amount"]').val(
                (selectedRequestData.payment_summary?.invoice_amount || selectedRequestData.purchase_order?.total_amount || '')
                    .toString()
                    .replace(/,/g, '')
            );

            openFinanceActionModal('#paymentModal');
        });

        $('#paymentForm').on('submit', function (event) {
            event.preventDefault();
            const $submitButton = $('#paymentSubmitBtn');
            setButtonLoading($submitButton, 'Submitting...');
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
                error: function (xhr) {
                    resetButtonLoading($submitButton);
                    showError(xhr, 'Payment Failed');
                }
            });
        });

        $('#processReturnForm').on('submit', function (event) {
            event.preventDefault();
            const $submitButton = $('#processReturnSubmitBtn');
            setButtonLoading($submitButton, 'Submitting...');
            const formData = new FormData(this);

            $.ajax({
                url: `${@json($processReturnRouteBase)}/${selectedRequestId}/process-return`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'Accept': 'application/json' },
                success: function (response) {
                    financeActionSubmitting = true;
                    reopenFinanceDetailsFromActionModal = false;
                    $('#processReturnModal').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'Return Processing Started',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => window.location.reload());
                },
                error: function (xhr) {
                    resetButtonLoading($submitButton);
                    showError(xhr, 'Return Processing Failed');
                }
            });
        });

        $('#replacementDeliveryForm').on('submit', function (event) {
            event.preventDefault();
            const $submitButton = $('#replacementDeliverySubmitBtn');
            setButtonLoading($submitButton, 'Saving...');
            const formData = new FormData(this);

            $.ajax({
                url: `${@json($replacementDeliveryRouteBase)}/${selectedRequestId}/replacement-delivery`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'Accept': 'application/json' },
                success: function (response) {
                    financeActionSubmitting = true;
                    reopenFinanceDetailsFromActionModal = false;
                    $('#replacementDeliveryModal').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'Replacement Delivery Saved',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => window.location.reload());
                },
                error: function (xhr) {
                    resetButtonLoading($submitButton);
                    showError(xhr, 'Replacement Delivery Failed');
                }
            });
        });

        $('#purchaseOrderModal, #deliveryModal, #paymentModal, #processReturnModal, #replacementDeliveryModal').on('hidden.bs.modal', function () {
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
