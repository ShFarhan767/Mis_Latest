<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background-color: #fff; padding: 30px; border-radius: 10px; text-align: center;">
        <h2 style="color: #333;">Hello {{ $userName }},</h2>
        <p style="font-size: 16px; color: #555;">You requested a password reset. Use the OTP below to reset your password:</p>
        <h1 style="font-size: 40px; color: #007BFF; margin: 20px 0;">{{ $otp }}</h1>
        <p style="font-size: 14px; color: #999;">This OTP is valid for 10 minutes.</p>
        <p style="font-size: 12px; color: #aaa;">If you did not request this, please ignore this email.</p>
    </div>
</body>
</html>
