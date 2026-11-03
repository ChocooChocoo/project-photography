<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Studio Approved</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .studio-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #007BFF;
        }
        .info-item {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .info-item i {
            margin-right: 10px;
            color: #007BFF;
            width: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #28a745;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 12px;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Studio Registration Approved!</h1>
            <p>Your studio has been successfully verified by our admin team</p>
        </div>
        
        <div class="content">
            <p>Hello <strong>{{ $studio->user->first_name }} {{ $studio->user->last_name }}</strong>,</p>
            
            <p>We're excited to inform you that your studio registration has been <strong>approved</strong> and is now live on our platform!</p>
            
            <div class="studio-info">
                <h3 style="margin-top: 0;">Studio Details:</h3>
                <div class="info-item">
                    <i>🏢</i> <strong>Studio Name:</strong> {{ $studio->studio_name }}
                </div>
                <div class="info-item">
                    <i>📊</i> <strong>Status:</strong> Verified <span class="status-badge">ACTIVE</span>
                </div>
                <div class="info-item">
                    <i>📅</i> <strong>Approval Date:</strong> {{ now()->format('F d, Y') }}
                </div>
                @if($studio->starting_price)
                <div class="info-item">
                    <i>💰</i> <strong>Starting Price:</strong> ₱{{ number_format($studio->starting_price, 2) }}
                </div>
                @endif
            </div>
            
            <p>Your studio is now visible to clients and ready to accept bookings. You can:</p>
            <ul>
                <li>Manage your studio profile</li>
                <li>Add services and packages</li>
                <li>Receive booking requests</li>
                <li>View analytics and reports</li>
            </ul>
            
            <div style="text-align: center;">
                <a href="{{ url('/owner/dashboard') }}" class="btn">Go to Dashboard</a>
            </div>
            
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            
            <p>Best regards,<br>
            <strong>{{ config('app.name') }} Team</strong></p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>