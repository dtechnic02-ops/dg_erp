@extends('company.layout')

@section('title', 'Payroll Register Print')

@section('content')

<div class="dg-page dg-payment-print">

    <main class="dg-container">
        <div class="container-fluid">

            <div id="printArea">
                <article class="card dg-card dg-payment">

                    <header class="text-center border-bottom pb-2 mb-2">
                        @if ($company?->logo_path)
                            <img
                                src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                                alt="{{ $company->company_name ?? 'Company' }}"
                                class="dg-print-logo d-block mx-auto mb-1">
                        @endif
                        <h1 class="dg-print-title mb-0">Payroll Register</h1>
                        <p class="mb-0 text-muted">{{ $company->company_name ?? '-' }}</p>
                    </header>

                    <div class="card-body dg-card-body py-2 px-3">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <strong>Total Net:</strong> {{ number_format($totals['net_salary'], 2) }}
                            </div>
                            <div class="col-md-3">
                                <strong>Total Paid:</strong> {{ number_format($totals['paid'], 2) }}
                            </div>
                            <div class="col-md-3">
                                <strong>Total Due:</strong> {{ number_format($totals['due'], 2) }}
                            </div>
                            <div class="col-md-3">
                                <strong>Active / Cancelled:</strong> {{ number_format($activeCount) }} / {{ number_format($cancelledCount) }}
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Branch</th>
                                        <th>Salary Month</th>
                                        <th class="text-end">Net</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Due</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($salarySheets as $sheet)
                                        <tr>
                                            <td>{{ $sheet->employee->full_name ?? $sheet->employee->first_name }}</td>
                                            <td>{{ $sheet->employee->department ?: '-' }}</td>
                                            <td>{{ $company->company_name ?? '-' }}</td>
                                            <td>{{ $sheet->salary_month }}</td>
                                            <td class="text-end">{{ number_format($sheet->net_salary, 2) }}</td>
                                            <td class="text-end">{{ number_format($sheet->paid_amount, 2) }}</td>
                                            <td class="text-end">{{ number_format($sheet->due_amount, 2) }}</td>
                                            <td>{{ ucfirst($sheet->status) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end small text-muted mt-3">
                            Printed Date: {{ now()->format('d-m-Y H:i') }}
                        </div>
                    </div>

                </article>
            </div>

        </div>
    </main>
</div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>

@endsection
