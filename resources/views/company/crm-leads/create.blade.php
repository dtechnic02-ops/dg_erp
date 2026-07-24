@extends('company.layout')



@section('title', 'Create Customer Relationship')



@section('content')



<div class="dg-page">



    <header class="dg-toolbar">

        <div class="container-fluid">

            <div class="row align-items-center g-2">

                <div class="col">

                    <h1 class="h4 mb-0">Create Customer Relationship</h1>

                    <p class="text-muted small mb-0">Link an existing customer and track the ongoing business relationship</p>

                </div>

                <div class="col-auto">

                    <nav class="btn-group" aria-label="Relationship toolbar">

                        <a href="{{ route('company.crm-leads.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>

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



            @if ($activeFy)

                <div class="alert alert-info dg-alert" role="alert">

                    Active Financial Year: <strong>{{ $activeFy->name }}</strong>

                    ({{ \Illuminate\Support\Carbon::parse($activeFy->start_date)->format('d-m-Y') }} to {{ \Illuminate\Support\Carbon::parse($activeFy->end_date)->format('d-m-Y') }})

                </div>

            @endif



            <form method="POST" action="{{ route('company.crm-leads.store') }}" class="dg-form">

                @csrf



                <section class="dg-section">

                    <article class="card dg-card">

                        <header class="card-header dg-card-header">

                            <h2 class="h6 mb-0">Customer Reference</h2>

                        </header>

                        <div class="card-body dg-card-body">

                            <div class="row g-3">

                                @include('company.crm.partials.customer-select', ['customers' => $customers])

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

                                            <option value="{{ $employee->id }}" @selected(old('assigned_employee_id') == $employee->id)>

                                                {{ $employee->employee_code }} — {{ $employee->full_name }}

                                            </option>

                                        @endforeach

                                    </select>

                                </div>



                                <div class="col-md-4">

                                    <label for="lead_date" class="form-label">Relationship Date <span class="text-danger">*</span></label>

                                    <input type="date" name="lead_date" id="lead_date" class="form-control dg-input" value="{{ old('lead_date', date('Y-m-d')) }}" required>

                                </div>



                                <div class="col-md-4">

                                    <label for="expected_value" class="form-label">Expected Value</label>

                                    <input type="number" name="expected_value" id="expected_value" step="0.01" min="0" class="form-control dg-input" value="{{ old('expected_value', 0) }}">

                                </div>



                                <div class="col-md-4">

                                    <label for="status" class="form-label">Relationship Status <span class="text-danger">*</span></label>

                                    <select name="status" id="status" class="form-select dg-select" required>

                                        @foreach ($statusOptions as $option)

                                            <option value="{{ $option->config_key }}" @selected(old('status', $defaultStatus) == $option->config_key)>{{ $option->config_label }}</option>

                                        @endforeach

                                    </select>

                                </div>



                                <div class="col-md-4">

                                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>

                                    <select name="priority" id="priority" class="form-select dg-select" required>

                                        @foreach ($priorityOptions as $option)

                                            <option value="{{ $option->config_key }}" @selected(old('priority', 'normal') == $option->config_key)>{{ $option->config_label }}</option>

                                        @endforeach

                                    </select>

                                </div>



                                <div class="col-12">

                                    <label for="remarks" class="form-label">Remarks</label>

                                    <textarea name="remarks" id="remarks" rows="3" class="form-control dg-input">{{ old('remarks') }}</textarea>

                                </div>

                            </div>

                        </div>

                        <footer class="card-footer dg-card-footer">

                            <button type="submit" class="btn btn-primary dg-btn">Save Relationship</button>

                            <a href="{{ route('company.crm-leads.index') }}" class="btn btn-outline-secondary dg-btn">Cancel</a>

                        </footer>

                    </article>

                </section>

            </form>



        </div>

    </main>

</div>



@endsection


