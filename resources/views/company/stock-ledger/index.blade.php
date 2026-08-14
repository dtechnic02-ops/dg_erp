@extends('company.layout')

@section('content')

<div class="dg-page">
    <main class="dg-container">
        <div class="container-fluid">
            <section class="dg-section d-print-none">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <form method="GET">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4 col-lg-2">
                                    <label for="product_id" class="form-label">Product</label>
                                    <select name="product_id" id="product_id" class="form-select dg-select">
                                        <option value="">All Products</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-2">
                                    <label for="financial_year_id" class="form-label">Financial Year</label>
                                    <select name="financial_year_id" id="financial_year_id" class="form-select dg-select">
                                        <option value="all" @selected($financialYearId == 'all')>All Financial Years</option>
                                        @foreach ($financialYears as $fy)
                                            <option value="{{ $fy->id }}" @selected($financialYearId == $fy->id)>{{ $fy->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-2">
                                    <label for="type" class="form-label">Type</label>
                                    <select name="type" id="type" class="form-select dg-select">
                                        <option value="">All Types</option>
                                        <option value="opening" @selected(request('type') == 'opening')>Opening</option>
                                        <option value="purchase" @selected(request('type') == 'purchase')>Purchase</option>
                                        <option value="sale" @selected(request('type') == 'sale')>Sale</option>
                                        <option value="return" @selected(request('type') == 'return')>All Returns</option>
                                        <option value="sale_return" @selected(request('type') == 'sale_return')>Sales Return</option>
                                        <option value="purchase_return" @selected(request('type') == 'purchase_return')>Purchase Return</option>
                                        <option value="adjustment_in" @selected(request('type') == 'adjustment_in')>Adjustment In</option>
                                        <option value="adjustment_out" @selected(request('type') == 'adjustment_out')>Adjustment Out</option>
                                        <option value="in" @selected(request('type') == 'in')>Stock In</option>
                                        <option value="out" @selected(request('type') == 'out')>Stock Out</option>
                                    </select>
                                </div>

                                <div class="col-md-4 col-lg-2">
                                    <label for="start_date" class="form-label">Date From</label>
                                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-4 col-lg-2">
                                    <label for="end_date" class="form-label">Date To</label>
                                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-4 col-lg-2">
                                    <button type="submit" class="btn btn-primary dg-btn">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card dg-print">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Stock Ledger</h2>

                        <div class="ms-auto d-flex align-items-center justify-content-end gap-3 flex-wrap">
                            <span class="small">Total Records: <strong>{{ $summary['total_movements'] }}</strong></span>
                            <span class="small">Total In: <strong>{{ $summary['total_in'] }}</strong></span>
                            <span class="small">Total Out: <strong>{{ $summary['total_out'] }}</strong></span>

                            <nav class="btn-group d-print-none" aria-label="Stock Ledger actions">
                                <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary dg-btn">Print</button>
                                <a href="{{ route('company.stock-ledger.pdf', request()->query()) }}" class="btn btn-sm btn-danger dg-btn">PDF</a>
                            </nav>
                        </div>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="dg-table-scroll">
                            <table class="table dg-table dg-table-compact">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col" class="dg-col-date">Date</th>
                                        <th scope="col">Product</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Reference</th>
                                        <th scope="col" class="dg-col-num">Qty</th>
                                        <th scope="col" class="dg-col-num">Before</th>
                                        <th scope="col" class="dg-col-num">After</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($movements as $move)
                                        <tr class="dg-row">
                                            <td class="dg-col-date">{{ $move->transaction_date }} @include('company.components.nepali-date-display', ['adDate' => $move->transaction_date])</td>
                                            <td>{{ optional($move->product)->name }}</td>
                                            <td>
                                                <span class="badge @if (in_array($move->type, ['purchase', 'sale_return', 'purchase_return', 'opening', 'adjustment_in'])) bg-success @else bg-danger @endif">
                                                    {{ str_replace('_', ' ', ucfirst($move->type)) }}
                                                </span>
                                            </td>
                                            <td>{{ $move->reference_no }}</td>
                                            <td class="dg-col-num">
                                                @if ($move->quantity > 0)
                                                    <span class="text-success fw-bold">+{{ $move->quantity }}</span>
                                                @else
                                                    <span class="text-danger fw-bold">{{ $move->quantity }}</span>
                                                @endif
                                            </td>
                                            <td class="dg-col-num">{{ $move->before_stock }}</td>
                                            <td class="dg-col-num">{{ $move->after_stock }}</td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="7" class="text-center">No Stock Movement Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer d-print-none">
                            <p class="dg-list-meta">
                                Showing {{ $movements->firstItem() ?? 0 }} to {{ $movements->lastItem() ?? 0 }} of {{ $movements->total() }} records
                            </p>

                            <div class="dg-pagination">
                                {{ $movements->links() }}
                            </div>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </main>
</div>

@endsection
