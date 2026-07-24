@extends('company.layout')

@section('title', 'Customer Relationships')

@section('content')

@php
    $selectedStatus = request()->has('status') ? request('status') : '';
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Customer Relationships</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-nowrap">
                    <a href="{{ route('company.crm.dashboard.index') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                    @if (userCan('create_crm_lead'))
                        <a href="{{ route('company.crm-leads.create') }}" class="btn btn-success dg-btn">New Relationship</a>
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
                        <form method="GET" action="{{ route('company.crm-leads.index') }}" class="dg-filter-form">
                            <div class="dg-filter-grid">
                                <div class="dg-filter-field">
                                    <label for="search" class="dg-filter-label">Search</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input dg-filter-control" value="{{ request('search') }}" placeholder="Relationship No / Customer / Mobile / Email">
                                </div>

                                <div class="dg-filter-field dg-filter-field-fy">
                                    <label for="financial_year_id" class="dg-filter-label">Financial Year</label>
                                    <select name="financial_year_id" id="financial_year_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Years</option>
                                        @foreach ($financialYears as $financialYear)
                                            <option value="{{ $financialYear->id }}" @selected(
                                                request()->has('financial_year_id')
                                                    ? request('financial_year_id') == $financialYear->id
                                                    : ($activeFy && $activeFy->id == $financialYear->id)
                                            )>{{ $financialYear->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-customer">
                                    <label for="employee_id" class="dg-filter-label">Employee</label>
                                    <select name="employee_id" id="employee_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Employees</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>
                                                {{ $employee->employee_code }} — {{ $employee->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="status" class="dg-filter-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select dg-filter-control">
                                        <option value="" @selected($selectedStatus === '')>Active Relationships</option>
                                        @foreach ($statusOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected($selectedStatus === $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="priority" class="dg-filter-label">Priority</label>
                                    <select name="priority" id="priority" class="form-select dg-select dg-filter-control">
                                        <option value="">All Priorities</option>
                                        @foreach ($priorityOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected(request('priority') == $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="start_date" class="dg-filter-label">Date From</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control dg-input dg-filter-control" value="{{ request('start_date') }}">
                                </div>

                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="end_date" class="dg-filter-label">Date To</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control dg-input dg-filter-control" value="{{ request('end_date') }}">
                                </div>

                                @if (request('per_page'))
                                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                @endif

                                <div class="dg-filter-actions">
                                    <button type="submit" class="btn btn-primary dg-btn">Filter</button>
                                    <a href="{{ route('company.crm-leads.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Customer Relationships</h2>

                        <form method="GET" action="{{ route('company.crm-leads.index') }}" class="dg-list-per-page">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="financial_year_id" value="{{ request('financial_year_id', $activeFy?->id) }}">
                            <input type="hidden" name="employee_id" value="{{ request('employee_id') }}">
                            <input type="hidden" name="status" value="{{ request()->has('status') ? request('status') : '' }}">
                            <input type="hidden" name="priority" value="{{ request('priority') }}">
                            <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                            <input type="hidden" name="end_date" value="{{ request('end_date') }}">

                            <label for="per_page" class="dg-list-per-page-label">Show</label>
                            <select name="per_page" id="per_page" class="form-select dg-select dg-list-per-page-select" onchange="this.form.submit()">
                                <option value="10" @selected($perPage == 10)>10</option>
                                <option value="20" @selected($perPage == 20)>20</option>
                                <option value="50" @selected($perPage == 50)>50</option>
                                <option value="100" @selected($perPage == 100)>100</option>
                            </select>
                        </form>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Relationship No</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Mobile</th>
                                        <th scope="col">Employee</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Priority</th>
                                        <th scope="col" width="160">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($leads as $lead)
                                        @php
                                            $statusLabel = $statusOptions->firstWhere('config_key', $lead->status)?->config_label ?? ucfirst(str_replace('_', ' ', $lead->status));
                                            $priorityLabel = $priorityOptions->firstWhere('config_key', $lead->priority)?->config_label ?? ucfirst(str_replace('_', ' ', $lead->priority));
                                        @endphp
                                        <tr class="dg-row">
                                            <td>{{ $leads->firstItem() + $loop->index }}</td>
                                            <td>{{ $lead->lead_no }}</td>
                                            <td>{{ $lead->lead_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>{{ $lead->customer->name ?? '-' }}</td>
                                            <td>{{ $lead->customer->mobile ?? '-' }}</td>
                                            <td>{{ $lead->assignedEmployee->full_name ?? '-' }}</td>
                                            <td><span class="badge bg-secondary">{{ $statusLabel }}</span></td>
                                            <td><span class="badge bg-light text-dark border">{{ $priorityLabel }}</span></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('company.crm-leads.show', $lead->id) }}" class="btn btn-sm btn-outline-info dg-btn">View</a>
                                                    @if (userCan('edit_crm_lead') && $lead->isEditable($terminalKeys))
                                                        <a href="{{ route('company.crm-leads.edit', $lead->id) }}" class="btn btn-sm btn-outline-primary dg-btn">Edit</a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No customer relationship records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $leads->firstItem() ?? 0 }} to {{ $leads->lastItem() ?? 0 }} of {{ $leads->total() }} records
                            </p>
                            {{ $leads->links() }}
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@endsection
