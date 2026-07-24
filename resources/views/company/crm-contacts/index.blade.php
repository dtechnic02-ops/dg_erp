@extends('company.layout')

@section('title', 'Customer Contacts')

@section('content')

@php
    $selectedStatus = request()->has('status') ? request('status') : '';
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Customer Contacts</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-nowrap">
                    <a href="{{ route('company.crm.dashboard.index') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                    @if (userCan('create_crm_contact'))
                        <a href="{{ route('company.crm-contacts.create') }}" class="btn btn-success dg-btn">New Contact</a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <section class="dg-section dg-filter">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>
                    <div class="card-body dg-card-body dg-filter-card-body">
                        <form method="GET" action="{{ route('company.crm-contacts.index') }}" class="dg-filter-form">
                            <div class="dg-filter-grid">
                                <div class="dg-filter-field">
                                    <label for="search" class="dg-filter-label">Search</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input dg-filter-control" value="{{ request('search') }}" placeholder="Contact No / Name / Mobile / Email">
                                </div>

                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="status" class="dg-filter-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select dg-filter-control">
                                        <option value="" @selected($selectedStatus === '')>Active Contacts</option>
                                        @foreach ($statusOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected($selectedStatus === $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-actions">
                                    <button type="submit" class="btn btn-primary dg-btn">Filter</button>
                                    <a href="{{ route('company.crm-contacts.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Contact List</h2>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Contact No</th>
                                        <th scope="col">Person Name</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Mobile</th>
                                        <th scope="col">Employee</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Contact Date</th>
                                        <th scope="col" width="100">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($contacts as $contact)
                                        @php
                                            $statusLabel = $statusOptions->firstWhere('config_key', $contact->status)?->config_label ?? ucfirst(str_replace('_', ' ', $contact->status));
                                        @endphp
                                        <tr class="dg-row">
                                            <td>{{ $contacts->firstItem() + $loop->index }}</td>
                                            <td>{{ $contact->contact_no }}</td>
                                            <td>{{ $contact->name }}</td>
                                            <td>{{ $contact->customer->name ?? '-' }}</td>
                                            <td>{{ $contact->mobile ?: '-' }}</td>
                                            <td>{{ $contact->assignedEmployee->full_name ?? '-' }}</td>
                                            <td><span class="badge bg-secondary">{{ $statusLabel }}</span></td>
                                            <td>{{ $contact->contact_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>
                                                <a href="{{ route('company.crm-contacts.show', $contact->id) }}" class="btn btn-sm btn-outline-info dg-btn">View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No contact records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $contacts->firstItem() ?? 0 }} to {{ $contacts->lastItem() ?? 0 }} of {{ $contacts->total() }} records
                            </p>
                            {{ $contacts->links() }}
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@endsection
