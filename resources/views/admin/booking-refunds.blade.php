@extends('layouts.admin.app')
@section('title', 'Booking Refund Queue')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h4 class="card-title mb-0">Booking Refund Queue</h4>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge badge-soft-warning" id="refundQueueCount">0 pending</span>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="refreshRefundQueue">
                                    <i class="ti ti-refresh me-1"></i>Refresh
                                </button>
                            </div>
                        </div>
                        <div class="card-body" id="refund-queue">
                            <div class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span>Loading refund queue...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function () {
            const completeUrlTemplate = '{{ route('admin.booking-refunds.complete', ['recoveryId' => '__ID__']) }}';

            function esc(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
            }

            function money(value) {
                const amount = parseFloat(value) || 0;
                return 'PHP ' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function eventDate(value) {
                if (!value) return 'N/A';
                const date = new Date(String(value).slice(0, 10) + 'T00:00:00');
                return isNaN(date) ? String(value).slice(0, 10) : date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            }

            function paymentStatusBadge(status) {
                const badges = {
                    'refund_pending': 'badge-soft-warning',
                    'refunded': 'badge-soft-success',
                    'paid': 'badge-soft-success',
                    'partially_paid': 'badge-soft-primary',
                    'pending': 'badge-soft-secondary',
                    'failed': 'badge-soft-danger',
                };
                return '<span class="badge ' + (badges[status] || 'badge-soft-secondary') + ' fs-8 px-1 text-uppercase">' + esc(status.replaceAll('_', ' ')) + '</span>';
            }

            function renderRecovery(item) {
                const booking = item.booking || {};
                const client = booking.client || {};
                const payments = (booking.payments || []).filter(payment => payment.status === 'succeeded');

                const paidTotal = payments.reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
                const ownerTarget = item.refund_amount == null ? null : Number(item.refund_amount);
                const target = ownerTarget ?? paidTotal;
                const percentage = item.refund_percentage == null ? 100 : Number(item.refund_percentage);
                const paymentInputs = payments.map(payment => `
                    <div class="mb-3">
                        <label class="form-label mb-1">Provider refund reference — ${esc(payment.payment_reference)} (${money(payment.amount)})</label>
                        <input name="provider_refund_references[${payment.id}]" required
                            class="form-control" placeholder="Enter the provider refund reference for ${esc(payment.payment_reference)}">
                    </div>
                `).join('');

                const reason = booking.cancellation_reason || item.outcome_reason || 'Not provided';

                return `
                    <form method="post" action="${completeUrlTemplate.replace('__ID__', item.id)}" class="refund-form border rounded p-3 mb-3">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <strong class="fs-md">${esc(booking.booking_reference)}</strong>
                                <div class="small text-muted">Client: ${esc(client.first_name)} ${esc(client.last_name)}</div>
                                <div class="small text-muted">Event: ${eventDate(booking.event_date)} · Total: ${money(booking.total_amount)}</div>
                            </div>
                            ${paymentStatusBadge(booking.payment_status)}
                        </div>
                        <div class="small text-muted mb-2"><i class="ti ti-info-circle me-1"></i>Reason: ${esc(reason)}</div>
                        <div class="alert alert-info py-2 mb-3">
                            ${ownerTarget == null ? 'Refund target' : 'Owner refund target'}: <strong>${money(target)}</strong>
                            (${percentage.toFixed(2)}% of succeeded payments). You may override this amount at or below the total paid (${money(paidTotal)}).
                        </div>
                        <div class="mb-3">
                            <label class="form-label mb-1" for="refund_amount_${item.id}">Refund amount to record</label>
                            <input type="number" step="0.01" min="0" max="${paidTotal}" id="refund_amount_${item.id}"
                                name="refund_amount" class="form-control" value="${target.toFixed(2)}">
                        </div>
                        ${paymentInputs || '<div class="text-danger small mb-3">No succeeded payments found for this booking.</div>'}
                        <div class="mb-3">
                            <label class="form-label mb-1">Processor fee or evidence notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes about the refund"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ti ti-receipt-refund me-1"></i>Record target refund
                        </button>
                    </form>
                `;
            }

            function loadRefundQueue() {
                $('#refund-queue').html(`
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Loading refund queue...</span>
                    </div>
                `);

                $.getJSON('{{ route('admin.booking-refunds.index') }}', function (payload) {
                    const recoveries = payload.recoveries || [];
                    $('#refundQueueCount').text(recoveries.length + ' pending');

                    if (!recoveries.length) {
                        $('#refund-queue').html(`
                            <div class="text-center py-4 text-muted">
                                <i class="ti ti-receipt-off fs-1 mb-2"></i>
                                <p class="mb-0">No refunds awaiting evidence.</p>
                            </div>
                        `);
                        return;
                    }

                    $('#refund-queue').html(recoveries.map(renderRecovery).join(''));
                }).fail(function () {
                    $('#refund-queue').html(`
                        <div class="text-center py-4 text-danger">
                            <p class="mb-0">Failed to load the refund queue. Please try again.</p>
                        </div>
                    `);
                });
            }

            $(document).on('submit', '.refund-form', function (e) {
                e.preventDefault();
                const $form = $(this);

                Swal.fire({
                    title: 'Recording refund...',
                    text: 'Please wait while the refund evidence is recorded',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    success: function () {
                        Swal.fire({
                            icon: 'success',
                            title: 'Refund recorded',
                            text: 'Refund evidence recorded.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => loadRefundQueue());
                    },
                    error: function (xhr) {
                        const message = (xhr.responseJSON && xhr.responseJSON.message) || 'Refund evidence is invalid or already used.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Refund failed',
                            text: message,
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            });

            $('#refreshRefundQueue').on('click', loadRefundQueue);
            loadRefundQueue();
        });
    </script>
@endsection
