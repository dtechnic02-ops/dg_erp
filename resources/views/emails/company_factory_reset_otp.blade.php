<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Company Factory Reset Verification Code</title></head>
<body>
    <h1>Company Factory Reset</h1>
    <p>A Factory Reset was requested for <strong>{{ $companyName }}</strong> by {{ $requestedByName }}.</p>
    <p>Verification Code:</p>
    <p style="font-size: 24px; font-weight: bold; letter-spacing: 4px;">{{ $otp }}</p>
    <p>This code expires in {{ $expiresInMinutes }} minutes.</p>
    <p>Factory Reset preserves the Company, Company Admins, subscription and profile, but permanently removes Staff and operational business data.</p>
    <p>If this request was not expected, do not share this code.</p>
</body>
</html>
