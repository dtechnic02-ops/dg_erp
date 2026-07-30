@extends('admin.layout')

@section('content')
<div class="dg-page dg-dashboard">
    <header class="dg-page-header">
        <div class="dg-page-header-content">
            <h2 class="dg-page-title">Admin Dashboard</h2>
            <p class="dg-page-subtitle">Platform overview for users, companies, registrations, subscriptions, and payments.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
    @endif

    <section class="dg-section" aria-labelledby="platform-overview-title">
        <div class="dg-section-header">
            <div>
                <h3 id="platform-overview-title" class="dg-section-title">Platform Overview</h3>
                <p class="dg-section-subtitle">Users, companies, and registration activity.</p>
            </div>
        </div>

        <div class="dg-dashboard-grid">
            <article class="dg-stat-card dg-stat-card-primary">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-people"></i></span><span class="dg-stat-card-label">Total users</span></div>
                <div class="dg-stat-card-value">{{ number_format($totalUsers) }}</div><p class="dg-stat-card-meta">All platform accounts</p>
            </article>
            <article class="dg-stat-card dg-stat-card-success">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-person-check"></i></span><span class="dg-stat-card-label">Active users</span></div>
                <div class="dg-stat-card-value">{{ number_format($activeUsers) }}</div><p class="dg-stat-card-meta">Account status: active</p>
            </article>
            <article class="dg-stat-card dg-stat-card-danger">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-person-slash"></i></span><span class="dg-stat-card-label">Blocked users</span></div>
                <div class="dg-stat-card-value">{{ number_format($blockedUsers) }}</div><p class="dg-stat-card-meta">Account status: blocked</p>
            </article>
            <article class="dg-stat-card dg-stat-card-warning">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span><span class="dg-stat-card-label">Pending users</span></div>
                <div class="dg-stat-card-value">{{ number_format($pendingUsers) }}</div><p class="dg-stat-card-meta">Account status: pending</p>
            </article>
            <article class="dg-stat-card dg-stat-card-neutral">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-person-gear"></i></span><span class="dg-stat-card-label">Super Admins</span></div>
                <div class="dg-stat-card-value">{{ number_format($admins) }}</div><p class="dg-stat-card-meta">System-level administrators</p>
            </article>
            <article class="dg-stat-card dg-stat-card-neutral">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-person-workspace"></i></span><span class="dg-stat-card-label">Company staff</span></div>
                <div class="dg-stat-card-value">{{ number_format($staff) }}</div><p class="dg-stat-card-meta">Company Staff accounts</p>
            </article>
            <article class="dg-stat-card dg-stat-card-success">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-broadcast-pin"></i></span><span class="dg-stat-card-label">Online users</span></div>
                <div class="dg-stat-card-value">{{ number_format($onlineUsers) }}</div><p class="dg-stat-card-meta">Seen in the last two minutes</p>
            </article>
            <article class="dg-stat-card dg-stat-card-neutral">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-person-dash"></i></span><span class="dg-stat-card-label">Offline users</span></div>
                <div class="dg-stat-card-value">{{ number_format($offlineUsers) }}</div><p class="dg-stat-card-meta">Not recently active</p>
            </article>
            <article class="dg-stat-card dg-stat-card-primary">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-buildings"></i></span><span class="dg-stat-card-label">Total companies</span></div>
                <div class="dg-stat-card-value">{{ number_format($totalCompanies) }}</div><p class="dg-stat-card-meta">All registered companies</p>
            </article>
            <article class="dg-stat-card dg-stat-card-success">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-building-check"></i></span><span class="dg-stat-card-label">Active companies</span></div>
                <div class="dg-stat-card-value">{{ number_format($activeCompanies) }}</div><p class="dg-stat-card-meta">Company status: active</p>
            </article>
            <article class="dg-stat-card dg-stat-card-danger">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-building-x"></i></span><span class="dg-stat-card-label">Blocked companies</span></div>
                <div class="dg-stat-card-value">{{ number_format($blockedCompanies) }}</div><p class="dg-stat-card-meta">Company status: blocked</p>
            </article>
            <article class="dg-stat-card dg-stat-card-warning">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-calendar-x"></i></span><span class="dg-stat-card-label">Expired companies</span></div>
                <div class="dg-stat-card-value">{{ number_format($expiredCompanies) }}</div><p class="dg-stat-card-meta">Expiry date has passed</p>
            </article>
            <article class="dg-stat-card dg-stat-card-primary">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span><span class="dg-stat-card-label">Registrations</span></div>
                <div class="dg-stat-card-value">{{ number_format($totalRegistrations) }}</div><p class="dg-stat-card-meta">All registration requests</p>
            </article>
            <article class="dg-stat-card dg-stat-card-success">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-file-earmark-check"></i></span><span class="dg-stat-card-label">Approved registrations</span></div>
                <div class="dg-stat-card-value">{{ number_format($approved) }}</div><p class="dg-stat-card-meta">Registration status: approved</p>
            </article>
            <article class="dg-stat-card dg-stat-card-danger">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-file-earmark-x"></i></span><span class="dg-stat-card-label">Rejected registrations</span></div>
                <div class="dg-stat-card-value">{{ number_format($rejected) }}</div><p class="dg-stat-card-meta">Registration status: rejected</p>
            </article>
            <article class="dg-stat-card dg-stat-card-warning">
                <div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-file-earmark-arrow-up"></i></span><span class="dg-stat-card-label">Pending registrations</span></div>
                <div class="dg-stat-card-value">{{ number_format($pending) }}</div><p class="dg-stat-card-meta">Registration status: pending</p>
            </article>
        </div>
    </section>

    <section class="dg-section" aria-labelledby="subscription-overview-title">
        <div class="dg-section-header"><div><h3 id="subscription-overview-title" class="dg-section-title">Subscription Overview</h3><p class="dg-section-subtitle">Existing plan and trial totals.</p></div></div>
        <div class="dg-dashboard-grid">
            <article class="dg-stat-card dg-stat-card-info"><div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-box-seam"></i></span><span class="dg-stat-card-label">Subscription plans</span></div><div class="dg-stat-card-value">{{ number_format($plans) }}</div><p class="dg-stat-card-meta">Configured subscription plans</p></article>
            <article class="dg-stat-card dg-stat-card-warning"><div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-gift"></i></span><span class="dg-stat-card-label">Trial companies</span></div><div class="dg-stat-card-value">{{ number_format($trial) }}</div><p class="dg-stat-card-meta">Companies without an expiry date</p></article>
        </div>
    </section>

    <section class="dg-section" aria-labelledby="payment-overview-title">
        <div class="dg-section-header"><div><h3 id="payment-overview-title" class="dg-section-title">Payment Overview</h3><p class="dg-section-subtitle">Existing subscription payment statuses.</p></div></div>
        <div class="dg-dashboard-grid">
            <article class="dg-stat-card dg-stat-card-primary"><div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-credit-card"></i></span><span class="dg-stat-card-label">Total payments</span></div><div class="dg-stat-card-value">{{ number_format($totalPayments) }}</div><p class="dg-stat-card-meta">All subscription payments</p></article>
            <article class="dg-stat-card dg-stat-card-success"><div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-check-circle"></i></span><span class="dg-stat-card-label">Approved payments</span></div><div class="dg-stat-card-value">{{ number_format($approvedPayments) }}</div><p class="dg-stat-card-meta">Payment status: approved</p></article>
            <article class="dg-stat-card dg-stat-card-danger"><div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-x-circle"></i></span><span class="dg-stat-card-label">Rejected payments</span></div><div class="dg-stat-card-value">{{ number_format($rejectedPayments) }}</div><p class="dg-stat-card-meta">Payment status: rejected</p></article>
            <article class="dg-stat-card dg-stat-card-warning"><div class="dg-stat-card-header"><span class="dg-stat-card-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span><span class="dg-stat-card-label">Pending payments</span></div><div class="dg-stat-card-value">{{ number_format($pendingPayments) }}</div><p class="dg-stat-card-meta">Payment status: pending</p></article>
        </div>
    </section>
</div>
@endsection
