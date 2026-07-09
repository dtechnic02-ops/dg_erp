<!DOCTYPE html>
<html>
<head>

    <title>
        Products PDF
    </title>

    <style>

        body{
            font-family: DejaVu Sans;
            font-size:12px;
        }

        h2{
            text-align:center;
            margin-bottom:20px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        table th,
        table td{
            border:1px solid #000;
            padding:8px;
            text-align:left;
        }

        table th{
            background:#ddd;
        }

        .stock-low{
            color:#b45309;
            font-weight:bold;
        }

        .stock-out{
            color:#dc2626;
            font-weight:bold;
        }

        .stock-ok{
            color:#15803d;
            font-weight:bold;
        }

    </style>

</head>

<body>

<h2>

    Product List

</h2>

<table>

    <tr>

        <th>#</th>

        <th>Name</th>

        <th>Barcode</th>

        <th>Cost</th>

        <th>Retail</th>

        <th>Wholesale</th>

        <th>Stock</th>

        <th>Status</th>

    </tr>

    @foreach($products as $p)

    <tr>

        <!-- ID -->

        <td>

            {{ $p->id }}

        </td>

        <!-- NAME -->

        <td>

            {{ $p->name }}

        </td>

        <!-- BARCODE -->

        <td>

            {{ $p->barcode }}

        </td>

        <!-- COST -->

        <td>

            {{ number_format(
                $p->cost_price,
                2
            ) }}

        </td>

        <!-- RETAIL -->

        <td>

            {{ number_format(
                $p->retail_price,
                2
            ) }}

        </td>

        <!-- WHOLESALE -->

        <td>

            {{ number_format(
                $p->wholesale_price,
                2
            ) }}

        </td>

        <!-- STOCK -->

        <td>

            @if($p->current_stock <= 0)

                <span class="stock-out">

                    Out

                </span>

            @elseif(
                $p->stock_alert &&
                $p->current_stock <= $p->stock_alert
            )

                <span class="stock-low">

                    Low:
                    {{ $p->current_stock }}

                </span>

            @else

                <span class="stock-ok">

                    {{ $p->current_stock }}

                </span>

            @endif

        </td>

        <!-- STATUS -->

        <td>

            {{ ucfirst($p->status) }}

        </td>

    </tr>

    @endforeach

</table>

</body>
</html>