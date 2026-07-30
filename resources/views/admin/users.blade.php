@extends('admin.layout')

@section('content')
<div class="dg-page">
    <div class="dg-page-header"><div><h2 class="dg-page-title">Users Management</h2><p class="dg-page-subtitle">Review legacy platform users, their company reference, role, and account status.</p></div></div>

    @if(session('success'))
        <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
    @endif

    <div class="dg-toolbar mb-3">
        <form method="GET" action="{{ route('admin.users') }}" class="dg-form dg-filter"><div class="dg-form-row">
            <div class="dg-form-group dg-search"><label for="user-search" class="visually-hidden">Search users</label><input id="user-search" class="form-control dg-input" type="search" name="search" value="{{ request('search') }}" placeholder="Search name or email" autocomplete="off"></div>
            <div class="dg-form-group"><label for="user-status" class="visually-hidden">User status</label><select id="user-status" class="form-select dg-select" name="status"><option value="">All statuses</option><option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option><option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option></select></div>
            <div class="dg-form-group"><label for="user-role" class="visually-hidden">User role</label><select id="user-role" class="form-select dg-select" name="role"><option value="">All roles</option><option value="1" {{ request('role') === '1' ? 'selected' : '' }}>Admin</option><option value="2" {{ request('role') === '2' ? 'selected' : '' }}>Company</option><option value="3" {{ request('role') === '3' ? 'selected' : '' }}>Staff</option></select></div>
            <div class="dg-form-group"><button type="submit" class="btn btn-primary dg-btn dg-btn-primary">Filter</button></div>
        </div></form>
    </div>

    <div class="dg-card card"><div class="dg-card-header card-header d-flex justify-content-between align-items-center gap-2"><span>Platform users</span><span class="text-muted small">{{ $users->total() }} total</span></div><div class="dg-card-body card-body">
        <div class="dg-table-scroll dg-table-wrap"><table class="table table-hover align-middle dg-table"><thead class="dg-head"><tr><th scope="col" class="dg-col-num">ID</th><th scope="col">User</th><th scope="col">Company</th><th scope="col">Role</th><th scope="col" class="dg-col-status">Status</th><th scope="col" class="dg-action-col">Actions</th></tr></thead><tbody class="dg-body">
            @forelse($users as $u)
                <tr class="dg-row"><td class="dg-col-num"><span class="dg-presence {{ $u->last_seen && \Carbon\Carbon::parse($u->last_seen)->gt(now()->subMinutes(2)) ? 'dg-presence-online' : 'dg-presence-offline' }}" aria-label="{{ $u->last_seen && \Carbon\Carbon::parse($u->last_seen)->gt(now()->subMinutes(2)) ? 'Online recently' : 'Offline' }}"></span>{{ $u->id }}</td><td><strong>{{ $u->name }}</strong><div class="text-muted small">{{ $u->email }}</div></td><td>{{ $u->company->company_name ?? 'N/A' }}</td><td>@if((int) $u->role_id === \App\Models\Role::SUPER_ADMIN_ID) Admin @elseif((int) $u->role_id === \App\Models\Role::COMPANY_ADMIN_ID) Company @else Staff @endif</td><td class="dg-col-status"><span class="dg-badge dg-badge-status {{ $u->account_status === 'blocked' ? 'dg-badge-danger' : 'dg-badge-success' }}">{{ ucfirst($u->account_status === 'blocked' ? 'blocked' : 'active') }}</span></td><td class="dg-action-col">
                    @if((int) auth()->user()->role_id === \App\Models\Role::SUPER_ADMIN_ID)
                        <div class="dg-action-group"><a href="{{ route('admin.user.show', $u) }}" class="btn btn-outline-primary dg-btn dg-action-btn">View</a>@if($u->account_status === 'blocked')<button type="button" class="btn btn-outline-success dg-btn dg-action-btn" data-bs-toggle="modal" data-bs-target="#user-unblock-modal-{{ $u->id }}">Unblock</button>@else<button type="button" class="btn btn-outline-warning dg-btn dg-action-btn" data-bs-toggle="modal" data-bs-target="#user-block-modal-{{ $u->id }}">Block</button>@endif<button type="button" class="btn btn-outline-secondary dg-btn dg-action-btn" data-bs-toggle="modal" data-bs-target="#user-reset-modal-{{ $u->id }}">Reset</button><button type="button" class="btn btn-outline-danger dg-btn dg-action-btn" data-bs-toggle="modal" data-bs-target="#user-delete-modal-{{ $u->id }}">Delete</button></div>
                    @endif
                </td></tr>
            @empty
                <tr><td colspan="6"><div class="dg-empty-state">No users match the current search or filter.</div></td></tr>
            @endforelse
        </tbody></table></div>
        <div class="dg-pagination">{{ $users->appends(request()->query())->links() }}</div>
    </div></div>
</div>

@if((int) auth()->user()->role_id === \App\Models\Role::SUPER_ADMIN_ID)
    @foreach($users as $u)
        @if($u->account_status === 'blocked')
            <div class="modal fade dg-modal" id="user-unblock-modal-{{ $u->id }}" tabindex="-1" aria-labelledby="user-unblock-title-{{ $u->id }}" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="user-unblock-title-{{ $u->id }}">Unblock user</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">Restore access for <strong>{{ $u->name }}</strong>?</div><div class="modal-footer"><button type="button" class="btn btn-light dg-btn dg-btn-light" data-bs-dismiss="modal">Cancel</button><form method="POST" action="{{ route('admin.user.unblock', $u->id) }}">@csrf<button type="submit" class="btn btn-success dg-btn dg-btn-success">Unblock user</button></form></div></div></div></div>
        @else
            <div class="modal fade dg-modal" id="user-block-modal-{{ $u->id }}" tabindex="-1" aria-labelledby="user-block-title-{{ $u->id }}" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="user-block-title-{{ $u->id }}">Block user</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">Block <strong>{{ $u->name }}</strong>? This uses the existing user block workflow.</div><div class="modal-footer"><button type="button" class="btn btn-light dg-btn dg-btn-light" data-bs-dismiss="modal">Cancel</button><form method="POST" action="{{ route('admin.user.block', $u->id) }}">@csrf<button type="submit" class="btn btn-warning dg-btn dg-btn-warning">Block user</button></form></div></div></div></div>
        @endif
        <div class="modal fade dg-modal" id="user-reset-modal-{{ $u->id }}" tabindex="-1" aria-labelledby="user-reset-title-{{ $u->id }}" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="user-reset-title-{{ $u->id }}">Reset user password</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">Send a one-time verification code to your registered Super Admin email before setting a new password for <strong>{{ $u->name }}</strong>.</div><div class="modal-footer"><button type="button" class="btn btn-light dg-btn dg-btn-light" data-bs-dismiss="modal">Cancel</button><form method="POST" action="{{ route('admin.user.reset', $u) }}">@csrf<button type="submit" class="btn btn-warning dg-btn dg-btn-warning">Send OTP</button></form></div></div></div></div>
        <div class="modal fade dg-modal" id="user-delete-modal-{{ $u->id }}" tabindex="-1" aria-labelledby="user-delete-title-{{ $u->id }}" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="user-delete-title-{{ $u->id }}">Delete user</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">Delete <strong>{{ $u->name }}</strong> through the existing workflow? This action cannot be undone.</div><div class="modal-footer"><button type="button" class="btn btn-light dg-btn dg-btn-light" data-bs-dismiss="modal">Cancel</button><form method="POST" action="{{ route('admin.user.delete', $u->id) }}">@csrf<button type="submit" class="btn btn-danger dg-btn dg-btn-danger">Delete user</button></form></div></div></div></div>
    @endforeach
@endif
@endsection
