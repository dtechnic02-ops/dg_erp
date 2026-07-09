<!DOCTYPE html>
<html>
<head>

    <title>
        Stock Ledger PDF
    </title>

    <style>

        body{
            font-family: DejaVu Sans;
            font-size:12px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        table th,
        table td{
            border:1px solid #000;
            padding:8px;
        }

        table th{
            background:#ddd;
        }

    </style>

</head>

<body>

<h2 align="center">

    Stock Ledger

</h2>

<table>

    <tr>

        <th>Date</th>
        <th>Product</th>
        <th>Type</th>
        <th>Reference</th>
        <th>Qty</th>
        <th>Before</th>
        <th>After</th>

    </tr>

    @foreach($movements as $move)

    <tr>

        <td>

            {{ $move->created_at }}

        </td>

        <td>

            {{ $move->product->name ?? '' }}

        </td>

        <td>

            {{ ucfirst($move->type) }}

        </td>

        <td>

            {{ $move->reference_no }}

        </td>

        <td>

            {{ $move->quantity }}

        </td>

        <td>

            {{ $move->before_stock }}

        </td>

        <td>

            {{ $move->after_stock }}

        </td>

    </tr>

    @endforeach

</table>

</body>
</html>