@extends('company.layout')

@section('title', 'General Ledger')

@section('content')

<div class="dg-page">
    <header class="dg-toolbar @if (request('print')) d-print-none @endif">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">General Ledger</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">
            <section class="dg-section dg-filter @if (request('print')) d-print-none @endif">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <form method="GET">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3 col-lg-2">
                                    <label for="financial_year_id" class="form-label">Financial Year</label>
                                    <select name="financial_year_id" id="financial_year_id" class="form-select dg-select">
                                        @foreach ($financialYears as $fy)
                                            <option value="{{ $fy->id }}" @selected($fy->id === $financialYear->id)>{{ $fy->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-5 col-lg-4">
                                    <label for="chart_account_id" class="form-label">Chart Account</label>
                                    <select name="chart_account_id" id="chart_account_id" class="form-select dg-select">
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected($account->id === $accountId)>{{ $account->code }} — {{ $account->name }}{{ $account->status !== 'active' ? ' (Inactive)' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 col-lg-2">
                                    <label for="from_date" class="form-label">From</label>
                                    <input type="date" name="from_date" id="from_date" value="{{ $from }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-2 col-lg-2">
                                    <label for="to_date" class="form-label">To</label>
                                    <input type="date" name="to_date" id="to_date" value="{{ $to }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-4 col-lg-2 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary dg-btn">Run</button>
                                    <button type="submit" name="print" value="1" class="btn btn-outline-secondary dg-btn">Print</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section dg-summary mb-2">
                <div class="row dg-row g-2">
                    @foreach (['Opening Balance' => $report['opening_balance'], 'Period Debit' => $report['period_debit'], 'Period Credit' => $report['period_credit'], 'Closing Balance' => $report['closing_balance']] as $label => $value)
                        <div class="col-12 col-md-3">
                            <article class="card dg-card h-100">
                                <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                    <span class="small mb-0">{{ $label }}</span>
                                </header>
                                <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                    <span class="fw-bold fs-6">{{ number_format((float) $value, 2) }}</span>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="dg-section">
                <article class="card dg-card dg-print">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">General Ledger</h2>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="dg-table-scroll">
                            <table class="table dg-table dg-table-compact">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col" class="dg-col-date">Date</th>
                                        <th scope="col">Entry</th>
                                        <th scope="col">Reference</th>
                                        <th scope="col">Source</th>
                                        <th scope="col">Description</th>
                                        <th scope="col" class="dg-col-num">Debit</th>
                                        <th scope="col" class="dg-col-num">Credit</th>
                                        <th scope="col" class="dg-col-num">Running Balance</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($report['rows'] as $row)
                                        <tr class="dg-row">
                                            <td class="dg-col-date">{{ $row['entry_date'] }} @include('company.components.nepali-date-display', ['adDate' => $row['entry_date']])</td>
                                            <td>{{ $row['entry_number'] }}</td>
                                            <td>{{ $row['reference'] }}</td>
                                            <td>{{ $row['source'] }}</td>
                                            <td>{{ $row['description'] }}</td>
                                            <td class="dg-col-num">{{ number_format((float) $row['debit'], 2) }}</td>
                                            <td class="dg-col-num">{{ number_format((float) $row['credit'], 2) }}</td>
                                            <td class="dg-col-num">{{ number_format((float) $row['running_balance'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="8" class="text-center">No ledger activity.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </main>
</div>

@if (request('print'))
    @push('scripts')
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endpush
@endif

@endsection
