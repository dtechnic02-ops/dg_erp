@extends('company.layout')

@section('title', 'Staff Management')

@section('content')

@php
    $canManage = auth()->user()->hasPermission('manage_users');
    $canEdit = auth()->user()->hasPermission('edit_users');
    $canBlock = auth()->user()->hasPermission('block_user');
    $canDelete = auth()->user()->hasPermission('delete_user');
    $canReset = auth()->user()->hasPermission('reset_password');
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Staff Management</h1>
                </div>
                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-nowrap">
                    @if(auth()->user()->hasPermission('manage_users'))
                        <a href="{{ route('company.permissions.index') }}" class="btn btn-outline-secondary dg-btn">Staff Permissions</a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if(session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger dg-alert" role="alert">{{ $errors->first() }}</div>
            @endif

            <section class="dg-section">
                <div class="dg-summary d-flex flex-row flex-nowrap justify-content-center align-items-center gap-3 mb-0 w-100">
                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Active Staff :</span>
                        <span class="fw-bold">{{ $staffCount }}</span>
                    </div>
                    <span>|</span>
                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Plan Limit :</span>
                        <span class="fw-bold">{{ $staffLimit }}</span>
                    </div>
                    <span>|</span>
                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Listed Records :</span>
                        <span class="fw-bold">{{ $users->total() }}</span>
                    </div>
                </div>
            </section>

            @if($canManage)
            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Add Staff</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <form method="POST" action="{{ route('company.users.store') }}" class="row g-3">
                            @csrf
                            <div class="col-md-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" name="name" id="name" class="form-control dg-input" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control dg-input" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control dg-input" required>
                            </div>
                            <div class="col-md-2">
                                <label for="job_role" class="form-label">Job Role</label>
                                <select name="job_role" id="job_role" class="form-select dg-select" required>
                                    <option value="">Select</option>
                                    @foreach(\App\Services\JobRoleVisibilityService::jobRoles() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('job_role') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-success dg-btn w-100">Add Staff</button>
                            </div>
                        </form>
                    </div>
                </article>
            </section>
            @endif

            <section class="dg-section dg-filter">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>
                    <div class="card-body dg-card-body dg-filter-card-body">
                        <form method="GET" action="{{ route('company.users.index') }}" class="dg-filter-form">
                            <div class="dg-filter-grid">
                                <div class="dg-filter-field">
                                    <label for="search" class="dg-filter-label">Search</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input dg-filter-control" value="{{ request('search') }}" placeholder="Name / Email">
                                </div>
                                <div class="dg-filter-field">
                                    <label for="status" class="dg-filter-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select dg-filter-control">
                                        <option value="">All</option>
                                        <option value="active" @selected(request('status') === 'active')>Active</option>
                                        <option value="blocked" @selected(request('status') === 'blocked')>Blocked</option>
                                    </select>
                                </div>
                                <div class="dg-filter-field">
                                    <label for="job_role" class="dg-filter-label">Job Role</label>
                                    <select name="job_role" id="job_role_filter" class="form-select dg-select dg-filter-control">
                                        <option value="">All</option>
                                        @foreach(\App\Services\JobRoleVisibilityService::jobRoles() as $value => $label)
                                            <option value="{{ $value }}" @selected(request('job_role') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="dg-filter-actions">
                                    <button type="submit" class="btn btn-primary dg-btn">Apply</button>
                                    <a href="{{ route('company.users.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section dg-list">
                <article class="card dg-card">
                    <header class="card-header dg-card-header d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0">Staff List</h2>
                        <form method="GET" action="{{ route('company.users.index') }}" class="d-flex align-items-center gap-2 mb-0">
                            @foreach(request()->except('per_page') as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <label for="per_page" class="dg-list-per-page-label">Show</label>
                            <select name="per_page" id="per_page" class="form-select dg-select dg-list-per-page-select" onchange="this.form.submit()">
                                @foreach([10, 20, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </form>
                    </header>
                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table dg-table-compact">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Job Role</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Online</th>
                                        <th scope="col" width="320">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse($users as $staff)
                                        <tr class="dg-row">
                                            <td>{{ $users->firstItem() + $loop->index }}</td>
                                            <td>{{ $staff->name }}</td>
                                            <td>{{ $staff->email }}</td>
                                            <td>{{ ucfirst($staff->job_role ?? 'N/A') }}</td>
                                            <td>
                                                @if($staff->account_status === 'active')
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Blocked</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($staff->last_seen && \Carbon\Carbon::parse($staff->last_seen)->gt(now()->subMinutes(2)))
                                                    <span class="badge bg-success">Online</span>
                                                @else
                                                    <span class="badge bg-secondary">Offline</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap dg-action-group">
                                                    @if($canEdit)
                                                        <a href="{{ route('company.users.edit', $staff->id) }}" class="btn btn-sm btn-outline-warning dg-action-btn">Edit</a>
                                                    @endif

                                                    @if($canManage)
                                                        <a href="{{ route('company.staff-permissions.edit', $staff->id) }}" class="btn btn-sm btn-outline-primary dg-action-btn">Permissions</a>
                                                    @endif

                                                    @if($canBlock)
                                                        @if($staff->account_status === 'active')
                                                            <form method="POST" action="{{ route('company.users.block', $staff->id) }}" class="d-inline" onsubmit="return confirm('Block this staff member?');">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-secondary dg-action-btn">Block</button>
                                                            </form>
                                                        @else
                                                            <form method="POST" action="{{ route('company.users.unblock', $staff->id) }}" class="d-inline" onsubmit="return confirm('Activate this staff member?');">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-success dg-action-btn">Unblock</button>
                                                            </form>
                                                        @endif
                                                    @endif

                                                    @if($canReset)
                                                        <button type="button" class="btn btn-sm btn-outline-primary dg-action-btn" data-bs-toggle="modal" data-bs-target="#resetModal{{ $staff->id }}">Reset Password</button>
                                                    @endif

                                                    @if($canDelete)
                                                        <form method="POST" action="{{ route('company.users.delete', $staff->id) }}" class="d-inline" onsubmit="return confirm('Delete this staff member?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-danger dg-action-btn">Delete</button>
                                                        </form>
                                                    @endif
                                                </div>

                                                @if($canReset)
                                                <div class="modal fade" id="resetModal{{ $staff->id }}" tabindex="-1" aria-labelledby="resetModalLabel{{ $staff->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST" action="{{ route('company.users.reset', $staff->id) }}">
                                                                @csrf
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="resetModalLabel{{ $staff->id }}">Reset Password — {{ $staff->name }}</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p class="small text-muted">Leave blank to generate a secure temporary password. Passwords are never shown on screen.</p>
                                                                    <div class="mb-3">
                                                                        <label for="new_password_{{ $staff->id }}" class="form-label">New Password</label>
                                                                        <input type="password" name="new_password" id="new_password_{{ $staff->id }}" class="form-control dg-input" minlength="8">
                                                                    </div>
                                                                    <div class="mb-0">
                                                                        <label for="new_password_confirmation_{{ $staff->id }}" class="form-label">Confirm Password</label>
                                                                        <input type="password" name="new_password_confirmation" id="new_password_confirmation_{{ $staff->id }}" class="form-control dg-input" minlength="8">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary dg-btn">Reset Password</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="7" class="text-center">No staff members found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} records
                            </p>
                            <div class="dg-pagination">
                                {{ $users->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@endsection
