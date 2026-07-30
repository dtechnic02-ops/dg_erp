@extends('admin.layout')

@section('title', 'User Details')

@section('content')
    <div class="dg-page dg-record-print">
        <div class="dg-page-header dg-print-hide">
            <div class="dg-page-header-content">
                <h2 class="dg-page-title">User Details</h2>
                <p class="dg-page-subtitle">User profile, company reference, account status, and login activity.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.users') }}" class="btn btn-light dg-btn dg-btn-light">Back</a>
                <button type="button" class="btn btn-primary dg-btn dg-btn-primary" onclick="window.print()">Print A4</button>
            </div>
        </div>

        <article id="printArea" class="dg-card card dg-record-sheet">
            <header class="dg-record-header">
                <div class="dg-record-brand">
                    <div class="dg-record-logo-fallback" aria-hidden="true">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</div>
                    <div>
                        <h1 class="dg-record-title">{{ $user->name }}</h1>
                        <p class="dg-record-subtitle">User profile and login activity summary</p>
                    </div>
                </div>
                <div class="dg-record-status">
                    <span class="dg-badge dg-badge-status {{ $user->account_status === 'blocked' ? 'dg-badge-danger' : 'dg-badge-success' }}">{{ ucfirst($user->account_status === 'blocked' ? 'blocked' : 'active') }}</span>
                    <span class="dg-record-id">User ID: {{ $user->id }}</span>
                </div>
            </header>

            <div class="dg-record-grid">
                <section class="dg-record-section" aria-labelledby="user-profile-title">
                    <h2 id="user-profile-title" class="dg-record-section-title">User Profile</h2>
                    <dl class="dg-record-list">
                        <div><dt>Name</dt><dd>{{ $user->name ?? 'N/A' }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $user->email ?? 'N/A' }}</dd></div>
                        <div><dt>Role</dt><dd>{{ $user->role?->name ?? 'Not assigned' }}</dd></div>
                        <div><dt>Job role</dt><dd>{{ $user->job_role ?: 'Not assigned' }}</dd></div>
                        <div><dt>Account status</dt><dd>{{ ucfirst($user->account_status ?? 'N/A') }}</dd></div>
                    </dl>
                </section>

                <section class="dg-record-section" aria-labelledby="user-company-title">
                    <h2 id="user-company-title" class="dg-record-section-title">Company Reference</h2>
                    <dl class="dg-record-list">
                        <div><dt>Company</dt><dd>{{ $user->company?->company_name ?? 'No company assigned' }}</dd></div>
                        <div><dt>Company ID</dt><dd>{{ $user->company_id ?? 'N/A' }}</dd></div>
                        <div><dt>Company email</dt><dd>{{ $user->company?->email ?? 'N/A' }}</dd></div>
                        <div><dt>Company status</dt><dd>{{ ucfirst($user->company?->status ?? 'N/A') }}</dd></div>
                    </dl>
                </section>

                <section class="dg-record-section" aria-labelledby="user-login-title">
                    <h2 id="user-login-title" class="dg-record-section-title">Last Login & Activity</h2>
                    <dl class="dg-record-list">
                        <div><dt>Last login</dt><dd>{{ $user->login_at ? \Carbon\Carbon::parse($user->login_at)->format('Y-m-d H:i') : 'Not recorded' }}</dd></div>
                        <div><dt>Last logout</dt><dd>{{ $user->logout_at ? \Carbon\Carbon::parse($user->logout_at)->format('Y-m-d H:i') : 'Not recorded' }}</dd></div>
                        <div><dt>Last seen</dt><dd>{{ $user->last_seen ? \Carbon\Carbon::parse($user->last_seen)->format('Y-m-d H:i') : 'Not recorded' }}</dd></div>
                        <div><dt>Presence</dt><dd>{{ $user->last_seen && \Carbon\Carbon::parse($user->last_seen)->gt(now()->subMinutes(2)) ? 'Online recently' : 'Offline' }}</dd></div>
                    </dl>
                </section>

                <section class="dg-record-section" aria-labelledby="user-record-title">
                    <h2 id="user-record-title" class="dg-record-section-title">Record Information</h2>
                    <dl class="dg-record-list">
                        <div><dt>Created</dt><dd>{{ $user->created_at?->format('Y-m-d H:i') ?? 'N/A' }}</dd></div>
                        <div><dt>Last updated</dt><dd>{{ $user->updated_at?->format('Y-m-d H:i') ?? 'N/A' }}</dd></div>
                    </dl>
                </section>
            </div>

            <footer class="dg-record-footer">Printed from DG ERP on {{ now()->format('Y-m-d H:i') }}</footer>
        </article>
    </div>
@endsection
