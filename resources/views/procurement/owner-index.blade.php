<div class="content-page">
    <div class="container-fluid">
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <div>
                        <h4 class="mb-1">Procurement Oversight</h4>
                        <p class="text-muted mb-0">Review owner approvals and monitor procurement progress across owned studios.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control" id="ownerProcurementSearch" placeholder="Search reference, requester, or studio">
                        <select class="form-select" id="ownerProcurementStatusFilter">
                            <option value="">All Statuses</option>
                            <option value="pending owner approval">Pending Owner Approval</option>
                            <option value="approved">Approved</option>
                            <option value="ordered">Ordered</option>
                            <option value="completed">Completed</option>
                        </select>
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
                        <h5 class="card-title mb-0">Owner Procurement Queue</h5>
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
                                <tbody id="ownerProcurementTable">
                                    @forelse ($procurementRequests as $procurementRequest)
                                        <tr
                                            data-search="{{ strtolower($procurementRequest->request_reference . ' ' . ($procurementRequest->requester->full_name ?? '') . ' ' . ($procurementRequest->studio->studio_name ?? '')) }}"
                                            data-status="{{ strtolower(str_replace('_', ' ', $procurementRequest->status)) }}"
                                        >
                                            <td>
                                                <div class="fw-semibold">{{ $procurementRequest->request_reference }}</div>
                                                <small class="text-muted">{{ $procurementRequest->required_date?->format('M d, Y') ?? 'No date' }}</small>
                                            </td>
                                            <td>{{ $procurementRequest->requester->full_name ?? 'N/A' }}</td>
                                            <td>{{ $procurementRequest->studio->studio_name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-soft-{{ $procurementRequest->status_badge_class }}">{{ $procurementRequest->status_label }}</span>
                                                @if ($procurementRequest->is_high_value)
                                                    <span class="badge badge-soft-warning ms-1">High Value</span>
                                                @endif
                                            </td>
                                            <td>PHP {{ number_format((float) $procurementRequest->estimated_total, 2) }}</td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <button type="button" class="dropdown-item owner-view" data-id="{{ $procurementRequest->id }}">
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

<div class="modal fade" id="ownerDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Procurement Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ownerDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer" id="ownerDetailsFooter">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ownerReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ownerReviewModalTitle">Owner Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ownerReviewForm">
                    @csrf
                    <input type="hidden" id="ownerReviewAction" name="action">
                    <div class="mb-0">
                        <label for="ownerReviewNote" class="form-label">Note</label>
                        <textarea class="form-control" id="ownerReviewNote" name="note" rows="4" placeholder="Enter your review note"></textarea>
                        <small class="text-muted" id="ownerReviewNoteHelp">A note is optional for approval and required for return or rejection.</small>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="ownerReviewSubmitBtn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        let selectedRequestId = null;
        let ownerReviewAction = null;
        let reopenOwnerDetailsModal = false;
        let ownerReviewSubmitting = false;

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

        function buildDocuments(documents) {
            if (!documents || Object.keys(documents).length === 0) {
                return '<p class="text-muted mb-0">No documents uploaded yet.</p>';
            }

            let html = '';
            Object.entries(documents).forEach(([type, files]) => {
                html += `<div class="mb-3"><h6 class="text-uppercase small text-muted">${type.replaceAll('_', ' ')}</h6><ul class="mb-0 ps-3">`;
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

        function renderDetails(data) {
            const itemsHtml = data.items.map((item) => `
                <tr>
                    <td>${item.item_name}</td>
                    <td>${item.quantity} ${item.unit_of_measure}</td>
                    <td>${item.category}</td>
                    <td>${item.expense_type.toUpperCase()}</td>
                    <td>PHP ${Number(item.estimated_total_cost).toFixed(2)}</td>
                </tr>
            `).join('');

            $('#ownerDetailsContent').html(`
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
                                            <th>Expense Type</th>
                                            <th>Estimated</th>
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
                            <p class="mb-1"><strong>Requester Role:</strong> ${data.requester_role || 'N/A'}</p>
                            <p class="mb-1"><strong>Estimated Total:</strong> PHP ${data.estimated_total}</p>
                            <p class="mb-1"><strong>Approved Total:</strong> PHP ${data.approved_total}</p>
                            <p class="mb-1"><strong>Finance Note:</strong> ${data.finance_review_note || 'None'}</p>
                            <p class="mb-0"><strong>Owner Note:</strong> ${data.owner_review_note || 'None'}</p>
                        </div>
                        <div class="border rounded p-3 mb-3">
                            <h6 class="mb-3">Documents</h6>
                            ${buildDocuments(data.documents)}
                        </div>
                        <div class="border rounded p-3">
                            <h6 class="mb-3">Timeline</h6>
                            ${buildTimeline(data.audit_trails)}
                        </div>
                    </div>
                </div>
            `);

            let footerButtons = '<button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>';

            if (data.permissions.can_owner_review) {
                footerButtons += '<button type="button" class="btn btn-success owner-action" data-action="approve">Approve</button>';
                footerButtons += '<button type="button" class="btn btn-warning owner-action" data-action="return">Return</button>';
                footerButtons += '<button type="button" class="btn btn-danger owner-action" data-action="reject">Reject</button>';
            }

            $('#ownerDetailsFooter').html(footerButtons);
        }

        function loadDetails(id) {
            $('#ownerDetailsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');

            $.get(`${@json($showRouteBase)}/${id}`, function (response) {
                selectedRequestId = id;
                renderDetails(response.data);
                $('#ownerDetailsModal').modal('show');
            }).fail(function (xhr) {
                showError(xhr, 'Unable to Load Details');
            });
        }

        function filterRows() {
            const keyword = ($('#ownerProcurementSearch').val() || '').toLowerCase().trim();
            const status = ($('#ownerProcurementStatusFilter').val() || '').toLowerCase().trim();

            $('#ownerProcurementTable tr').each(function () {
                const searchValue = ($(this).data('search') || '').toString();
                const statusValue = ($(this).data('status') || '').toString();
                const matchesKeyword = searchValue === '' || searchValue.includes(keyword);
                const matchesStatus = status === '' || statusValue === status;

                $(this).toggle(matchesKeyword && matchesStatus);
            });
        }

        $('#ownerProcurementSearch').on('input', filterRows);
        $('#ownerProcurementStatusFilter').on('change', filterRows);

        $('.owner-view').on('click', function () {
            loadDetails($(this).data('id'));
        });

        $(document).on('click', '.owner-action', function () {
            ownerReviewAction = $(this).data('action');
            const requiresNote = ownerReviewAction !== 'approve';

            $('#ownerReviewAction').val(ownerReviewAction);
            $('#ownerReviewNote').val('');
            $('#ownerReviewModalTitle').text(`Owner ${ownerReviewAction.charAt(0).toUpperCase()}${ownerReviewAction.slice(1)}`);
            $('#ownerReviewNote').attr('placeholder', requiresNote ? 'Enter the reason for this action' : 'Optional approval note');
            $('#ownerReviewNoteHelp').text(requiresNote ? 'A note is required for this action.' : 'A note is optional for approval.');
            $('#ownerReviewSubmitBtn').text(ownerReviewAction === 'approve' ? 'Approve' : ownerReviewAction === 'return' ? 'Return' : 'Reject');
            reopenOwnerDetailsModal = true;
            ownerReviewSubmitting = false;
            $('#ownerDetailsModal').modal('hide');
        });

        $('#ownerReviewForm').on('submit', function (event) {
            event.preventDefault();
            const $submitButton = $('#ownerReviewSubmitBtn');

            const note = ($('#ownerReviewNote').val() || '').trim();
            const requiresNote = ownerReviewAction !== 'approve';

            if (requiresNote && !note) {
                showError({
                    responseJSON: {
                        message: 'A note is required for this action.'
                    }
                }, 'Owner Action Failed');
                return;
            }

            ownerReviewSubmitting = true;
            reopenOwnerDetailsModal = false;
            setButtonLoading($submitButton, ownerReviewAction === 'approve' ? 'Approving...' : ownerReviewAction === 'return' ? 'Returning...' : 'Rejecting...');

            $.post(`${@json($processRouteBase)}/${selectedRequestId}/process`, {
                _token: '{{ csrf_token() }}',
                action: ownerReviewAction,
                note: note
            }).done(function (response) {
                $('#ownerReviewModal').modal('hide');

                Swal.fire({
                    icon: 'success',
                    title: 'Owner Action Saved',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => window.location.reload());
            }).fail(function (xhr) {
                ownerReviewSubmitting = false;
                reopenOwnerDetailsModal = true;
                resetButtonLoading($submitButton);
                showError(xhr, 'Owner Action Failed');
            });
        });

        $('#ownerDetailsModal').on('hidden.bs.modal', function () {
            if (reopenOwnerDetailsModal) {
                $('#ownerReviewModal').modal('show');
            }
        });

        $('#ownerReviewModal').on('hidden.bs.modal', function () {
            if (reopenOwnerDetailsModal && !ownerReviewSubmitting) {
                reopenOwnerDetailsModal = false;
                $('#ownerDetailsModal').modal('show');
                return;
            }

            reopenOwnerDetailsModal = false;
            ownerReviewSubmitting = false;
        });
    });
</script>
