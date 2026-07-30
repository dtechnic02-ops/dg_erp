@extends('admin.layout')

@section('title', 'Super Staff Details')

@section('header-actions')
    <a href="{{ route('admin.super-staff.edit', $user) }}" class="btn btn-outline-warning dg-btn">Edit</a>
    <a href="{{ route('admin.super-staff.permissions.edit', $user) }}" class="btn btn-outline-info dg-btn">Permissions</a>
@endsection

@section('content')
    <div class="dg-page">
        <header class="dg-toolbar">
            <div class="container-fluid d-flex justify-content-between align-items-center gap-2">
                <h1 class="h4 mb-0">Super Staff Details</h1>
                <a href="{{ route('admin.super-staff.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
            </div>
        </header>

        <main class="dg-container">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
                @endif
                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header"><h2 class="h6 mb-0">Account Information</h2></header>
                        <div class="card-body dg-card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $user->name }}</dd>
                                <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $user->email }}</dd>
                                <dt class="col-sm-3">Role</dt><dd class="col-sm-9">Super Staff</dd>
                                <dt class="col-sm-3">Company</dt><dd class="col-sm-9">Platform user</dd>
                                <dt class="col-sm-3">Status</dt>
                                <dd class="col-sm-9"><span class="badge {{ $user->account_status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($user->account_status) }}</span></dd>
                            </dl>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>
@endsection
