<!DOCTYPE html>

<html>

<head>


<meta charset="utf-8">

<title>
    Expense Report
</title>

<link
    rel="stylesheet"
    href="{{ asset('assets/company/css/print.css') }}"
>


</head>

<body>

@include(
'company.partials.print-header-portrait'
)

<div class="report-container">

<div class="text-center mb-3">

    <h3>
        Expense Report
    </h3>

</div>

<table class="table-report">

    <thead>

        <tr>

            <th width="50">
                SN
            </th>

            <th>
                Voucher No
            </th>

            <th width="120">
                Date
            </th>

            <th>
                Category
            </th>

            <th>
                Account
            </th>

            <th width="120" class="text-end">
                Amount
            </th>

        </tr>

    </thead>

    <tbody>

        @php

        $grandTotal = 0;

        @endphp

        @forelse($expenses as $expense)

        @php

        $grandTotal += $expense->amount;

        @endphp

        <tr>

            <td>
                {{ $loop->iteration }}
            </td>

            <td>
                {{ $expense->expense_no }}
            </td>

            <td>
                {{ $expense->expense_date }}
            </td>

            <td>
                {{ $expense->category->name ?? '' }}
            </td>

            <td>
                {{ $expense->account->account_name ?? '' }}
            </td>

            <td class="text-end">
                {{ number_format($expense->amount,2) }}
            </td>

        </tr>

        @empty

        <tr>

            <td
                colspan="6"
                class="text-center"
            >
                No Expense Found
            </td>

        </tr>

        @endforelse

    </tbody>

    <tfoot>

        <tr>

            <th
                colspan="5"
                class="text-end"
            >
                Grand Total
            </th>

            <th class="text-end">
                {{ number_format($grandTotal,2) }}
            </th>

        </tr>

    </tfoot>

</table>


</div>

@include(
'company.partials.print-footer-portrait'
)

<script>

window.onload = function(){

    window.print();

};

</script>

</body>

</html>
