@extends('layouts.admin.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Booking refund queue</h1>
    <div id="refund-queue" class="text-muted">Loading queued refunds…</div>
</div>
<script>
fetch('{{ route('admin.booking-refunds.index') }}', {headers: {'Accept': 'application/json'}})
    .then(response => response.json())
    .then(payload => {
        const queue = document.getElementById('refund-queue');
        if (!payload.recoveries.length) { queue.textContent = 'No refunds awaiting evidence.'; return; }
        queue.innerHTML = payload.recoveries.map(item => `
            <form method="post" action="{{ url('/admin/booking-refunds') }}/${item.id}/complete" class="card p-3 mb-2">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <strong>${item.booking.booking_reference}</strong>
                <div class="small text-muted">Client: ${item.booking.client?.first_name ?? ''} ${item.booking.client?.last_name ?? ''}</div>
                <div class="small text-muted mb-2">Reason: ${item.booking.cancellation_reason ?? item.outcome_reason ?? 'Not provided'}</div>
                ${item.booking.payments.filter(payment => payment.status === 'succeeded').map(payment => `<input name="provider_refund_references[${payment.id}]" required class="form-control my-2" placeholder="Provider refund reference for payment ${payment.payment_reference}">`).join('')}
                <textarea name="notes" class="form-control mb-2" placeholder="Processor fee or evidence notes"></textarea>
                <button class="btn btn-primary">Record full refund</button>
            </form>`).join('');
    });
</script>
@endsection
