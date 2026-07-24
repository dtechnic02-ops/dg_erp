@extends('company.layout')

@section('title', 'Edit Meeting')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Meeting</h1>
                    <p class="text-muted small mb-0">{{ $meeting->activity_no }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Meeting toolbar">
                        <a href="{{ route('company.crm-meetings.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
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

            <form method="POST" action="{{ route('company.crm-meetings.update', $meeting->id) }}" class="dg-form">
                @csrf
                @method('PUT')

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Meeting Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="meeting_date" class="form-label">Meeting Date <span class="text-danger">*</span></label>
                                    <input type="date" name="meeting_date" id="meeting_date" class="form-control dg-input" value="{{ old('meeting_date', $meeting->meeting_date?->format('Y-m-d')) }}" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="meeting_time" class="form-label">Meeting Time</label>
                                    <input type="time" name="meeting_time" id="meeting_time" class="form-control dg-input" value="{{ old('meeting_time', $meeting->meeting_time) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="assigned_employee_id" class="form-label">Assigned Employee <span class="text-danger">*</span></label>
                                    <select name="assigned_employee_id" id="assigned_employee_id" class="form-select dg-select" required>
                                        <option value="">Select Employee</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}" @selected(old('assigned_employee_id', $meeting->assigned_employee_id) == $employee->id)>
                                                {{ $employee->employee_code }} — {{ $employee->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" name="location" id="location" class="form-control dg-input" value="{{ old('location', $meeting->location) }}">
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-select dg-select" required>
                                        @foreach ($statusOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected(old('status', $meeting->status) == $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea name="remarks" id="remarks" rows="3" class="form-control dg-input">{{ old('remarks', $meeting->remarks) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Meeting</button>
                            <a href="{{ route('company.crm-meetings.index') }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                        </footer>
                    </article>
                </section>
            </form>

        </div>
    </main>
</div>

@endsection
