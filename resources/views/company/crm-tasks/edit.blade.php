@extends('company.layout')

@section('title', 'Edit Task')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Task</h1>
                    <p class="text-muted small mb-0">{{ $task->activity_no }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Task toolbar">
                        <a href="{{ route('company.crm-tasks.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
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

            <form method="POST" action="{{ route('company.crm-tasks.update', $task->id) }}" class="dg-form">
                @csrf
                @method('PUT')

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Task Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="task_type" class="form-label">Task Type <span class="text-danger">*</span></label>
                                    <select name="task_type" id="task_type" class="form-select dg-select" required>
                                        @foreach ($typeOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected(old('task_type', $task->task_type) == $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="task_status" class="form-label">Task Status <span class="text-danger">*</span></label>
                                    <select name="task_status" id="task_status" class="form-select dg-select" required>
                                        @foreach ($statusOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected(old('task_status', $task->task_status) == $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                    <select name="priority" id="priority" class="form-select dg-select" required>
                                        @foreach ($priorityOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected(old('priority', $task->priority) == $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" name="due_date" id="due_date" class="form-control dg-input" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="assigned_employee_id" class="form-label">Assigned Employee <span class="text-danger">*</span></label>
                                    <select name="assigned_employee_id" id="assigned_employee_id" class="form-select dg-select" required>
                                        <option value="">Select Employee</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}" @selected(old('assigned_employee_id', $task->assigned_employee_id) == $employee->id)>
                                                {{ $employee->employee_code }} — {{ $employee->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea name="remarks" id="remarks" rows="3" class="form-control dg-input">{{ old('remarks', $task->remarks) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Task</button>
                            <a href="{{ route('company.crm-tasks.index') }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                        </footer>
                    </article>
                </section>
            </form>

        </div>
    </main>
</div>

@endsection
