@extends('company.layout')

@section('title', 'Staff Permissions')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Staff Permissions</h1>
                </div>
                <div class="flex-fill d-flex justify-content-end">
                    <a href="{{ route('company.users.index') }}" class="btn btn-outline-secondary dg-btn">Back to Staff</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if(session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger dg-alert" role="alert">{{ $errors->first() }}</div>
            @endif

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Company Staff Permission Template</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <p class="text-muted small">Only company-scope permissions are shown. Platform permissions are never assignable from this screen.</p>

                        <form method="POST" action="{{ route('company.permissions.update') }}">
                            @csrf

                            <div class="row">
                                @forelse($permissions as $permission)
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->id }}"
                                                id="permission_{{ $permission->id }}"
                                                @checked(in_array($permission->id, $assignedPermissionIds, true))
                                            >
                                            <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="mb-0">No company permissions are configured.</p>
                                    </div>
                                @endforelse
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary dg-btn">Save Permissions</button>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@endsection
