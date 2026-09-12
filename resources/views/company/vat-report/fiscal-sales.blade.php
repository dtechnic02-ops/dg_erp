@extends('company.layout')

@section('title', 'Fiscal Sales Account')

@section('content')
@php
    $money = fn ($value) => $value === null ? 'NOT READY' : number_format((float) $value, 2);
    $labels = [
        'gross_sales' => 'Gross', 'total_discount' => 'Discount', 'net_sales' => 'Net Sales',
        'taxable_sales' => 'Taxable', 'exempt_sales' => 'Exempt', 'zero_rated_sales' => 'Zero-rated',
        'export_sales' => 'Export', 'out_of_scope_sales' => 'Out of Scope', 'vat_total' => 'VAT',
        'grand_total' => 'Grand Total',
    ];
@endphp
<div class="dg-page">
    <main class="dg-container">
        <div class="container-fluid">
            <div class="dg-toolbar mb-3">
                <div>
                    <h1 class="h4 mb-1">Fiscal Sales Account</h1>
                    <div class="text-muted">Issued Nepal fiscal invoices and Credit Notes only</div>
                </div>
                @unless(request('print'))
                    <a class="btn btn-outline-primary dg-btn" href="{{ route('company.vat-report.fiscal-sales.print', request()->query()) }}">Print</a>
                @endunless
            </div>

            @if($report['summary']['not_ready_count'])
                <div class="alert alert-warning dg-alert">
                    {{ $report['summary']['not_ready_count'] }} fiscal document(s) have incomplete authoritative evidence. They are listed as NOT READY and excluded from totals.
                </div>
            @endif

            @unless(request('print'))
                <section class="dg-card mb-3 p-3">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label" for="financial_year_id">Financial Year</label>
                            <select class="form-select dg-select" id="financial_year_id" name="financial_year_id">
                                <option value="">All Years</option>
                                @foreach($financialYears as $fy)
                                    <option value="{{ $fy->id }}" @selected((string) $financialYearId === (string) $fy->id)>{{ $fy->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label" for="from_date">From (AD)</label><input class="form-control dg-input" type="date" id="from_date" name="from_date" value="{{ $fromDate }}"></div>
                        <div class="col-md-3"><label class="form-label" for="to_date">To (AD)</label><input class="form-control dg-input" type="date" id="to_date" name="to_date" value="{{ $toDate }}"></div>
                        <div class="col-md-2"><button class="btn btn-primary dg-btn w-100">Apply</button></div>
                    </form>
                </section>
            @endunless

            @foreach(['sales' => 'Fiscal Sales Register', 'credit_notes' => 'Fiscal Credit Note Register'] as $key => $title)
                <section class="dg-card mb-4">
                    <div class="p-3 border-bottom"><h2 class="h6 mb-0">{{ $title }}</h2></div>
                    <div class="table-responsive">
                        <table class="table dg-table mb-0">
                            <thead><tr>
                                <th>Date / Issue Date</th><th>FY</th><th>Document / Original</th><th>Buyer / PAN</th>
                                @foreach($labels as $label)<th class="text-end">{{ $label }}</th>@endforeach
                                <th>Status</th>
                            </tr></thead>
                            <tbody>
                            @forelse($report[$key] as $row)
                                <tr>
                                    <td>{{ $row['transaction_date'] }}<br><small class="text-muted">{{ $row['fiscal_issue_date'] }}</small></td>
                                    <td>{{ $row['financial_year'] ?: '-' }}</td>
                                    <td>{{ $row['document_no'] }}@if($key === 'credit_notes')<br><small class="text-muted">Original: {{ $row['original_invoice_no'] }} ({{ $row['original_invoice_date'] }})</small>@endif</td>
                                    <td>{{ $row['buyer_name'] ?: '-' }}<br><small class="text-muted">{{ $row['buyer_pan'] ?: '-' }}</small></td>
                                    @foreach(array_keys($labels) as $amount)<td class="text-end">{{ $money($row[$amount]) }}</td>@endforeach
                                    <td>
                                        @if($row['is_ready'])<span class="badge bg-success">Ready</span>
                                        @else<span class="badge bg-warning text-dark">Not Ready</span><div class="small mt-1">{{ implode(' ', $row['errors']) }}</div>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="15" class="text-center text-muted py-4">No fiscal documents found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach

            <section class="dg-card mb-4">
                <div class="p-3 border-bottom"><h2 class="h6 mb-0">Period Tax Summary</h2></div>
                <div class="table-responsive"><table class="table dg-table mb-0">
                    <thead><tr><th>Category</th>@foreach($labels as $label)<th class="text-end">{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach(['sales' => 'Sales', 'returns' => 'Credit Notes'] as $key => $label)
                            <tr><th>{{ $label }}</th>@foreach(array_keys($labels) as $amount)<td class="text-end">{{ $money($report['summary'][$key][$amount]) }}</td>@endforeach</tr>
                        @endforeach
                        <tr class="fw-bold"><th>Net</th><td class="text-end">-</td><td class="text-end">-</td><td class="text-end">-</td>
                            @foreach(['taxable_sales','exempt_sales','zero_rated_sales','export_sales','out_of_scope_sales','vat_total','grand_total'] as $amount)<td class="text-end">{{ $money($report['summary']['net'][$amount]) }}</td>@endforeach
                        </tr>
                    </tbody>
                </table></div>
            </section>
        </div>
    </main>
</div>
@if(request('print'))<script>window.print();</script>@endif
@endsection
