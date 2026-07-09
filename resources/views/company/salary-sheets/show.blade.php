@extends('company.layout')

@section('content')

<div class="card">

    <div class="card-header">
        <h5 class="mb-0">
            Salary Sheet Details
        </h5>
    </div>

    <div class="card-body">

        <table class="table table-bordered">

            <tr>
                <th>Employee</th>
                <td>
                    {{ $salarySheet->employee->first_name }}
                </td>
            </tr>

            <tr>
                <th>Employee Code</th>
                <td>
                    {{ $salarySheet->employee->employee_code }}
                </td>
            </tr>

            <tr>
                <th>Salary Month</th>
                <td>
                    {{ $salarySheet->salary_month }}
                </td>
            </tr>

            <tr>
                <th>Basic Salary</th>
                <td>
                    {{ number_format($salarySheet->basic_salary,2) }}
                </td>
            </tr>

            <tr>
                <th>Working Days</th>
                <td>
                    {{ $salarySheet->working_days }}
                </td>
            </tr>

            <tr>
                <th>Present Days</th>
                <td>
                    {{ $salarySheet->present_days }}
                </td>
            </tr>

            <tr>
                <th>Absent Days</th>
                <td>
                    {{ $salarySheet->absent_days }}
                </td>
            </tr>

            <tr>
                <th>Allowance</th>
                <td>
                    {{ number_format($salarySheet->allowance,2) }}
                </td>
            </tr>

            <tr>
                <th>Bonus</th>
                <td>
                    {{ number_format($salarySheet->bonus,2) }}
                </td>
            </tr>

            <tr>
                <th>Overtime</th>
                <td>
                    {{ number_format($salarySheet->overtime_amount,2) }}
                </td>
            </tr>

            <tr>
                <th>Deduction</th>
                <td>
                    {{ number_format($salarySheet->deduction,2) }}
                </td>
            </tr>

            <tr>
                <th>Net Salary</th>
                <td>
                    <strong>
                        {{ number_format($salarySheet->net_salary,2) }}
                    </strong>
                </td>
            </tr>

            <tr>
                <th>Status</th>
                <td>

                    @if($salarySheet->status=='paid')

                        <span class="badge bg-success">
                            Paid
                        </span>

                    @else

                        <span class="badge bg-danger">
                            Unpaid
                        </span>

                    @endif

                </td>
            </tr>

            <tr>
                <th>Note</th>
                <td>
                    {{ $salarySheet->note }}
                </td>
            </tr>

        </table>

    </div>

</div>

@endsection