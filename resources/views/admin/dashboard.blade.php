@extends('admin.layout')

@section('content')

<h2 class="title">📊 Admin Dashboard</h2>

<style>
body {
    background:#020617;
}

/* TITLE */
.title {
    color:#e2e8f0;
    margin-bottom:20px;
}

/* SECTION */
.section {
    margin-bottom:25px;
}

.section h4 {
    color:#94a3b8;
    margin-bottom:10px;
}

/* BAR */
.bar {
    display:flex;
    flex-wrap:wrap;
    gap:15px;
    background:#0f172a;
    padding:12px;
    border-radius:8px;
}

/* ITEM */
.item {
    background:#020617;
    padding:8px 12px;
    border-radius:6px;
    font-size:14px;
}

.item span {
    font-weight:bold;
    font-size:16px;
}

/* COLORS */
.green { color:#22c55e; }
.red { color:#ef4444; }
.blue { color:#3b82f6; }
.yellow { color:#facc15; }

/* GRID */
.grid {
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
    gap:12px;
}

.card {
    background:#0f172a;
    padding:15px;
    border-radius:8px;
    text-align:center;
}

.card h4 {
    color:#94a3b8;
    font-size:13px;
}

.card p {
    font-size:20px;
    font-weight:bold;
}
</style>

<!-- 👤 USERS -->
<div class="section">
<h4>👤 Users</h4>
<div class="bar">
    <div class="item blue">Total: <span>{{ $totalUsers ?? 0 }}</span></div>
    <div class="item green">Active: <span>{{ $activeUsers ?? 0 }}</span></div>
    <div class="item red">Blocked: <span>{{ $blockedUsers ?? 0 }}</span></div>
    <div class="item yellow">Pending: <span>{{ $pendingUsers ?? 0 }}</span></div>
    <div class="item blue">Admin: <span>{{ $admins ?? 0 }}</span></div>
    <div class="item blue">Staff: <span>{{ $staff ?? 0 }}</span></div>
    <div class="item green">Online: <span>{{ $onlineUsers ?? 0 }}</span></div>
    <div class="item red">Offline: <span>{{ $offlineUsers ?? 0 }}</span></div>
</div>
</div>

<!-- 🏢 COMPANIES -->
<div class="section">
<h4>🏢 Companies</h4>
<div class="bar">
    <div class="item blue">Total: <span>{{ $totalCompanies ?? 0 }}</span></div>
    <div class="item green">Active: <span>{{ $activeCompanies ?? 0 }}</span></div>
    <div class="item red">Blocked: <span>{{ $blockedCompanies ?? 0 }}</span></div>
    <div class="item yellow">Expired: <span>{{ $expiredCompanies ?? 0 }}</span></div>
</div>
</div>

<!-- 📝 REGISTRATION -->
<div class="section">
<h4>📝 Registration</h4>
<div class="bar">
    <div class="item blue">Total: <span>{{ $totalRegistrations ?? 0 }}</span></div>
    <div class="item green">Approved: <span>{{ $approved ?? 0 }}</span></div>
    <div class="item red">Rejected: <span>{{ $rejected ?? 0 }}</span></div>
    <div class="item yellow">Pending: <span>{{ $pending ?? 0 }}</span></div>
</div>
</div>

<!-- 💳 PAYMENTS -->
<div class="section">
<h4>💳 Payments</h4>
<div class="grid">
    <div class="card">
        <h4>Total</h4>
        <p>{{ $totalPayments ?? 0 }}</p>
    </div>

    <div class="card">
        <h4>Approved</h4>
        <p class="green">{{ $approvedPayments ?? 0 }}</p>
    </div>

    <div class="card">
        <h4>Rejected</h4>
        <p class="red">{{ $rejectedPayments ?? 0 }}</p>
    </div>

    <div class="card">
        <h4>Pending</h4>
        <p class="yellow">{{ $pendingPayments ?? 0 }}</p>
    </div>

    <div class="card">
        <h4>Trial</h4>
        <p>{{ $trial ?? 0 }}</p>
    </div>
</div>
</div>

<!-- 📦 SYSTEM -->
<div class="section">
<h4>📦 System</h4>
<div class="grid">
    <div class="card">
        <h4>Total Plans</h4>
        <p>{{ $plans ?? 0 }}</p>
    </div>

    <div class="card">
        <h4>Online Users</h4>
        <p class="green">{{ $onlineUsers ?? 0 }}</p>
    </div>

    <div class="card">
        <h4>Offline Users</h4>
        <p class="red">{{ $offlineUsers ?? 0 }}</p>
    </div>
</div>
</div>

@endsection