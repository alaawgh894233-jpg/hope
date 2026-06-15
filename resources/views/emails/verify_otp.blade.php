<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>OTP Verification</title>
</head>
<body style="font-family: Arial; background:#f4f4f4; padding:20px;">

<div style="max-width:600px;margin:auto;background:#fff;padding:30px;border-radius:10px">

    <h2>Hello {{ $name }} 👋</h2>
    <p>Welcome to our app ☺</p>
    <p>Your OTP code is:</p>

    <div style="font-size:30px;letter-spacing:5px;font-weight:bold;text-align:center;margin:20px 0;">
        {{ $otp }}
    </div>

    <p>This code will expire in <b>{{ $ttl }} minutes</b>.</p>

    <hr>

    <p style="color:#888;font-size:12px;">
        If you didn't request this, ignore this email.
    </p>

</div>

</body>
</html>
