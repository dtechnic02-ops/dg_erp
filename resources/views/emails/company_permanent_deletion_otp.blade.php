<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Company Deletion Verification Code</title></head>
<body>
    <h1>Permanent Company Deletion</h1>
    <p>A permanent deletion request was initiated for:</p>
    <p><strong>{{ $companyName }}</strong> (Company ID: {{ $companyId }})</p>
    <p>Requested by: {{ $requestedByName }}</p>
    <p>Verification Code:</p>
    <p style="font-size: 24px; font-weight: bold; letter-spacing: 4px;">{{ $otp }}</p>
    <p>This code expires in {{ $expiresInMinutes }} minutes.</p>
    <p>This operation permanently removes the Company, its users, business data, and Company-owned files.</p>
    <p>If this request was not expected, do not share this code.</p>
</body>
</html>
