@extends('company.layout')

@section('content')




<div>

<h5 class="mb-0">
    

<div class="container-fluid">

    <div class="card">

        <div class="d-flex justify-content-between align-items-center mb-2">

            <h4 class="mb-0">

                📦 Stock Ledger

            </h4>

            <!-- STOCK SYNC -->

            <form action="{{ route('company.stock-ledger.sync') }}"
                  method="POST">

                @csrf

                <button type="submit"
                        class="btn btn-warning btn-sm">

                    🔄 Sync Stock

                </button>

            </form>

        </div>

        <div class="card-body">

            <!-- FILTER FORM -->

            <form method="GET">

                <div class="row g-2">

                    <!-- PRODUCT -->

                    <div class="col-md-2">

                        <select name="product_id"
                                class="form-control">

                            <option value="">
                                All Products
                            </option>

                            @foreach($products as $product)

                                <option value="{{ $product->id }}"
                                    {{ request('product_id') == $product->id ? 'selected' : '' }}>

                                    {{ $product->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>
<div class="col-md-2">

    <select
        name="financial_year_id"
        class="form-control">

        <option value="all"
            {{ $financialYearId == 'all' ? 'selected' : '' }}>
            All Financial Years
        </option>

        @foreach($financialYears as $fy)

            <option
                value="{{ $fy->id }}"
                {{ $financialYearId == $fy->id ? 'selected' : '' }}>

                {{ $fy->name }}

            </option>

        @endforeach

    </select>

</div>
                    <!-- TYPE -->

                    <div class="col">

                        <select name="type"
                                class="form-control">

                            <option value="">
All Types
</option>

<option value="opening">
{{ request('type')=='opening' ? 'selected':'' }}>
Opening
</option>

<option value="purchase"
{{ request('type')=='purchase' ? 'selected':'' }}>
Purchase
</option>

<option value="sale"
{{ request('type')=='sale' ? 'selected':'' }}>
Sale
</option>

<option value="return"
{{ request('type')=='return' ? 'selected':'' }}>
All Returns
</option>

<option value="sale_return"
{{ request('type')=='sale_return' ? 'selected':'' }}>
Sales Return
</option>

<option value="purchase_return"
{{ request('type')=='purchase_return' ? 'selected':'' }}>
Purchase Return
</option>

<option value="adjustment_in"
{{ request('type')=='adjustment_in' ? 'selected':'' }}>
Adjustment In
</option>

<option value="adjustment_out"
{{ request('type')=='adjustment_out' ? 'selected':'' }}>
Adjustment Out
</option>

<option value="in"
{{ request('type')=='in' ? 'selected':'' }}>
Stock In
</option>

<option value="out"
{{ request('type')=='out' ? 'selected':'' }}>
Stock Out
</option>
                        </select>

                    </div>

                    <!-- START DATE -->

                    <div class="col-md-2">

                        <input type="date"
                               name="start_date"
                               value="{{ request('start_date') }}"
                               class="form-control">

                    </div>

                    <!-- END DATE -->

                    <div class="col-md-2">

                        <input type="date"
                               name="end_date"
                               value="{{ request('end_date') }}"
                               class="form-control">

                    </div>

                    <!-- FILTER -->

                    <div class="col-md-1">

                        <button class="btn btn-primary w-100">

                            Filter

                        </button>

                    </div>

                    <!-- PRINT -->

                    <div class="col-md-1">

                        <button type="button"
                                onclick="window.print()"
                                class="btn btn-dark w-100">

                            Print

                        </button>

                    </div>

                    <!-- PDF -->

                    <div class="col-md-1">

                        <a href="{{ route(
                            'company.stock-ledger.pdf',
                            request()->query()
                        ) }}"
                           class="btn btn-danger w-100">

                            PDF

                        </a>

                    </div>

                </div>

            </form>

            <!-- PRINT HEADER -->

            <div class="print-header">

                <!-- LOGO -->

                @if(auth()->user()->company->logo_path)

                    <div class="logo-box">

                        <img src="{{ asset(
                            auth()->user()->company->logo_path
                        ) }}"
                             class="company-logo">

                    </div>

                @endif

                <!-- COMPANY -->

                <h1 class="company-name">

                    {{ auth()->user()->company->name ?? 'Company' }}

                </h1>

                <!-- REPORT -->

                <h2 class="report-title">

                    Stock Ledger Report

                </h2>

                <!-- DATE -->

                <p class="print-date">

                    Print Date:
                    {{ now()->format('Y-m-d h:i A') }}

                </p>

            </div>

            <!-- SUMMARY -->

            <div class="ledger-summary">

                <div>

                    Total Records:
                    {{ $summary['total_movements'] }}

                </div>

                <div>

                    Total In:
                   {{ $summary['total_in'] }}

                </div>

                <div>

                    Total Out:
                   {{ $summary['total_out'] }}

                </div>

            </div>
</div>

            <!-- TABLE -->

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-dark">

                        <tr>

                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Qty</th>
                            <th>Before</th>
                            <th>After</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($movements as $move)

                            <tr>

                                <td>
{{ $move->transaction_date }}
                                </td>

                                <td>

                                    {{ optional($move->product)->name }}

                                </td>

                                <td>

                                   <span class="badge

@if(
in_array(
$move->type,

[
'purchase',
'sale_return',
'purchase_return',
'opening',
'adjustment_in'
]
)

)

bg-success

@else

bg-danger

@endif

">

                                        {{ str_replace('_',' ',ucfirst($move->type)) }}

                                    </span>

                                </td>

                                <td>

                                    {{ $move->reference_no }}

                                </td>

                                <td>

                                    @if($move->quantity > 0)

                                        <span class="text-success fw-bold">

                                            +{{ $move->quantity }}

                                        </span>

                                    @else

                                        <span class="text-danger fw-bold">

                                            {{ $move->quantity }}

                                        </span>

                                    @endif

                                </td>

                                <td>

                                    {{ $move->before_stock }}

                                </td>

                                <td>

                                    {{ $move->after_stock }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7"
                                    class="text-center py-4">

                                    No Stock Movement Found

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <!-- PAGINATION -->

            <div class="mt-3">

                {{ $movements->links() }}

            </div>

            <!-- SIGNATURE -->

            <div class="signature-section">

                <div>

                    @if(auth()->user()->company->signature_path)

                        <img src="{{ asset(
                            auth()->user()->company->signature_path
                        ) }}"
                             style="
                                height:60px;
                                margin-bottom:5px;
                             ">

                    @endif

                    <br>

                    _______________________

                    <br>

                    Authorized Signature

                </div>

            </div>

        </div>

    </div>

</div>

@endsection