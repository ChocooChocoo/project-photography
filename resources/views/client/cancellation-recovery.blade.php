@extends('layouts.client.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Photographer replacement</h1>
    <p>A replacement photographer is available for booking {{ $recovery->booking->booking_reference }}.</p>
    <p class="text-muted">Please respond by {{ $recovery->deadline ? $recovery->deadline->format('M d, Y h:i A') : 'Not set' }}.</p>
    @if ($recovery->status === 'awaiting_client')
        <form method="post" action="{{ route('client.booking.recovery.response', $recovery->id) }}" class="d-inline recovery-response-form">
            @csrf
            <input type="hidden" name="accepted" value="1">
            <button class="btn btn-primary">Accept replacement</button>
        </form>
        <form method="post" action="{{ route('client.booking.recovery.response', $recovery->id) }}" class="d-inline recovery-response-form">
            @csrf
            <input type="hidden" name="accepted" value="0">
            <button class="btn btn-outline-danger">Decline and request full refund</button>
        </form>
    @else
        <p>Status: <strong>{{ str_replace('_', ' ', $recovery->status) }}</strong></p>
    @endif
</div>
@endsection

@section('scripts')
<script>
$(function () {
    $('.recovery-response-form').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const accepted = $form.find('input[name="accepted"]').val() === '1';
        const $btn = $form.find('button[type="submit"]');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            beforeSend: function () {
                $btn.prop('disabled', true);
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we record your response',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            },
            success: function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Replacement accepted',
                    text: 'Your booking will continue with the replacement photographer.',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => location.reload());
            },
            error: function (xhr) {
                const message = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.';
                // A decline returns 422 by design; the refund is queued regardless.
                Swal.fire({
                    icon: accepted ? 'error' : 'success',
                    title: accepted ? 'Response failed' : 'Refund queued',
                    text: message,
                    confirmButtonColor: '#3475db'
                }).then(() => location.reload());
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endsection
