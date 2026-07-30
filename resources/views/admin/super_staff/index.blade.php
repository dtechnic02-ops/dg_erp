@extends('admin.layout')

@section('title', 'Super Staff')

@section('header-actions')
    <a href="{{ route('admin.super-staff.create') }}" class="btn btn-primary dg-btn">Add Super Staff</a>
@endsection

@section('content')
    <div class="dg-page">
        <header class="dg-toolbar">
            <div class="container-fluid">
                <h1 class="h4 mb-0">Super Staff Management</h1>
            </div>
        </header>

        <main class="dg-container">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
                @endif

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header d-flex justify-content-between align-items-center">
                            <h2 class="h6 mb-0">Super Staff List</h2>
                            <span class="badge text-bg-secondary">{{ $superStaff->total() }}</span>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="table-responsive">
                                <table class="table dg-table mb-0">
                                    <thead class="dg-head">
                                        <tr>
                                            <th scope="col">Name</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Status</th>
                                            <th scope="col" class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="dg-body">
                                        @forelse($superStaff as $staff)
                                            <tr class="dg-row">
                                                <td>{{ $staff->name }}</td>
                                                <td>{{ $staff->email }}</td>
                                                <td>
                                                    <span class="badge {{ $staff->account_status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                        {{ ucfirst($staff->account_status) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                                        <a href="{{ route('admin.super-staff.show', $staff) }}" class="btn btn-sm btn-outline-primary dg-btn">View</a>
                                                        <a href="{{ route('admin.super-staff.edit', $staff) }}" class="btn btn-sm btn-outline-warning dg-btn">Edit</a>
                                                        <a href="{{ route('admin.super-staff.permissions.edit', $staff) }}" class="btn btn-sm btn-outline-info dg-btn">Permissions</a>
                                                        @if($staff->account_status === 'active')
                                                            <form method="POST" action="{{ route('admin.super-staff.block', $staff) }}">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-secondary dg-btn" onclick="return confirm('Block this Super Staff user?')">Block</button>
                                                            </form>
                                                        @else
                                                            <form method="POST" action="{{ route('admin.super-staff.unblock', $staff) }}">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-success dg-btn" onclick="return confirm('Unblock this Super Staff user?')">Unblock</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="dg-row">
                                                <td colspan="4" class="text-center text-muted py-4">No Super Staff users found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @if($superStaff->hasPages())
                            <footer class="card-footer dg-card-footer">{{ $superStaff->links() }}</footer>
                        @endif
                    </article>
                </section>
            </div>
        </main>
    </div>
@endsection
