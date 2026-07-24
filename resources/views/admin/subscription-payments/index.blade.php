@extends('admin.layout')

@section('title', 'Subscription Payments')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Subscription Payments</h1>
                </div>
                <div class="flex-fill d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.subscription-payments.manual') }}" class="btn btn-primary dg-btn">Manual Payment</a>
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
                        <form method="GET" action="{{ route('admin.subscription-payments.index') }}" class="dg-filter-form dg-form">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Search Company</label>
                                    <input type="text" name="search" class="form-control dg-input dg-search" value="{{ request('search') }}" placeholder="Company name">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select dg-select">
                                        <option value="all" @selected(request('status', 'all') === 'all')>All</option>
                                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                                        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary dg-btn w-100">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Payment List</h2>
                    </header>
                    <div class="card-body dg-card-body p-0">
                        <div class="table-responsive">
                            <table class="table dg-table mb-0">
                                <thead class="dg-head">
                                    <tr>
                                        <th>ID</th>
                                        <th>Company</th>
                                        <th>Plan</th>
                                        <th>Cycle</th>
                                        <th>Action</th>
                                        <th>Amount</th>
                                        <th>Proof</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($payments as $payment)
                                        <tr class="dg-row">
                                            <td>{{ $payment->id }}</td>
                                            <td>{{ $payment->company->company_name ?? 'N/A' }}</td>
                                            <td>{{ $payment->plan->name ?? 'N/A' }}</td>
                                            <td>{{ $payment->billingCycle->name ?? 'N/A' }}</td>
                                            <td>{{ ucfirst($payment->action_type) }}</td>
                                            <td>{{ number_format($payment->amount, 2) }}</td>
                                            <td>
                                                @if ($payment->proof_path)
                                                    <a href="{{ asset('storage/'.$payment->proof_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary dg-btn">View</a>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ ucfirst($payment->status) }}</td>
                                            <td class="text-end">
                                                @if ($payment->status === 'pending')
                                                    <form method="POST" action="{{ route('admin.subscription-payments.verify', $payment->id) }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-info dg-btn">Verify</button></form>
                                                    <form method="POST" action="{{ route('admin.subscription-payments.approve', $payment->id) }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-success dg-btn">Approve</button></form>
                                                    <details class="d-inline-block">
                                                        <summary class="btn btn-sm btn-outline-danger dg-btn">Reject</summary>
                                                        <form method="POST" action="{{ route('admin.subscription-payments.reject', $payment->id) }}" class="p-2 border rounded mt-2 dg-form">
                                                            @csrf
                                                            <label class="form-label">Reason</label>
                                                            <textarea name="rejection_reason" class="form-control dg-textarea mb-2" required maxlength="500"></textarea>
                                                            <button type="submit" class="btn btn-sm btn-danger dg-btn">Confirm Reject</button>
                                                        </form>
                                                    </details>
                                                @else
                                                    <span class="text-muted">Processed</span>
                                                @endif
                                                <a href="{{ route('admin.subscription-payments.invoice', $payment->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary dg-btn">Invoice</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row"><td colspan="9" class="text-center">No payments found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if ($payments->hasPages())
                        <footer class="card-footer dg-card-footer">
                            {{ $payments->links() }}
                        </footer>
                    @endif
                </article>
            </section>

        </div>
    </main>
</div>
@endsection
