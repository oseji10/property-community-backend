<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Message Received</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%);
            padding: 30px 40px;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px;
        }
        .message-box {
            background: #f8fafc;
            border-left: 4px solid #1a56db;
            padding: 20px 24px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .message-box p {
            margin: 0;
            color: #1e293b;
        }
        .message-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 20px 0;
            padding: 16px 20px;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 14px;
            color: #475569;
        }
        .message-meta .label {
            font-weight: 600;
            color: #1e293b;
        }
        .button {
            display: inline-block;
            padding: 12px 32px;
            background: #1a56db;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin-top: 20px;
            transition: background 0.3s ease;
        }
        .button:hover {
            background: #1e40af;
        }
        .footer {
            padding: 20px 40px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #64748b;
            text-align: center;
        }
        .footer a {
            color: #1a56db;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .property-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 16px 0;
        }
        .property-info .label {
            font-weight: 600;
            color: #1e293b;
        }
        .property-info .value {
            color: #0f172a;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 20px;
                border-radius: 8px;
            }
            .header {
                padding: 20px;
            }
            .content {
                padding: 20px;
            }
            .footer {
                padding: 15px 20px;
            }
            .message-meta {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📬 New Message Received</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <p>Hello <strong>{{ $recipient->firstName }} {{ $recipient->lastName }}</strong>,</p>

            <p>You have received a new message from <strong>{{ $sender->firstName }} {{ $sender->lastName }}</strong> regarding your property listing.</p>

            <!-- Message Content -->
            <div class="message-box">
                <p style="white-space: pre-wrap;">{{ $message->content }}</p>
            </div>

            <!-- Message Meta Information -->
            <div class="message-meta">
                <div>
                    <span class="label">From:</span>
                    <span>{{ $sender->firstName }} {{ $sender->lastName }}</span>
                    @if($sender->email)
                        <br><span style="font-size: 12px; color: #64748b;">{{ $sender->email }}</span>
                    @endif
                </div>
                <div>
                    <span class="label">Sent:</span>
                    <span>{{ $message->created_at->format('F j, Y \a\t g:i A') }}</span>
                </div>
            </div>

            <!-- Property Information (if available) -->
            @if($message->property)
                <div class="property-info">
                    <div><span class="label">Property:</span> <span class="value">{{ $message->property->propertyTitle }}</span></div>
                    @if($message->property->address)
                        <div><span class="label">Location:</span> <span class="value">{{ $message->property->address }}, {{ $message->property->city }}, {{ $message->property->state }}</span></div>
                    @endif
                </div>
            @endif

            <!-- Action Button -->
            <div style="text-align: center;">
                <a href="{{ url('/dashboard/messages') }}" class="button">
                    View Message
                </a>
            </div>

            <p style="margin-top: 24px; font-size: 14px; color: #64748b;">
                This message was sent to you from your Property Plus account. Please do not reply to this email.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                &copy; {{ date('Y') }} <a href="{{ url('/') }}">Property Plus Africa</a>.
                All rights reserved.
            </p>
            <p>
                <a href="{{ url('/settings/notifications') }}">Manage email preferences</a>
            </p>
        </div>
    </div>
</body>
</html>