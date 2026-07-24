@extends('company.layout')

@section('title', 'Edit Opportunity')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Opportunity</h1>
                    <p class="text-muted small mb-0">{{ $opportunity->opportunity_no }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Opportunity toolbar">
                        <a href="{{ route('company.crm-opportunities.show', $opportunity->id) }}" class="btn btn-outline-secondary dg-btn">Back</a>
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

            <form method="POST" action="{{ route('company.crm-opportunities.update', $opportunity->id) }}" class="dg-form">
                @csrf
                @method('PUT')

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Opportunity Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" id="title" class="form-control dg-input" value="{{ old('title', $opportunity->title) }}" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="crm_lead_id" class="form-label">Customer Relationship <span class="text-danger">*</span></label>
                                    <select name="crm_lead_id" id="crm_lead_id" class="form-select dg-select" required>
                                        <option value="">Select Relationship</option>
                                        @foreach ($leads as $lead)
                                            <option value="{{ $lead->id }}" @selected(old('crm_lead_id', $opportunity->crm_lead_id) == $lead->id)>
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
                                            <option value="{{ $employee->id }}" @selected(old('assigned_employee_id', $opportunity->assigned_employee_id) == $employee->id)>
                                                {{ $employee->employee_code }} — {{ $employee->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="stage" class="form-label">Stage <span class="text-danger">*</span></label>
                                    <select name="stage" id="stage" class="form-select dg-select" required>
                                        @foreach ($stageOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected(old('stage', $opportunity->stage) == $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="potential_value" class="form-label">Potential Value</label>
                                    <input type="number" name="potential_value" id="potential_value" step="0.01" min="0" class="form-control dg-input" value="{{ old('potential_value', $opportunity->potential_value) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="expected_closing_date" class="form-label">Expected Closing Date</label>
                                    <input type="date" name="expected_closing_date" id="expected_closing_date" class="form-control dg-input" value="{{ old('expected_closing_date', $opportunity->expected_closing_date?->format('Y-m-d')) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="probability" class="form-label">Probability (%)</label>
                                    <input type="number" name="probability" id="probability" step="0.01" min="0" max="100" class="form-control dg-input" value="{{ old('probability', $opportunity->probability) }}">
                                </div>

                                <div class="col-12">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea name="remarks" id="remarks" rows="3" class="form-control dg-input">{{ old('remarks', $opportunity->remarks) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Opportunity</button>
                            <a href="{{ route('company.crm-opportunities.show', $opportunity->id) }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                        </footer>
                    </article>
                </section>
            </form>

        </div>
    </main>
</div>

@endsection
