<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment received</title>
</head>
<body style="font-family: Arial, sans-serif; color: #25324b; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px;">
        <h1 style="font-size: 24px;">Payment received</h1>
        <p>We received your payment for booking {{ $booking->booking_reference }}.</p>
        <table style="border-collapse: collapse; width: 100%;">
            <tr><td style="padding: 8px 0;">Receipt</td><td style="text-align: right;">{{ $payment->payment_reference }}</td></tr>
            <tr><td style="padding: 8px 0;">Amount paid</td><td style="text-align: right;">&#8369;{{ number_format($payment->amount, 2) }}</td></tr>
            <tr><td style="padding: 8px 0;">Booking total</td><td style="text-align: right;">&#8369;{{ number_format($booking->total_amount, 2) }}</td></tr>
            <tr><td style="padding: 8px 0;">Remaining balance</td><td style="text-align: right;">&#8369;{{ number_format($booking->remaining_balance, 2) }}</td></tr>
        </table>
        <p><a href="{{ route('client.my-bookings.index') }}">Open your bookings</a></p>
        <p style="color: #6c757d; font-size: 12px;">This is an automated message from {{ config('app.name') }}.</p>
    </div>
</body>
</html>
