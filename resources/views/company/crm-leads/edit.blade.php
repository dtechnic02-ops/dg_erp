@extends('company.layout')



@section('title', 'Edit Customer Relationship')



@section('content')



<div class="dg-page">



    <header class="dg-toolbar">

        <div class="container-fluid">

            <div class="row align-items-center g-2">

                <div class="col">

                    <h1 class="h4 mb-0">Edit Customer Relationship</h1>

                    <p class="text-muted small mb-0">{{ $lead->lead_no }}</p>

                </div>

                <div class="col-auto">

                    <nav class="btn-group" aria-label="Relationship toolbar">

                        <a href="{{ route('company.crm-leads.show', $lead->id) }}" class="btn btn-outline-secondary dg-btn">Back</a>

                    </nav>

                </div>

            </div>

        </div>

    </header>



    <main class="dg-container">

        <div class="container-fluid">



            @if ($errors->any())

                <div class="alert alert-danger dg-alert" role="alert">

                    <ul class="mb-0">

                        @foreach ($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif



            @if (session('error'))

                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>

            @endif



            <form method="POST" action="{{ route('company.crm-leads.update', $lead->id) }}" class="dg-form">

                @csrf



                <section class="dg-section">

                    <article class="card dg-card">

                        <header class="card-header dg-card-header">

                            <h2 class="h6 mb-0">Customer Reference</h2>

                        </header>

                        <div class="card-body dg-card-body">

                            <div class="row g-3">

                                @include('company.crm.partials.customer-select', [

                                    'customers' => $customers,

                                    'selectedCustomerId' => old('customer_id', $lead->customer_id),

                                ])

                            </div>

                        </div>

                    </article>

                </section>



                <section class="dg-section">

                    <article class="card dg-card">

                        <header class="card-header dg-card-header">

                            <h2 class="h6 mb-0">Relationship Details</h2>

                        </header>

                        <div class="card-body dg-card-body">

                            <div class="row g-3">

                                <div class="col-md-4">

                                    <label for="assigned_employee_id" class="form-label">Assigned Employee <span class="text-danger">*</span></label>

                                    <select name="assigned_employee_id" id="assigned_employee_id" class="form-select dg-select" required>

                                        <option value="">Select Employee</option>

                                        @foreach ($employees as $employee)

                                            <option value="{{ $employee->id }}" @selected(old('assigned_employee_id', $lead->assigned_employee_id) == $employee->id)>

                                                {{ $employee->employee_code }} — {{ $employee->full_name }}

                                            </option>

                                        @endforeach

                                    </select>

                                </div>



                                <div class="col-md-4">

                                    <label for="lead_date" class="form-label">Relationship Date <span class="text-danger">*</span></label>

                                    <input type="date" name="lead_date" id="lead_date" class="form-control dg-input" value="{{ old('lead_date', $lead->lead_date?->format('Y-m-d')) }}" required>

                                </div>



                                <div class="col-md-4">

                                    <label for="expected_value" class="form-label">Expected Value</label>

                                    <input type="number" name="expected_value" id="expected_value" step="0.01" min="0" class="form-control dg-input" value="{{ old('expected_value', $lead->expected_value) }}">

                                </div>



                                <div class="col-md-4">

                                    <label for="status" class="form-label">Relationship Status <span class="text-danger">*</span></label>

                                    <select name="status" id="status" class="form-select dg-select" required>

                                        @foreach ($statusOptions as $option)

                                            <option value="{{ $option->config_key }}" @selected(old('status', $lead->status) == $option->config_key)>{{ $option->config_label }}</option>

                                        @endforeach

                                    </select>

                                </div>



                                <div class="col-md-4">

                                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>

                                    <select name="priority" id="priority" class="form-select dg-select" required>

                                        @foreach ($priorityOptions as $option)

                                            <option value="{{ $option->config_key }}" @selected(old('priority', $lead->priority) == $option->config_key)>{{ $option->config_label }}</option>

                                        @endforeach

                                    </select>

                                </div>



                                <div class="col-12">

                                    <label for="remarks" class="form-label">Remarks</label>

                                    <textarea name="remarks" id="remarks" rows="3" class="form-control dg-input">{{ old('remarks', $lead->remarks) }}</textarea>

                                </div>

                            </div>

                        </div>

                        <footer class="card-footer dg-card-footer">

                            <button type="submit" class="btn btn-primary dg-btn">Save Relationship</button>

                            <a href="{{ route('company.crm-leads.show', $lead->id) }}" class="btn btn-outline-secondary dg-btn">Cancel</a>

                        </footer>

                    </article>

                </section>

            </form>



        </div>

    </main>

</div>



@endsection


