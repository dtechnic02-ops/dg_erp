<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <title>Salary Sheet Report</title>

    <style>

        body{
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header{
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
            padding:6px;
            text-align:left;
        }

        table th{
            background:#f2f2f2;
        }

        .text-right{
            text-align:right;
        }

        .text-center{
            text-align:center;
        }

        .footer{
            margin-top:20px;
        }

        @media print{
            .no-print{
                display:none;
            }
        }

    </style>

</head>
<body>

<div class="header">

    <h2>
        Salary Sheet Report
    </h2>

    <p>

        @if(request('salary_month'))
            Month :
            {{ request('salary_month') }}
            <br>
        @endif

        @if(request('status'))
            Status :
            {{ ucfirst(request('status')) }}
        @endif

    </p>

</div>

<div class="no-print">

    <button onclick="window.print()">
        Print
    </button>

</div>

<br>

<table>

    <thead>

        <tr>

            <th>#</th>

            <th>Employee Code</th>

            <th>Employee Name</th>

            <th>Month</th>

            <th>Basic Salary</th>

            <th>Present</th>

            <th>Absent</th>

            <th>Net Salary</th>

            <th>Status</th>

        </tr>

    </thead>

    <tbody>

        @php

            $totalSalary = 0;

        @endphp

        @forelse($salarySheets as $salary)

            @php

                $totalSalary +=
                $salary->net_salary;

            @endphp

            <tr>

                <td>
                    {{ $loop->iteration }}
                </td>

                <td>
                    {{ $salary->employee->employee_code }}
                </td>

                <td>
                    {{ $salary->employee->first_name }}
                    {{ $salary->employee->last_name }}
                </td>

                <td>
                    {{ $salary->salary_month }}
                </td>

                <td class="text-right">
                    {{ number_format($salary->basic_salary,2) }}
                </td>

                <td class="text-center">
                    {{ $salary->present_days }}
                </td>

                <td class="text-center">
                    {{ $salary->absent_days }}
                </td>

                <td class="text-right">
                    {{ number_format($salary->net_salary,2) }}
                </td>

                <td class="text-center">
                    {{ ucfirst($salary->status) }}
                </td>

            </tr>

        @empty

            <tr>

                <td colspan="9" class="text-center">

                    No Data Found

                </td>

            </tr>

        @endforelse

    </tbody>

    <tfoot>

        <tr>

            <th colspan="7" class="text-right">

                Total Salary

            </th>

            <th class="text-right">

                {{ number_format($totalSalary,2) }}

            </th>

            <th></th>

        </tr>

    </tfoot>

</table>

<div class="footer">

    <br><br>

    <table style="border:none;">

        <tr>

            <td style="border:none;">
                Prepared By
                <br><br><br>
                __________________
            </td>

            <td style="border:none; text-align:right;">
                Approved By
                <br><br><br>
                __________________
            </td>

        </tr>

    </table>

</div>

</body>
</html>