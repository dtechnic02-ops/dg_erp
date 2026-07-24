@extends('company.layout')

@section('title', 'Edit Follow-up')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Follow-up</h1>
                    <p class="text-muted small mb-0">{{ $followUp->activity_no }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Follow-up toolbar">
                        <a href="{{ route('company.crm-follow-ups.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
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

            <form method="POST" action="{{ route('company.crm-follow-ups.update', $followUp->id) }}" class="dg-form">
                @csrf
                @method('PUT')

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Follow-up Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="follow_up_date" class="form-label">Follow-up Date <span class="text-danger">*</span></label>
                                    <input type="date" name="follow_up_date" id="follow_up_date" class="form-control dg-input" value="{{ old('follow_up_date', $followUp->follow_up_date?->format('Y-m-d')) }}" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="next_follow_up_date" class="form-label">Next Follow-up Date</label>
                                    <input type="date" name="next_follow_up_date" id="next_follow_up_date" class="form-control dg-input" value="{{ old('next_follow_up_date', $followUp->next_follow_up_date?->format('Y-m-d')) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="assigned_employee_id" class="form-label">Assigned Employee <span class="text-danger">*</span></label>
                                    <select name="assigned_employee_id" id="assigned_employee_id" class="form-select dg-select" required>
                                        <option value="">Select Employee</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}" @selected(old('assigned_employee_id', $followUp->assigned_employee_id) == $employee->id)>
                                                {{ $employee->employee_code }} — {{ $employee->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                    <select name="priority" id="priority" class="form-select dg-select" required>
                                        @foreach ($priorityOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected(old('priority', $followUp->priority) == $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-select dg-select" required>
                                        @foreach ($statusOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected(old('status', $followUp->status) == $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea name="remarks" id="remarks" rows="3" class="form-control dg-input">{{ old('remarks', $followUp->remarks) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Follow-up</button>
                            <a href="{{ route('company.crm-follow-ups.index') }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                        </footer>
                    </article>
                </section>
            </form>

        </div>
    </main>
</div>

@endsection
