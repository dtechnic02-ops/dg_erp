@extends('company.layout')

@section('title', 'Trial Balance')

@section('content')

<div class="dg-page">
    <header class="dg-toolbar @if (request('print')) d-print-none @endif">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Trial Balance</h1>
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

                                <div class="col-md-2 col-lg-2">
                                    <label for="from_date" class="form-label">From</label>
                                    <input type="date" name="from_date" id="from_date" value="{{ $from }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-2 col-lg-2">
                                    <label for="to_date" class="form-label">To</label>
                                    <input type="date" name="to_date" id="to_date" value="{{ $to }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="show_zero" id="show_zero" value="1" class="form-check-input" @checked($showZero)>
                                        <label for="show_zero" class="form-check-label">Show Zero Balance</label>
                                    </div>
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

            @unless ($report['integrity'])
                <div class="alert alert-danger dg-alert" role="alert">
                    REPORT INTEGRITY FAILURE — Trial Balance totals do not balance.
                </div>
            @endunless

            <section class="dg-section dg-summary mb-2">
                <div class="row dg-row g-2">
                    @foreach (['Opening Debit' => 'opening_debit', 'Opening Credit' => 'opening_credit', 'Period Debit' => 'period_debit', 'Period Credit' => 'period_credit', 'Closing Debit' => 'closing_debit', 'Closing Credit' => 'closing_credit'] as $label => $key)
                        <div class="col-12 col-md-4 col-xl-2">
                            <article class="card dg-card h-100">
                                <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                    <span class="small mb-0">{{ $label }}</span>
                                </header>
                                <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                    <span class="fw-bold fs-6">{{ number_format((float) $report['totals'][$key], 2) }}</span>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="dg-section">
                <article class="card dg-card dg-print">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Trial Balance</h2>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="dg-table-scroll">
                            <table class="table dg-table dg-table-compact">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">Code</th>
                                        <th scope="col">Account</th>
                                        <th scope="col" class="dg-col-num">Opening Debit</th>
                                        <th scope="col" class="dg-col-num">Opening Credit</th>
                                        <th scope="col" class="dg-col-num">Period Debit</th>
                                        <th scope="col" class="dg-col-num">Period Credit</th>
                                        <th scope="col" class="dg-col-num">Closing Debit</th>
                                        <th scope="col" class="dg-col-num">Closing Credit</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($report['rows'] as $row)
                                        <tr class="dg-row">
                                            <td>{{ $row['account']->code }}</td>
                                            <td>{{ $row['account']->name }}{{ $row['account']->status !== 'active' ? ' (Inactive)' : '' }}</td>
                                            @foreach (['opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit'] as $key)
                                                <td class="dg-col-num">{{ number_format((float) $row[$key], 2) }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="8" class="text-center">No trial balance activity.</td>
                                        </tr>
                                    @endforelse
                                </tbody>

                                <tfoot>
                                    <tr class="dg-row fw-bold">
                                        <td colspan="2">TOTAL</td>
                                        @foreach (['opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit'] as $key)
                                            <td class="dg-col-num">{{ number_format((float) $report['totals'][$key], 2) }}</td>
                                        @endforeach
                                    </tr>
                                </tfoot>
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
