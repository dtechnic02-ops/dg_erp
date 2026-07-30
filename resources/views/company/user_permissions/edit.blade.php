@extends('company.layout')

@section('title', 'User Permission Management')

@section('content')

@php
    $permissionActions = [
        'view', 'create', 'edit', 'delete', 'manage', 'block', 'unblock',
        'reset', 'print', 'cancel', 'approve', 'reject', 'payment', 'receive',
        'export', 'import', 'restore', 'archive', 'assign', 'close', 'process',
    ];

    $permissionModules = $permissions->groupBy(function ($permission) use ($permissionActions) {
        $segments = explode('_', $permission->name);
        $first = $segments[0] ?? '';
        $last = $segments[count($segments) - 1] ?? '';

        if (in_array($first, $permissionActions, true)) {
            $segments = array_slice($segments, 1);
        } elseif (in_array($last, $permissionActions, true)) {
            array_pop($segments);
        }

        return ucwords(str_replace('_', ' ', implode('_', $segments) ?: 'general'));
    });
@endphp

<div class="dg-page">
<div class="container-fluid">

    <header class="card dg-card mb-3">
        <div class="card-header dg-card-header d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">User Permission Management</h1>
                <small class="text-muted">
                    Employee: {{ $user->name ?? '—' }} · {{ $user->job_role ?? '—' }}
                </small>
            </div>
            <a href="{{ route('company.users.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </header>

    <section class="card dg-card dg-permission-summary" aria-labelledby="dgPermissionSummaryTitle">
        <header class="card-header dg-card-header">
            <h2 class="h6 mb-0" id="dgPermissionSummaryTitle">Staff Information</h2>
        </header>
        <div class="card-body dg-card-body">
            <dl class="row mb-0">
                <div class="col-12 col-md-6 col-xl-3 dg-permission-summary-item">
                    <dt>Employee Name</dt>
                    <dd>{{ $user->name ?? '—' }}</dd>
                </div>
                <div class="col-12 col-md-6 col-xl-3 dg-permission-summary-item">
                    <dt>Employee ID</dt>
                    <dd>{{ $user->id ?? '—' }}</dd>
                </div>
                <div class="col-12 col-md-6 col-xl-3 dg-permission-summary-item">
                    <dt>Email</dt>
                    <dd>{{ $user->email ?? '—' }}</dd>
                </div>
                <div class="col-12 col-md-6 col-xl-3 dg-permission-summary-item">
                    <dt>Department</dt>
                    <dd>{{ $user->department ?? '—' }}</dd>
                </div>
                <div class="col-12 col-md-6 col-xl-3 dg-permission-summary-item">
                    <dt>Designation</dt>
                    <dd>{{ $user->job_role ?? '—' }}</dd>
                </div>
                <div class="col-12 col-md-6 col-xl-3 dg-permission-summary-item">
                    <dt>Role</dt>
                    <dd>{{ $user->role?->name ?? '—' }}</dd>
                </div>
                <div class="col-12 col-md-6 col-xl-3 dg-permission-summary-item">
                    <dt>Status</dt>
                    <dd>{{ ucfirst($user->account_status ?? '—') }}</dd>
                </div>
                <div class="col-12 col-md-6 col-xl-3 dg-permission-summary-item">
                    <dt>Company</dt>
                    <dd>{{ $user->company?->company_name ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <form
            method="POST"
            action="{{ route('company.staff-permissions.update',$user) }}"
        >

            @csrf
            @method('PUT')

            <div class="card dg-card">

            <div class="card-body dg-card-body">
                @forelse($permissionModules as $moduleName => $modulePermissions)
                    <section class="card dg-card dg-permission-module" aria-labelledby="dgPermissionModule{{ $loop->index }}">
                        <header class="card-header dg-card-header dg-permission-module-header">
                            <h2 class="h6 mb-0" id="dgPermissionModule{{ $loop->index }}">{{ $moduleName }}</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="table-responsive dg-permission-responsive">
                                <table class="table table-bordered table-hover align-middle dg-table dg-permission-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Permission</th>
                                            <th scope="col" class="text-center">Default</th>
                                            <th scope="col" class="text-center">Allow</th>
                                            <th scope="col" class="text-center">Deny</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($modulePermissions as $permission)
                                            @php
                                                $state = array_key_exists($permission->id, $overrides)
                                                    ? ($overrides[$permission->id] ? 'allow' : 'deny')
                                                    : 'default';
                                            @endphp
                                            <tr>
                                                <th scope="row">{{ $permission->name }}</th>
                                                @foreach(['default' => 'Default', 'allow' => 'Allow', 'deny' => 'Deny'] as $value => $label)
                                                    <td class="text-center">
                                                        <div class="form-check form-check-inline dg-permission-choice">
                                                            <input
                                                                class="form-check-input"
                                                                id="permission_{{ $permission->id }}_{{ $value }}"
                                                                type="radio"
                                                                name="permissions[{{ $permission->id }}]"
                                                                value="{{ $value }}"
                                                                {{ $state == $value ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="permission_{{ $permission->id }}_{{ $value }}">{{ $label }}</label>
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                @empty
                    <p class="mb-0 text-muted">No permissions are configured.</p>
                @endforelse
            </div>

            <div class="card-footer dg-card-footer text-end">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    Save Permissions

                </button>

            </div>

            </div>
        </form>

    </div>

</div>
</div>

@endsection
