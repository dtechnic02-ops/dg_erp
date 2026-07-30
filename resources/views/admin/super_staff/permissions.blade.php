@extends('admin.layout')

@section('title', 'Super Staff Permissions')

@section('content')
    <div class="dg-page">
        <header class="dg-toolbar">
            <div class="container-fluid d-flex justify-content-between align-items-center gap-2">
                <div>
                    <h1 class="h4 mb-1">Platform Permissions</h1>
                    <p class="mb-0 text-muted">{{ $user->name }} · {{ $user->email }}</p>
                </div>
                <a href="{{ route('admin.super-staff.show', $user) }}" class="btn btn-outline-secondary dg-btn">Back</a>
            </div>
        </header>

        <main class="dg-container">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
                @endif
                @if($errors->has('permissions'))
                    <div class="alert alert-danger dg-alert" role="alert">{{ $errors->first('permissions') }}</div>
                @endif

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header d-flex justify-content-between align-items-center">
                            <h2 class="h6 mb-0">Individual Platform Permissions</h2>
                            <span class="badge text-bg-secondary">{{ count($assignedPermissionIds) }} assigned</span>
                        </header>
                        <div class="card-body dg-card-body">
                            <p class="text-muted small">Only the approved safe platform permissions are available. Unchecked allowed permissions are removed; company permissions are never shown or changed.</p>

                            <form method="POST" action="{{ route('admin.super-staff.permissions.update', $user) }}" class="dg-form">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    @forelse($permissionGroups as $module => $permissions)
                                        <div class="col-md-6 col-xl-4">
                                            <article class="card h-100 border">
                                                <header class="card-header bg-transparent">
                                                    <h3 class="h6 mb-0">{{ $module }}</h3>
                                                </header>
                                                <div class="card-body">
                                                    @foreach($permissions as $permission)
                                                        <div class="form-check mb-2">
                                                            <input
                                                                class="form-check-input"
                                                                type="checkbox"
                                                                name="permissions[]"
                                                                value="{{ $permission->id }}"
                                                                id="permission_{{ $permission->id }}"
                                                                @checked(in_array($permission->id, old('permissions', $assignedPermissionIds)))
                                                            >
                                                            <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                                {{ ucwords(str_replace('_', ' ', $permission->name)) }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </article>
                                        </div>
                                    @empty
                                        <div class="col-12"><p class="text-muted mb-0">No platform permissions are available.</p></div>
                                    @endforelse
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary dg-btn">Save Platform Permissions</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>
@endsection
