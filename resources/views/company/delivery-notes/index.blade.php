@extends('company.layout')

@section('title', 'Delivery Notes')

@section('content')

@php
    $user = auth()->user();
    $canCreate = $user && ((int) $user->role_id === \App\Models\Role::COMPANY_ADMIN_ID || $user->hasPermission('create_delivery'));
    $canProcess = $user && ((int) $user->role_id === \App\Models\Role::COMPANY_ADMIN_ID || $user->hasPermission('process_delivery'));

    $statusOptions = [
        '' => 'Active (Exclude Cancelled)',
        'all' => 'All',
        \App\Models\DeliveryNote::STATUS_READY => 'Ready',
        \App\Models\DeliveryNote::STATUS_DELIVERED => 'Delivered',
        \App\Models\DeliveryNote::STATUS_PARTIAL => 'Partial',
        \App\Models\DeliveryNote::STATUS_REJECTED => 'Rejected',
        \App\Models\DeliveryNote::STATUS_CANCELLED => 'Cancelled',
    ];

    $selectedStatus = request()->has('status') ? request('status') : '';
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Delivery Notes</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-nowrap">
                    @if ($canCreate)
                        <a href="{{ route('company.delivery-notes.create') }}" class="btn btn-success dg-btn">New Delivery</a>
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

            <section class="dg-section">
                <div class="dg-summary d-flex flex-row flex-nowrap justify-content-center align-items-center gap-3 mb-0 w-100">
                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Active Deliveries :</span>
                        <span class="fw-bold">{{ $activeCount }}</span>
                    </div>

                    <span>|</span>

                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Cancelled :</span>
                        <span class="fw-bold">{{ $cancelledCount }}</span>
                    </div>

                    <span>|</span>

                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Listed Records :</span>
                        <span class="fw-bold">{{ $totalCount }}</span>
                    </div>
                </div>
            </section>

            <section class="dg-section dg-filter">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>
                    <div class="card-body dg-card-body dg-filter-card-body">
                        <form method="GET" action="{{ route('company.delivery-notes.index') }}" class="dg-filter-form">
                            <div class="dg-filter-grid">
                                <div class="dg-filter-field">
                                    <label for="search" class="dg-filter-label">Search</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input dg-filter-control" value="{{ request('search') }}" placeholder="Delivery No / Customer / Invoice">
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

                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="start_date" class="dg-filter-label">Date From</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control dg-input dg-filter-control" value="{{ request('start_date') }}">
                                </div>

                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="end_date" class="dg-filter-label">Date To</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control dg-input dg-filter-control" value="{{ request('end_date') }}">
                                </div>

                                <div class="dg-filter-field dg-filter-field-customer">
                                    <label for="customer_id" class="dg-filter-label">Customer</label>
                                    <select name="customer_id" id="customer_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Customers</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-customer">
                                    <label for="employee_id" class="dg-filter-label">Employee</label>
                                    <select name="employee_id" id="employee_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Employees</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>
                                                {{ $employee->employee_code }} — {{ trim($employee->first_name . ' ' . ($employee->middle_name ?? '') . ' ' . ($employee->last_name ?? '')) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="status" class="dg-filter-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select dg-filter-control">
                                        @foreach ($statusOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($selectedStatus === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if (request('per_page'))
                                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                @endif

                                <div class="dg-filter-actions">
                                    <button type="submit" class="btn btn-primary dg-btn">Filter</button>
                                    <a href="{{ route('company.delivery-notes.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Delivery List</h2>

                        <form method="GET" action="{{ route('company.delivery-notes.index') }}" class="dg-list-per-page">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="financial_year_id" value="{{ request('financial_year_id', $activeFy?->id) }}">
                            <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                            <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                            <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
                            <input type="hidden" name="employee_id" value="{{ request('employee_id') }}">
                            <input type="hidden" name="status" value="{{ request()->has('status') ? request('status') : '' }}">

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
                                        <th scope="col">Delivery No</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Invoice</th>
                                        <th scope="col">Employee</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="180">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($deliveryNotes as $deliveryNote)
                                        <tr class="dg-row">
                                            <td>{{ $deliveryNotes->firstItem() + $loop->index }}</td>
                                            <td>{{ $deliveryNote->delivery_no }}</td>
                                            <td>{{ $deliveryNote->delivery_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>{{ $deliveryNote->customer->name ?? '-' }}</td>
                                            <td>{{ $deliveryNote->salesInvoice->invoice_no ?? '-' }}</td>
                                            <td>{{ $deliveryNote->employee->full_name ?? '-' }}</td>
                                            <td>@include('company.delivery-notes.partials.status-badge', ['deliveryNote' => $deliveryNote])</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('company.delivery-notes.show', $deliveryNote->id) }}" class="btn btn-sm btn-outline-info dg-btn">View</a>
                                                    @if ($canProcess && $deliveryNote->isProcessable())
                                                        <a href="{{ route('company.delivery-notes.process', $deliveryNote->id) }}" class="btn btn-sm btn-outline-success dg-btn">Process</a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="8" class="text-center">No delivery records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $deliveryNotes->firstItem() ?? 0 }} to {{ $deliveryNotes->lastItem() ?? 0 }} of {{ $deliveryNotes->total() }} records
                            </p>
                            {{ $deliveryNotes->links() }}
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@endsection
