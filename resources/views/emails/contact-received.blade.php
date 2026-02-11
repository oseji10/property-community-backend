<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Message Received</title>
    <style>
        /* Basic reset and email-safe styles */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .header {
            background: #f8f9fa;
            padding: 30px 40px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        .header img {
            max-width: 160px;
            height: auto;
        }
        .content {
            padding: 40px;
        }
        h1 {
            font-size: 24px;
            margin: 0 0 20px;
            color: #1a1a1a;
        }
        .panel {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .panel strong {
            color: #111;
        }
        .button {
            display: inline-block;
            background: #2563eb;
            color: white !important;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 20px 0;
        }
        .button:hover {
            background: #1d4ed8;
        }
        .footer {
            text-align: center;
            font-size: 14px;
            color: #666;
            padding: 30px 40px;
            border-top: 1px solid #eee;
            background: #f8f9fa;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 24px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <img 
            src="https://backend.propertyplusafrica.com/images/logo.png" 
            alt="Property Plus Africa" 
            width="140"
            style="max-width: 160px; height: auto;"
        >
    </div>

    <div class="content">
        <h1>New Contact Message Received</h1>

        <p>A new inquiry has been submitted through the <strong>Property Plus Africa</strong> website.</p>

        <div class="panel">
            <strong>Sender Details</strong><br><br>
            
            <strong>Name:</strong> {{ $fullname }}<br>
            <strong>Email:</strong> {{ $email }}<br>
            <strong>Phone:</strong> {{ $mobile }}
        </div>

        <div class="panel">
            <strong>Message</strong><br><br>
            {!! nl2br(e($message)) !!}
        </div>

        <a href="mailto:{{ $email }}" class="button">
            Reply to {{ $fullname }}
        </a>

        <p style="margin-top: 32px;">
            Thank you for keeping the team informed.<br>
            This message was received at {{ now()->format('M d, Y \a\t H:i') }} WAT.
        </p>
    </div>

    <div class="footer">
        Best regards,<br>
        <strong>Property Plus Africa</strong> Team<br>
        <a href="mailto:support@propertyplusafrica.com">support@propertyplusafrica.com</a><br>
        <a href="https://www.propertyplusafrica.com">www.propertyplusafrica.com</a>
    </div>
</div>

</body>
</html>