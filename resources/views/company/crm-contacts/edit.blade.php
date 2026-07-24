@extends('company.layout')

@section('title', 'Edit Contact')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Contact</h1>
                    <p class="text-muted small mb-0">{{ $contact->contact_no }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Contact toolbar">
                        <a href="{{ route('company.crm-contacts.show', $contact->id) }}" class="btn btn-outline-secondary dg-btn">Back</a>
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

            <form method="POST" action="{{ route('company.crm-contacts.update', $contact->id) }}" class="dg-form">
                @csrf
                @method('PUT')

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Contact Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Person Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control dg-input" value="{{ old('name', $contact->name) }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="designation" class="form-label">Designation</label>
                                    <input type="text" name="designation" id="designation" class="form-control dg-input" value="{{ old('designation', $contact->designation) }}">
                                </div>

                                <div class="col-md-6">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" name="department" id="department" class="form-control dg-input" value="{{ old('department', $contact->department) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="mobile" class="form-label">Mobile</label>
                                    <input type="text" name="mobile" id="mobile" class="form-control dg-input" value="{{ old('mobile', $contact->mobile) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="text" name="phone" id="phone" class="form-control dg-input" value="{{ old('phone', $contact->phone) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" name="email" id="email" class="form-control dg-input" value="{{ old('email', $contact->email) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="contact_date" class="form-label">Contact Date <span class="text-danger">*</span></label>
                                    <input type="date" name="contact_date" id="contact_date" class="form-control dg-input" value="{{ old('contact_date', $contact->contact_date?->format('Y-m-d')) }}" required>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Customer &amp; Assignment</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                @include('company.crm.partials.customer-select', [
                                    'customers' => $customers,
                                    'selectedCustomerId' => old('customer_id', $contact->customer_id),
                                ])

                                <div class="col-md-6">
                                    <label for="crm_lead_id" class="form-label">Customer Relationship</label>
                                    <select name="crm_lead_id" id="crm_lead_id" class="form-select dg-select">
                                        <option value="">Select Relationship (Optional)</option>
                                        @foreach ($leads as $lead)
                                            <option value="{{ $lead->id }}" @selected(old('crm_lead_id', $contact->crm_lead_id) == $lead->id)>
                                                {{ $lead->lead_no }} — {{ $lead->customer?->name ?? '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="assigned_employee_id" class="form-label">Assigned Employee <span class="text-danger">*</span></label>
                                    <select name="assigned_employee_id" id="assigned_employee_id" class="form-select dg-select" required>
                                        <option value="">Select Employee</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}" @selected(old('assigned_employee_id', $contact->assigned_employee_id) == $employee->id)>
                                                {{ $employee->employee_code }} — {{ $employee->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-select dg-select" required>
                                        @foreach ($statusOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected(old('status', $contact->status) == $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                    <select name="priority" id="priority" class="form-select dg-select" required>
                                        @foreach ($priorityOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected(old('priority', $contact->priority) == $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea name="remarks" id="remarks" rows="3" class="form-control dg-input">{{ old('remarks', $contact->remarks) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Contact</button>
                            <a href="{{ route('company.crm-contacts.show', $contact->id) }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                        </footer>
                    </article>
                </section>
            </form>

        </div>
    </main>
</div>

@endsection
