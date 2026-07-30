@extends('admin.layout')

@section('content')
<div class="dg-page">
    <div class="dg-page-header">
        <div>
            <h2 class="dg-page-title">Companies Management</h2>
            <p class="dg-page-subtitle">Review company accounts, limits, status, and available administration actions.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
    @endif

    <div class="dg-toolbar mb-3">
        <form method="GET" action="{{ route('admin.companies') }}" class="dg-form dg-filter">
            <div class="dg-form-row">
                <div class="dg-form-group dg-search">
                    <label for="company-search" class="visually-hidden">Search companies</label>
                    <input id="company-search" class="form-control dg-input" type="search" name="search" value="{{ request('search') }}" placeholder="Search company or email" autocomplete="off">
                </div>
                <div class="dg-form-group">
                    <label for="company-status" class="visually-hidden">Company status</label>
                    <select id="company-status" class="form-select dg-select" name="status">
                        <option value="">All statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                    </select>
                </div>
                <div class="dg-form-group">
                    <button type="submit" class="btn btn-primary dg-btn dg-btn-primary">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <div class="dg-card card">
        <div class="dg-card-header card-header d-flex justify-content-between align-items-center gap-2">
            <span>Company accounts</span>
            <span class="text-muted small">{{ $companies->total() }} total</span>
        </div>
        <div class="dg-card-body card-body">
            <div class="dg-table-scroll dg-table-wrap">
                <table class="table table-hover align-middle dg-table">
                    <thead class="dg-head">
                        <tr>
                            <th scope="col" class="dg-col-num">ID</th>
                            <th scope="col">Company</th>
                            <th scope="col">User limit</th>
                            <th scope="col">Customer limit</th>
                            <th scope="col" class="dg-col-status">Status</th>
                            <th scope="col" class="dg-col-date">Expiry date</th>
                            <th scope="col" class="dg-action-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="dg-body">
                        @forelse($companies as $c)
                            <tr class="dg-row">
                                <td class="dg-col-num">{{ $c->id }}</td>
                                <td>
                                    <strong>{{ $c->company_name }}</strong>
                                    <div class="text-muted small">{{ $c->email }}</div>
                                </td>
                                <td>
                                    @if(auth()->user()->hasPermission('edit_company'))
                                        <form method="POST" action="{{ route('admin.company.limit', $c->id) }}" class="dg-form dg-form-inline">
                                            @csrf
                                            <label for="company-limit-{{ $c->id }}" class="visually-hidden">User limit for {{ $c->company_name }}</label>
                                            <input id="company-limit-{{ $c->id }}" class="form-control form-control-sm dg-input" type="number" name="limit" value="{{ old('limit', $c->selected_user_limit) }}" min="0" required>
                                            <button type="submit" class="btn btn-outline-primary dg-btn dg-btn-sm" aria-label="Save user limit for {{ $c->company_name }}">Save</button>
                                        </form>
                                    @else
                                        {{ $c->selected_user_limit }}
                                    @endif
                                </td>
                                <td>
                                    @if(auth()->user()->hasPermission('edit_company'))
                                        <form method="POST" action="{{ route('admin.company.customer.limit', $c->id) }}" class="dg-form dg-form-inline">
                                            @csrf
                                            <label for="company-customer-limit-{{ $c->id }}" class="visually-hidden">Customer limit for {{ $c->company_name }}</label>
                                            <input id="company-customer-limit-{{ $c->id }}" class="form-control form-control-sm dg-input" type="number" name="customer_limit" value="{{ old('customer_limit', $c->selected_customer_limit) }}" min="0" required>
                                            <button type="submit" class="btn btn-outline-primary dg-btn dg-btn-sm" aria-label="Save customer limit for {{ $c->company_name }}">Save</button>
                                        </form>
                                    @else
                                        {{ $c->selected_customer_limit }}
                                    @endif
                                </td>
                                <td class="dg-col-status">
                                    <span class="dg-badge dg-badge-status {{ $c->status === 'active' ? 'dg-badge-success' : 'dg-badge-danger' }}">{{ ucfirst($c->status) }}</span>
                                </td>
                                <td class="dg-col-date">
                                    <div>{{ $c->expiry_date ?? 'N/A' }}</div>
                                    @if(isset($c->days) && $c->days <= 3 && $c->days >= 0)
                                        <span class="dg-status dg-status-warning">Expiring soon</span>
                                    @elseif(isset($c->days) && $c->days < 0)
                                        <span class="dg-status dg-status-danger">Expired</span>
                                    @endif
                                </td>
                                <td class="dg-action-col">
                                    <div class="dg-action-group">
                                        <a href="{{ route('admin.company.show', $c) }}" class="btn btn-outline-primary dg-btn dg-action-btn">View</a>
                                        @if(auth()->user()->hasPermission('reset_company_password'))
                                            <button type="button" class="btn btn-outline-secondary dg-btn dg-action-btn" data-bs-toggle="modal" data-bs-target="#company-reset-modal-{{ $c->id }}">Reset</button>
                                        @endif
                                        @if(auth()->user()->hasPermission('block_company') && $c->status === 'active')
                                            <button type="button" class="btn btn-outline-warning dg-btn dg-action-btn" data-bs-toggle="modal" data-bs-target="#company-block-modal-{{ $c->id }}">Block</button>
                                        @endif
                                        @if(auth()->user()->hasPermission('unblock_company') && $c->status !== 'active')
                                            <button type="button" class="btn btn-outline-success dg-btn dg-action-btn" data-bs-toggle="modal" data-bs-target="#company-unblock-modal-{{ $c->id }}">Unblock</button>
                                        @endif
                                        @if(auth()->user()->hasPermission('delete_company'))
                                            <button type="button" class="btn btn-outline-danger dg-btn dg-action-btn" data-bs-toggle="modal" data-bs-target="#company-delete-modal-{{ $c->id }}">Delete</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="dg-empty-state">No companies match the current search or filter.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="dg-pagination">{{ $companies->links() }}</div>
        </div>
    </div>
</div>

@foreach($companies as $c)
    @if(auth()->user()->hasPermission('reset_company_password'))
        <div class="modal fade dg-modal" id="company-reset-modal-{{ $c->id }}" tabindex="-1" aria-labelledby="company-reset-title-{{ $c->id }}" aria-hidden="true">
            <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="company-reset-title-{{ $c->id }}">Reset company admin password</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">Send a one-time verification code to your registered Super Admin email before setting a new password for <strong>{{ $c->company_name }}</strong>.</div><div class="modal-footer"><button type="button" class="btn btn-light dg-btn dg-btn-light" data-bs-dismiss="modal">Cancel</button><form method="POST" action="{{ route('admin.company.reset', $c) }}">@csrf<button type="submit" class="btn btn-warning dg-btn dg-btn-warning">Send OTP</button></form></div></div></div>
        </div>
    @endif
    @if(auth()->user()->hasPermission('block_company') && $c->status === 'active')
        <div class="modal fade dg-modal" id="company-block-modal-{{ $c->id }}" tabindex="-1" aria-labelledby="company-block-title-{{ $c->id }}" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="company-block-title-{{ $c->id }}">Block company</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">Block <strong>{{ $c->company_name }}</strong>? Its users will no longer be able to use the company account.</div><div class="modal-footer"><button type="button" class="btn btn-light dg-btn dg-btn-light" data-bs-dismiss="modal">Cancel</button><form method="POST" action="{{ route('admin.company.block', $c->id) }}">@csrf<button type="submit" class="btn btn-warning dg-btn dg-btn-warning">Block company</button></form></div></div></div></div>
    @endif
    @if(auth()->user()->hasPermission('unblock_company') && $c->status !== 'active')
        <div class="modal fade dg-modal" id="company-unblock-modal-{{ $c->id }}" tabindex="-1" aria-labelledby="company-unblock-title-{{ $c->id }}" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="company-unblock-title-{{ $c->id }}">Unblock company</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">Restore access for <strong>{{ $c->company_name }}</strong>?</div><div class="modal-footer"><button type="button" class="btn btn-light dg-btn dg-btn-light" data-bs-dismiss="modal">Cancel</button><form method="POST" action="{{ route('admin.company.unblock', $c->id) }}">@csrf<button type="submit" class="btn btn-success dg-btn dg-btn-success">Unblock company</button></form></div></div></div></div>
    @endif
    @if(auth()->user()->hasPermission('delete_company'))
        <div class="modal fade dg-modal" id="company-delete-modal-{{ $c->id }}" tabindex="-1" aria-labelledby="company-delete-title-{{ $c->id }}" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="company-delete-title-{{ $c->id }}">Delete company</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><form method="POST" action="{{ route('admin.company.delete', $c->id) }}" class="dg-form"><div class="modal-body"><p>This existing workflow permanently deletes <strong>{{ $c->company_name }}</strong> and its users. Enter your admin password to confirm.</p><label for="company-delete-password-{{ $c->id }}" class="form-label">Admin password</label><input id="company-delete-password-{{ $c->id }}" class="form-control dg-input" type="password" name="admin_password" autocomplete="current-password" required></div><div class="modal-footer">@csrf<button type="button" class="btn btn-light dg-btn dg-btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger dg-btn dg-btn-danger">Delete company</button></div></form></div></div></div>
    @endif
@endforeach
@endsection
