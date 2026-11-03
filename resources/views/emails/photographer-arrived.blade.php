<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Photographer arrived</title>
</head>
<body style="font-family: Arial, sans-serif; color: #25324b; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px;">
        <h1 style="font-size: 24px;">Your photographer has arrived</h1>
        <p>{{ $photographerName }} checked in on site for booking {{ $booking->booking_reference }} at {{ now()->format('g:i A') }}.</p>
        @if(! is_null($distanceMeters))
            <p>Their phone placed them about {{ number_format($distanceMeters) }} m from the {{ $booking->location_type === 'on-location' ? 'venue pin' : 'studio' }}.</p>
        @endif
        <p><a href="{{ $actionUrl }}">Open the booking</a></p>
        <p style="color: #6c757d; font-size: 12px;">This is an automated message from {{ config('app.name') }}.</p>
    </div>
</body>
</html>
