<!DOCTYPE html>
<html>
<head>
    <title>Invoice</title>

    <style>
        body {
            font-family: Arial;
            font-size: 14px;
            margin: 20px;
        }

        .top-bar {
            text-align: right;
            margin-bottom: 10px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            cursor: pointer;
            margin-left: 5px;
        }

        .print-btn {
            background: #16a34a;
            color: #fff;
        }

        .box {
            border:1px solid #000;
            padding:20px;
        }

        table {
            width:100%;
        }

        td {
            vertical-align: top;
        }

        .right {
            text-align:right;
        }

        .signature {
            margin-top:50px;
        }

        hr {
            margin:15px 0;
        }
    </style>
</head>

<body>

<!-- PRINT BUTTON -->
<div class="top-bar">
    <button class="btn print-btn" onclick="window.print()">🖨 Print</button>
</div>

<!-- LOGO -->
<div style="text-align:center;">
    @if(!empty($logo))
        <img src="data:image/png;base64,{{ $logo }}" width="120">
    @endif
    <h2>Invoice</h2>
</div>

<div class="box">

    <h2 style="text-align:center;">DG ERP Invoice</h2>

    <hr>

    <table>
        <tr>
            <td>
                <strong>Company:</strong> {{ $company->company_name ?? 'N/A' }}<br>
                <strong>Email:</strong> {{ $company->email ?? 'N/A' }}<br>
            </td>

            <td class="right">
                <strong>Date:</strong> {{ $date ?? now()->format('d M Y') }}<br>
                <strong>Plan:</strong> {{ $plan->name ?? 'N/A' }}<br>
                <strong>Amount:</strong> Rs. {{ $amount ?? 0 }}<br>
                <strong>Expiry:</strong> {{ $expiry ?? 'N/A' }}<br>
            </td>
        </tr>
    </table>

    <hr>

    <div class="signature">
        @if(!empty($signature) && file_exists($signature))
            <img src="{{ $signature }}" width="120"><br>
        @endif

        <strong>Authorized Signature</strong>
    </div>

</div>

</body>
</html>