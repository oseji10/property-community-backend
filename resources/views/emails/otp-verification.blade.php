<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Property Plus Africa Account</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; border-radius: 8px; margin: 20px 0; }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 8px;
            color: #2563eb;
            margin: 20px 0;
        }
        .footer { text-align: center; color: #6b7280; font-size: 14px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <img
                    src="https://backend.propertyplusafrica.com/property_images/dark-propertyafrica.jpg"
                    alt="Property Plus Africa"
                    width="140"
                >

        <div class="content">
            <h2>Hello {{ $firstName }} {{ $lastName }},</h2>

            <p>Welcome to Property Plus Africa! To complete your registration and verify your email address, please use the following verification code:</p>

            <div class="otp-code">
                {{ $otp }}
            </div>

            <p><strong>This code will expire in 10 minutes.</strong></p>

            <p>Enter this code in the verification window to activate your account and start using Property Plus Africa.</p>

            <p>If you didn't create an account with Property Plus Africa, please ignore this email.</p>
        </div>

        <tr>
            <td class="footer">
                <p>
                    <a href="https://propertyplusafrica.com">propertyplusafrica.com</a> ·
                    <a href="mailto:info@propertyplusafrica.com">info@propertyplusafrica.com</a>
                </p>
                <p>© {{ date('Y') }} Property Plus Africa</p>
            </td>
        </tr>
    </div>
</body>
</html>
