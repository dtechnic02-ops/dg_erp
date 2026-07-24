@extends('company.layout')

@section('title', 'CRM Opportunities')

@section('content')

@php
    $selectedStatus = request()->has('status') ? request('status') : '';
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">CRM Opportunities</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-nowrap">
                    <a href="{{ route('company.crm.dashboard.index') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                    @if (userCan('create_crm_opportunity'))
                        <a href="{{ route('company.crm-opportunities.create') }}" class="btn btn-success dg-btn">New Opportunity</a>
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
                        <form method="GET" action="{{ route('company.crm-opportunities.index') }}" class="dg-filter-form">
                            <div class="dg-filter-grid">
                                <div class="dg-filter-field">
                                    <label for="search" class="dg-filter-label">Search</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input dg-filter-control" value="{{ request('search') }}" placeholder="Opportunity No / Title / Customer">
                                </div>

                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="stage" class="dg-filter-label">Stage</label>
                                    <select name="stage" id="stage" class="form-select dg-select dg-filter-control">
                                        <option value="">All Stages</option>
                                        @foreach ($stageOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected(request('stage') == $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="status" class="dg-filter-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select dg-filter-control">
                                        <option value="" @selected($selectedStatus === '')>Active Opportunities</option>
                                        <option value="open" @selected($selectedStatus === 'open')>Open</option>
                                        <option value="won" @selected($selectedStatus === 'won')>Won</option>
                                        <option value="lost" @selected($selectedStatus === 'lost')>Lost</option>
                                        <option value="closed" @selected($selectedStatus === 'closed')>Closed</option>
                                        <option value="archived" @selected($selectedStatus === 'archived')>Archived</option>
                                        <option value="cancelled" @selected($selectedStatus === 'cancelled')>Cancelled</option>
                                    </select>
                                </div>

                                <div class="dg-filter-actions">
                                    <button type="submit" class="btn btn-primary dg-btn">Filter</button>
                                    <a href="{{ route('company.crm-opportunities.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Opportunity List</h2>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Opportunity No</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Relationship</th>
                                        <th scope="col">Employee</th>
                                        <th scope="col">Stage</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-end">Potential Value</th>
                                        <th scope="col" width="100">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($opportunities as $opportunity)
                                        @php
                                            $stageLabel = $stageOptions->firstWhere('config_key', $opportunity->stage)?->config_label ?? ucfirst(str_replace('_', ' ', $opportunity->stage));
                                        @endphp
                                        <tr class="dg-row">
                                            <td>{{ $opportunities->firstItem() + $loop->index }}</td>
                                            <td>{{ $opportunity->opportunity_no }}</td>
                                            <td>{{ $opportunity->title }}</td>
                                            <td>{{ $opportunity->customer->name ?? '-' }}</td>
                                            <td>{{ $opportunity->lead->lead_no ?? '-' }}</td>
                                            <td>{{ $opportunity->assignedEmployee->full_name ?? '-' }}</td>
                                            <td><span class="badge bg-info text-dark">{{ $stageLabel }}</span></td>
                                            <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $opportunity->status)) }}</span></td>
                                            <td class="text-end">{{ number_format($opportunity->potential_value, 2) }}</td>
                                            <td>
                                                <a href="{{ route('company.crm-opportunities.show', $opportunity->id) }}" class="btn btn-sm btn-outline-info dg-btn">View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="10" class="text-center">No opportunity records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $opportunities->firstItem() ?? 0 }} to {{ $opportunities->lastItem() ?? 0 }} of {{ $opportunities->total() }} records
                            </p>
                            {{ $opportunities->links() }}
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@endsection
