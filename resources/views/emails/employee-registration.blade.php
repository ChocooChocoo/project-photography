<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Account Created</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            border: 1px solid #e0e0e0;
        }
        .credentials {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .credentials p {
            margin: 10px 0;
            font-size: 16px;
        }
        .credentials strong {
            color: #667eea;
            min-width: 100px;
            display: inline-block;
        }
        .role-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        .warning {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 5px 0;
            color: #e65100;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            margin: 20px 0;
            font-weight: 600;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to {{ config('app.name') }}!</h1>
        <p>Your employee account has been created</p>
    </div>
    
    <div class="content">
        <div class="role-badge">
            {{ $employeeData['role_display'] ?? 'Employee' }}
            @if(isset($employeeData['role_type']))
                - {{ $employeeData['role_type'] }}
            @endif
        </div>
        
        <h2>Hello, {{ $employeeData['first_name'] }} {{ $employeeData['last_name'] }}!</h2>
        
        <p>You have been registered as an employee for <strong>{{ $employeeData['studio_name'] }}</strong>. Below are your login credentials:</p>
        
        <div class="credentials">
            <p><strong>Email:</strong> {{ $employeeData['email'] }}</p>
            <p><strong>Temporary Password:</strong> <code style="background: #f0f0f0; padding: 4px 8px; border-radius: 4px;">{{ $temporaryPassword }}</code></p>
            <p><strong>Role:</strong> {{ $employeeData['role_display'] ?? $employeeData['role'] }}</p>
            @if(isset($employeeData['position']))
                <p><strong>Position:</strong> {{ $employeeData['position'] }}</p>
            @endif
        </div>
        
        <div class="warning">
            <p><strong>⚠️ Important Security Notice:</strong></p>
            <p>For security reasons, please change your password immediately after logging in. Do not share your credentials with anyone.</p>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ route('login') }}" class="button">Login to Your Account</a>
        </div>
        
        <p><strong>Your Account Details:</strong></p>
        <ul style="list-style: none; padding: 0;">
            <li>✓ Access to studio: <strong>{{ $employeeData['studio_name'] }}</strong></li>
            @if(isset($employeeData['schedule']))
                <li>✓ Work Schedule: <strong>{{ $employeeData['schedule'] }}</strong></li>
            @endif
            <li>✓ Account Status: <strong>{{ ucfirst($employeeData['status']) }}</strong></li>
        </ul>
        
        <p>If you have any questions or need assistance, please contact your studio administrator.</p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>This is an automated message, please do not reply to this email.</p>
    </div>
</body>
</html>