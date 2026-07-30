@extends('company.layout')

@section('title', 'Dashboard')

@section('content')
@php
    $jobRoleVisibility = app(\App\Services\JobRoleVisibilityService::class);
    $canSeeDashboard = fn (string $section): bool => $jobRoleVisibility
        ->canSeeDashboard(auth()->user(), $section);
    $canViewCrm = $canSeeDashboard('crm');
    $canViewLoan = $canSeeDashboard('loan');
    $canViewDelivery = $canSeeDashboard('delivery');
@endphp

<div class="dg-page dg-dashboard-page">
<div class="container-fluid dg-container">

    {{-- =====================================================
        Dashboard Header
    ====================================================== --}}
    <div class="row align-items-center mb-3 dg-dashboard-heading">

        <div class="col-md-6">
            <h3 class="fw-bold mb-1">
                Dashboard
            </h3>

            <small class="text-muted">
                Welcome back, {{ auth()->user()->name }}
            </small>
        </div>

        <div class="col-md-6 text-md-end mt-3 mt-md-0">

            @can('sales.create')
            <a href="{{ route('company.sales.create') }}"
               class="btn btn-primary">
                <i class="fa fa-plus"></i>
                New Sale
            </a>
            @endcan

            @can('purchase.create')
            <a href="{{ route('company.purchase.create') }}"
               class="btn btn-success">
                <i class="fa fa-plus"></i>
                New Purchase
            </a>
            @endcan

        </div>

    </div>

    {{-- =====================================================
        KPI CARDS
    ====================================================== --}}

    <div class="row g-3">

        @if($canSeeDashboard('accounts'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">💰 Total Wallet</div>
                <div class="dg-value">
                    {{ number_format($data['cash'] + $data['bank'],2) }}
                </div>
            </div>
        </div>
        @endif

        @if($canSeeDashboard('inventory'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">📦 Products</div>
                <div class="dg-value">
                    {{ number_format($data['products']) }}
                </div>
            </div>
        </div>
        @endif

        @if($canSeeDashboard('sales'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">🧾 Sales</div>
                <div class="dg-value">
                    {{ number_format($data['sales']) }}
                </div>
            </div>
        </div>
        @endif

        @if($canSeeDashboard('purchase'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">🛒 Purchases</div>
                <div class="dg-value">
                    {{ number_format($data['purchases']) }}
                </div>
            </div>
        </div>
        @endif

        @if($canSeeDashboard('sales'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">👥 Customers</div>
                <div class="dg-value">
                    {{ number_format($data['customers']) }}
                </div>
            </div>
        </div>
        @endif

        @if($canSeeDashboard('cash_accounts'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">💵 Cash Wallet</div>
                <div class="dg-value">
                    {{ number_format($data['cash'],2) }}
                </div>
            </div>
        </div>
        @endif

        @if($canSeeDashboard('accounts'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">🏦 Bank Wallet</div>
                <div class="dg-value">
                    {{ number_format($data['bank'],2) }}
                </div>
            </div>
        </div>
        @endif

        @if($canSeeDashboard('income'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">📈 Active Income</div>
                <div class="dg-value">
                    {{ number_format($data['income_total'],2) }}
                </div>
            </div>
        </div>
        @endif

        @if($canSeeDashboard('expense'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">📉 Active Expense</div>
                <div class="dg-value">
                    {{ number_format($data['expense_total'],2) }}
                </div>
            </div>
        </div>
        @endif

        @if($canSeeDashboard('inventory'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">📦 Stock Items</div>
                <div class="dg-value">
                    {{ number_format($data['stock_items']) }}
                </div>
            </div>
        </div>
        @endif

        @if($canSeeDashboard('sales'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">💳 Customer Due</div>
                <div class="dg-value">
                    {{ number_format($data['customer_due'],2) }}
                </div>
            </div>
        </div>
        @endif

        @if($canSeeDashboard('purchase'))
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="dg-card dg-dashboard-card">
                <div class="dg-title">📄 Supplier Due</div>
                <div class="dg-value">
                    {{ number_format($data['supplier_due'],2) }}
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- =====================================================
        MODULE SUMMARY
    ====================================================== --}}

    @if(
        ($modules['hr'] && $canSeeDashboard('hr')) ||
        ($modules['crm'] && $canViewCrm) ||
        ($modules['loan'] && $canViewLoan) ||
        ($modules['delivery'] && $canViewDelivery)
    )

    <div class="row mt-4 g-3">

        @if($modules['hr'] && $canSeeDashboard('hr'))

        <div class="col-lg-3 col-md-6">
            <div class="dg-card">

                <h5 class="mb-3">
                    👨‍💼 HR Summary
                </h5>

                <table class="table table-sm mb-0">

                    <tr>
                        <td>Total Employee</td>
                        <td class="text-end">
                            {{ $hrSummary['total_employees'] }}
                        </td>
                    </tr>

                    <tr>
                        <td>Salary Generated</td>
                        <td class="text-end">
                            {{ number_format($hrSummary['salary_generated'],2) }}
                        </td>
                    </tr>

                    <tr>
                        <td>Salary Paid</td>
                        <td class="text-end">
                            {{ number_format($hrSummary['salary_paid'],2) }}
                        </td>
                    </tr>

                    <tr>
                        <td>Salary Due</td>
                        <td class="text-end text-danger">
                            {{ number_format($hrSummary['salary_due'],2) }}
                        </td>
                    </tr>

                </table>

            </div>
        </div>

        @endif

        @if($modules['crm'] && $canViewCrm)

        <div class="col-lg-3 col-md-6">
            <div class="dg-card">
                <h5>📞 CRM Summary</h5>
                <p class="mb-0 text-success">
                    Module Enabled
                </p>
            </div>
        </div>

        @endif

        @if($modules['loan'] && $canViewLoan)

        <div class="col-lg-3 col-md-6">
            <div class="dg-card">
                <h5>💳 Loan Summary</h5>
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Active Loans</td>
                        <td class="text-end">{{ number_format($loanSummary['active_loans'] ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Remaining Principal</td>
                        <td class="text-end">{{ number_format($loanSummary['remaining_principal'] ?? 0, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @endif

        @if($modules['delivery'] && $canViewDelivery)

        <div class="col-lg-3 col-md-6">
            <div class="dg-card">
                <h5>🚚 Delivery Summary</h5>
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Ready</td>
                        <td class="text-end">{{ number_format($deliverySummary['ready'] ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td>Completed</td>
                        <td class="text-end">{{ number_format($deliverySummary['completed'] ?? 0) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @endif

    </div>

    @endif

{{-- =====================================================
    ANALYTICS
===================================================== --}}

<div class="row mt-4">

    {{-- Sales Chart --}}
    @if($canSeeDashboard('sales'))
    <div class="col-lg-6 mb-4">

        <div class="dg-card">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="mb-0">
                    📊 Sales Overview
                </h5>

                <span class="badge bg-primary">
                    FY: {{ $activeFy?->name ?? 'Not set' }}
                </span>

            </div>

            <canvas id="salesChart" height="120"></canvas>

        </div>

    </div>
    @endif

    {{-- Purchase Chart --}}
    @if($canSeeDashboard('purchase'))
    <div class="col-lg-6 mb-4">

        <div class="dg-card">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="mb-0">
                    📈 Purchase Overview
                </h5>

                <span class="badge bg-success">
                    FY: {{ $activeFy?->name ?? 'Not set' }}
                </span>

            </div>

            <canvas id="purchaseChart" height="120"></canvas>

        </div>

    </div>
    @endif

</div>

{{-- =====================================================
    RECENT TRANSACTIONS
===================================================== --}}

<div class="row">

    {{-- Recent Sales --}}
    @if($canSeeDashboard('sales'))
    <div class="col-lg-6 mb-4">

        <div class="dg-card">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="mb-0">
                    🧾 Recent Sales
                </h5>

                <a href="{{ route('company.sales.index') }}"
                   class="btn btn-sm btn-outline-primary">
                    View All
                </a>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead>

                        <tr>
                            <th>Date</th>
                            <th>Invoice</th>
                            <th class="text-end">Amount</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($recentSales ?? [] as $sale)

                            <tr>

                                <td>
                                    {{ $sale->sale_date }}
                                </td>

                                <td>
                                    {{ $sale->invoice_no }}
                                </td>

                                <td class="text-end">

                                    {{ number_format($sale->grand_total,2) }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="3" class="text-center text-muted">

                                    No Sales Found

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>
    @endif

    {{-- Recent Purchase --}}
    @if($canSeeDashboard('purchase'))
    <div class="col-lg-6 mb-4">

        <div class="dg-card">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="mb-0">
                    🛒 Recent Purchases
                </h5>

                <a href="{{route('company.purchases.index') }}"
                   class="btn btn-sm btn-outline-success">
                    View All
                </a>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead>

                        <tr>
                            <th>Date</th>
                            <th>Invoice</th>
                            <th class="text-end">Amount</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($recentPurchases ?? [] as $purchase)

                            <tr>

                                <td>

                                    {{ $purchase->purchase_date }}

                                </td>

                                <td>

                                    {{ $purchase->invoice_no }}

                                </td>

                                <td class="text-end">

                                    {{ number_format($purchase->grand_total,2) }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="3" class="text-center text-muted">

                                    No Purchases Found

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>
    @endif

</div>
{{-- =====================================================
    DASHBOARD BOTTOM SECTION
===================================================== --}}

<div class="row">

    {{-- LOW STOCK --}}
    @if($canSeeDashboard('inventory'))
    <div class="col-lg-4 mb-4">

        <div class="dg-card">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="mb-0">
                    ⚠️ Low Stock
                </h5>

                <a href="{{ route('company.products.index') }}"
                   class="btn btn-sm btn-outline-danger">
                    View All
                </a>

            </div>

            <div class="table-responsive">

                <table class="table table-sm table-hover align-middle">

                    <thead>

                    <tr>
                        <th>Product</th>
                        <th class="text-end">Stock</th>
                    </tr>

                    </thead>

                    <tbody>

                    @forelse($lowStock as $item)

                        <tr>

                            <td>
                                {{ $item->product_name }}
                            </td>

                            <td class="text-end text-danger fw-bold">
                                {{ number_format($item->current_stock,2) }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="2" class="text-center text-muted">

                                No Low Stock Items

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>
    @endif

    {{-- STAFF ACTIVITY --}}
    @if($canSeeDashboard('staff_management') || $canSeeDashboard('hr'))
    <div class="col-lg-4 mb-4">

        <div class="dg-card">

            <h5 class="mb-3">
                👨‍💼 Staff Activity
            </h5>

            <ul class="list-group list-group-flush">

                @forelse($staffActivity as $activity)

                    <li class="list-group-item">

                        <strong>
                            {{ $activity->name }}
                        </strong>

                        <br>

                        <small class="text-muted">

                            {{ $activity->last_seen ? \Carbon\Carbon::parse($activity->last_seen)->diffForHumans() : 'No recent activity' }}

                        </small>

                    </li>

                @empty

                    <li class="list-group-item text-center text-muted">

                        No Recent Activity

                    </li>

                @endforelse

            </ul>

        </div>

    </div>
    @endif

    {{-- QUICK ACTION --}}
    @if($canSeeDashboard('sales') || $canSeeDashboard('purchase') || $canSeeDashboard('inventory'))
    @canany(['sales.create', 'purchase.create', 'customer.create', 'product.create'])
    <div class="col-lg-4 mb-4">

        <div class="dg-card">

            <h5 class="mb-3">
                ⚡ Quick Actions
            </h5>

            <div class="d-grid gap-2">

                @can('sales.create')
                <a href="{{ route('company.sales.create') }}"
                   class="btn btn-primary">
                    ➕ New Sale
                </a>
                @endcan

                @can('purchase.create')
                <a href="{{ route('company.purchase.create') }}"
                   class="btn btn-success">
                    🛒 New Purchase
                </a>
                @endcan

                @can('customer.create')
                <a href="{{ route('company.customer.create') }}"
                   class="btn btn-info">
                    👥 New Customer
                </a>
                @endcan

                @can('product.create')
                <a href="{{ route('company.product.create') }}"
                   class="btn btn-warning">
                    📦 New Product
                </a>
                @endcan

            </div>

        </div>

    </div>
    @endcanany
    @endif

</div>

{{-- =====================================================
    FOOTER SUMMARY
===================================================== --}}

<div class="row">

    <div class="col-12">

        <div class="dg-card">

            <div class="row text-center">

                <div class="col-md-3">

                    <h6 class="text-muted">
                        Financial Year
                    </h6>

                    <strong>

                        {{ $activeFy?->name ?? '-' }}

                    </strong>

                </div>

                <div class="col-md-3">

                    <h6 class="text-muted">
                        Company
                    </h6>

                    <strong>

                        {{ auth()->user()->company->company_name ?? '-' }}

                    </strong>

                </div>

                <div class="col-md-3">

                    <h6 class="text-muted">
                        Login User
                    </h6>

                    <strong>

                        {{ auth()->user()->name }}

                    </strong>

                </div>

                <div class="col-md-3">

                    <h6 class="text-muted">
                        Current Time
                    </h6>

                    <strong id="dashboardClock">

                        --

                    </strong>

                </div>

            </div>

        </div>

    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const salesCtx = document.getElementById('salesChart');

if (salesCtx && window.Chart) {

    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: @json($salesChart->pluck('day')->values()),
            datasets: [{
                label: 'Sales',
                data: @json($salesChart->pluck('total')->values()),
                borderWidth:2,
                fill:false
            }]
        }
    });

}

const purchaseCtx = document.getElementById('purchaseChart');

if (purchaseCtx && window.Chart) {

    new Chart(purchaseCtx, {
        type:'bar',
        data:{
            labels:@json($purchaseChart->pluck('day')->values()),
            datasets:[{
                label:'Purchase',
                data:@json($purchaseChart->pluck('total')->values())
            }]
        }
    });

}
</script>
@endpush
</div>
</div>
@endsection
