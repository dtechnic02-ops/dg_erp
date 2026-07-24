@extends('admin.layout')

@section('title', 'Subscription Reports')

@php
    use App\Models\BillingCycle;
    use App\Models\Company;
    use App\Models\CompanySubscription;
    use App\Models\SubscriptionPlan;

    $perPage = (int) request('per_page', 20);
    if (! in_array($perPage, [10, 20, 100, 200], true)) {
        $perPage = 20;
    }

    $subscriptionsQuery = CompanySubscription::with(['company', 'plan', 'billingCycle', 'payments'])
        ->when(request('date_from'), fn ($q) => $q->whereDate('start_date', '>=', request('date_from')))
        ->when(request('date_to'), fn ($q) => $q->whereDate('expiry_date', '<=', request('date_to')))
        ->when(request('company_id'), fn ($q) => $q->where('company_id', request('company_id')))
        ->when(request('subscription_plan_id'), fn ($q) => $q->where('subscription_plan_id', request('subscription_plan_id')))
        ->when(request('billing_cycle_id'), fn ($q) => $q->where('billing_cycle_id', request('billing_cycle_id')))
        ->when(request('status') && request('status') !== 'all', fn ($q) => $q->where('status', request('status')))
        ->when(request('search'), function ($q) {
            $search = request('search');
            $q->where(function ($inner) use ($search) {
                $inner->whereHas('company', fn ($company) => $company->where('company_name', 'like', "%{$search}%"))
                    ->orWhereHas('plan', fn ($plan) => $plan->where('name', 'like', "%{$search}%"));
            });
        });

    $subscriptions = $subscriptionsQuery->latest('id')->paginate($perPage)->withQueryString();

    $filterCompanies = Company::orderBy('company_name')->get(['id', 'company_name']);
    $filterPlans = SubscriptionPlan::orderBy('sort_order')->get(['id', 'name']);
    $filterBillingCycles = BillingCycle::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);

    $totalCompanies = Company::count();
    $totalRevenue = $revenueReports->sum('total_amount');
@endphp

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Subscription Reports</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <nav class="btn-group" aria-label="Subscription reports toolbar">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        <a href="{{ route('admin.subscription-payments.index', request()->query()) }}" class="btn btn-outline-secondary dg-btn">Export</a>
                        <a href="{{ route('admin.subscription-reports.index') }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
                    </nav>
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <section class="dg-section dg-filter">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <form method="GET" action="{{ route('admin.subscription-reports.index') }}">
                            <div class="row g-2 align-items-end">

                                <div class="col-md-2 col-lg-1">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control dg-input" value="{{ request('date_from') }}">
                                </div>

                                <div class="col-md-2 col-lg-1">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control dg-input" value="{{ request('date_to') }}">
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <label for="company_id" class="form-label">Company</label>
                                    <select name="company_id" id="company_id" class="form-select dg-select">
                                        <option value="">All Companies</option>
                                        @foreach ($filterCompanies as $company)
                                            <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->company_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 col-lg-2">
                                    <label for="subscription_plan_id" class="form-label">Plan</label>
                                    <select name="subscription_plan_id" id="subscription_plan_id" class="form-select dg-select">
                                        <option value="">All Plans</option>
                                        @foreach ($filterPlans as $plan)
                                            <option value="{{ $plan->id }}" @selected(request('subscription_plan_id') == $plan->id)>{{ $plan->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 col-lg-2">
                                    <label for="billing_cycle_id" class="form-label">Billing Cycle</label>
                                    <select name="billing_cycle_id" id="billing_cycle_id" class="form-select dg-select">
                                        <option value="">All Cycles</option>
                                        @foreach ($filterBillingCycles as $cycle)
                                            <option value="{{ $cycle->id }}" @selected(request('billing_cycle_id') == $cycle->id)>{{ $cycle->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 col-lg-1">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select">
                                        <option value="all" @selected(request('status', 'all') === 'all')>All</option>
                                        <option value="active" @selected(request('status') === 'active')>Active</option>
                                        <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                                    </select>
                                </div>

                                <div class="col-md-2 col-lg-2">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input" value="{{ request('search') }}" placeholder="Company or Plan">
                                </div>

                                @if (request('per_page'))
                                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                @endif

                                <div class="col-md-2 col-lg-2 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary dg-btn">Search</button>
                                    <a href="{{ route('admin.subscription-reports.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>

                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section dg-summary mb-2">
                <div class="row dg-row g-2">

                    <div class="col-12 col-md-6 col-lg-3">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Total Companies</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($totalCompanies) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Active Subscriptions</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($activeCompanies) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Expired Subscriptions</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($expiredCompanies) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Total Revenue</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($totalRevenue, 2) }}</span>
                            </div>
                        </article>
                    </div>

                </div>
            </section>

            <section class="dg-section" id="dgSubscriptionReportsList">
                <article class="card dg-card dg-print">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Subscription Reports List</h2>

                        <form method="GET" action="{{ route('admin.subscription-reports.index') }}" class="dg-list-per-page">
                            <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                            <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                            <input type="hidden" name="company_id" value="{{ request('company_id') }}">
                            <input type="hidden" name="subscription_plan_id" value="{{ request('subscription_plan_id') }}">
                            <input type="hidden" name="billing_cycle_id" value="{{ request('billing_cycle_id') }}">
                            <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                            <input type="hidden" name="search" value="{{ request('search') }}">

                            <label for="per_page" class="dg-list-per-page-label">Show</label>
                            <select name="per_page" id="per_page" class="form-select dg-select dg-list-per-page-select" onchange="this.form.submit()">
                                <option value="10" @selected($perPage == 10)>10</option>
                                <option value="20" @selected($perPage == 20)>20</option>
                                <option value="100" @selected($perPage == 100)>100</option>
                                <option value="200" @selected($perPage == 200)>200</option>
                            </select>
                        </form>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="dg-table-scroll">
                            <table class="table dg-table dg-table-compact">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Company</th>
                                        <th scope="col">Plan</th>
                                        <th scope="col">Billing Cycle</th>
                                        <th scope="col" class="dg-col-date">Start Date</th>
                                        <th scope="col" class="dg-col-date">Expiry Date</th>
                                        <th scope="col" class="dg-col-status">Status</th>
                                        <th scope="col" class="dg-col-num dg-col-total">Amount</th>
                                        <th scope="col" class="dg-action-col">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($subscriptions as $subscription)
                                        @php
                                            $latestPayment = $subscription->payments
                                                ->where('status', 'approved')
                                                ->sortByDesc('id')
                                                ->first();
                                            $statusClass = match ($subscription->status) {
                                                'active' => 'success',
                                                'expired' => 'danger',
                                                'cancelled' => 'secondary',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <tr class="dg-row">
                                            <td>{{ $subscriptions->firstItem() + $loop->index }}</td>
                                            <td>{{ $subscription->company->company_name ?? '-' }}</td>
                                            <td>{{ $subscription->plan->name ?? str_replace('_', ' ', ucfirst($subscription->subscription_type)) }}</td>
                                            <td>{{ $subscription->billingCycle->name ?? '-' }}</td>
                                            <td class="dg-col-date">{{ $subscription->start_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td class="dg-col-date">{{ $subscription->expiry_date?->format('d-m-Y') ?? 'Lifetime' }}</td>
                                            <td class="dg-col-status">
                                                <span class="dg-badge dg-badge-status dg-badge-{{ $statusClass }}">
                                                    {{ ucfirst($subscription->status) }}
                                                </span>
                                            </td>
                                            <td class="dg-col-num dg-col-total">{{ $latestPayment ? number_format($latestPayment->amount, 2) : '-' }}</td>
                                            <td class="dg-action-col">
                                                <div class="dg-action-group" role="group" aria-label="Subscription actions for {{ $subscription->company->company_name ?? 'company' }}">
                                                    <a href="{{ route('admin.subscriptions.index', ['search' => $subscription->company->company_name ?? '']) }}" class="btn btn-sm btn-outline-primary dg-action-btn">View</a>
                                                    @if ($latestPayment)
                                                        <a href="{{ route('admin.subscription-payments.invoice', $latestPayment->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary dg-action-btn">Invoice</a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No subscription records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $subscriptions->firstItem() ?? 0 }} to {{ $subscriptions->lastItem() ?? 0 }} of {{ $subscriptions->total() }} records
                            </p>

                            <div class="dg-pagination">
                                {{ $subscriptions->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@endsection
