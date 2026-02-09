<!DOCTYPE html>
<html>
<head>
    <title>Your Property Plus Africa Verification Code</title>
</head>
<body>
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <img
                    src="https://backend.propertyplusafrica.com/property_images/dark-propertyafrica.svg"
                    alt="Property Plus Africa"
                    width="140"
                >

        <h2 style="color: #FF8C00;">Property Africa Plus - Email Verification</h2>

        <p>Hello,</p>

        <p>Thank you for signing up with Property Africa Plus! Use the verification code below to complete your registration:</p>

        <div style="background-color: #f3f4f6; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;">
            <h1 style="color: #2563eb; font-size: 32px; letter-spacing: 8px; margin: 0;">{{ $otp }}</h1>
        </div>

        <p><strong>This code will expire in {{ $expires_in }}.</strong></p>

        <p>If you didn't request this code, please ignore this email.</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">

        <p style="color: #6b7280; font-size: 14px;">
            Best regards,<br>
            The Property Plus Africa Team
        </p>
    </div>
      <tr>
            <td class="footer">
                <p>
                    <a href="https://propertycommunity.com">propertycommunity.com</a> ·
                    <a href="mailto:info@propertycommunity.com">info@propertycommunity.com</a>
                </p>
                <p>© {{ date('Y') }} Property Africa Plus</p>
            </td>
        </tr>
</body>
</html>
