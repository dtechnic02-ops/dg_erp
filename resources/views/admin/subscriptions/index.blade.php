@extends('admin.layout')

@section('title', 'Company Subscriptions')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Company Subscriptions</h1>
                </div>
                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <nav class="btn-group" aria-label="Subscriptions toolbar">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
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
                        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="dg-form dg-filter-form">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Search Company</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input dg-search" value="{{ request('search') }}" placeholder="Company name">
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select">
                                        <option value="all" @selected(request('status', 'all') === 'all')>All</option>
                                        <option value="active" @selected(request('status') === 'active')>Active</option>
                                        <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary dg-btn">Search</button>
                                    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section" id="dgSubscriptionList">
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Subscription List</h2>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body p-0">
                        <div class="dg-table-scroll">
                            <table class="table dg-table dg-table-compact mb-0">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">Company</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Plan</th>
                                        <th scope="col">Cycle</th>
                                        <th scope="col" class="dg-col-num">Staff</th>
                                        <th scope="col" class="dg-col-date">Start</th>
                                        <th scope="col" class="dg-col-date">Expiry</th>
                                        <th scope="col" class="dg-col-status">Status</th>
                                        <th scope="col" class="dg-action-col">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($subscriptions as $subscription)
                                        @php
                                            $statusClass = match ($subscription->status) {
                                                'active' => 'success',
                                                'expired' => 'danger',
                                                'cancelled' => 'secondary',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <tr class="dg-row">
                                            <td>{{ $subscription->company->company_name ?? '-' }}</td>
                                            <td>{{ str_replace('_', ' ', ucfirst($subscription->subscription_type)) }}</td>
                                            <td>{{ $subscription->plan->name ?? 'Trial' }}</td>
                                            <td>{{ $subscription->billingCycle->name ?? '-' }}</td>
                                            <td class="dg-col-num">{{ $subscription->staff_limit }}</td>
                                            <td class="dg-col-date">{{ $subscription->start_date?->format('d-m-Y') }}</td>
                                            <td class="dg-col-date">{{ $subscription->expiry_date?->format('d-m-Y') ?? 'Lifetime' }}</td>
                                            <td class="dg-col-status">
                                                <span class="dg-badge dg-badge-status dg-badge-{{ $statusClass }}">{{ ucfirst($subscription->status) }}</span>
                                            </td>
                                            <td class="dg-action-col">
                                                @if ($subscription->company)
                                                    <div class="dg-action-group" role="group" aria-label="Subscription actions for {{ $subscription->company->company_name ?? 'company' }}">
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-primary dg-action-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#dgSubscriptionManageModal{{ $subscription->id }}"
                                                        >
                                                            Manage
                                                        </button>
                                                    </div>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No subscriptions found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($subscriptions->hasPages())
                            <div class="dg-list-footer">
                                <p class="dg-list-meta">
                                    Showing {{ $subscriptions->firstItem() ?? 0 }} to {{ $subscriptions->lastItem() ?? 0 }} of {{ $subscriptions->total() }} records
                                </p>

                                <div class="dg-pagination">
                                    {{ $subscriptions->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@foreach ($subscriptions as $subscription)
    @if ($subscription->company)
        @include('admin.partials.dg-subscription-manage-modal', [
            'subscription' => $subscription,
            'plans' => $plans,
            'billingCycles' => $billingCycles,
        ])
    @endif
@endforeach

@endsection
