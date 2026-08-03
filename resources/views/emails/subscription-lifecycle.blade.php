<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #25324b; line-height: 1.6;">
    <div style="max-width: 600px; margin: 0 auto; padding: 24px;">
        <h1 style="font-size: 24px;">{{ $title }}</h1>
        <p>{{ $message }}</p>
        <p><strong>Studio:</strong> {{ $subscription->studio->studio_name }}</p>
        <p><strong>Relevant deadline:</strong> {{ $deadline->format('M d, Y g:i A') }}</p>
        <p><a href="{{ route('owner.subscription.index') }}">Manage subscription</a></p>
        <p style="color: #6c757d; font-size: 12px;">This is an automated message from {{ config('app.name') }}.</p>
    </div>
</body>
</html>
