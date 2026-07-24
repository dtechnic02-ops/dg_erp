@extends('company.layout')

@section('title', 'CRM Tasks')

@section('content')

@php
    $selectedTaskStatus = request()->has('task_status') ? request('task_status') : '';
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">CRM Tasks</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-nowrap">
                    <a href="{{ route('company.crm.dashboard.index') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                    @if (userCan('create_crm_task'))
                        <a href="{{ route('company.crm-tasks.create') }}" class="btn btn-success dg-btn">New Task</a>
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
                        <form method="GET" action="{{ route('company.crm-tasks.index') }}" class="dg-filter-form">
                            <div class="dg-filter-grid">
                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="task_status" class="dg-filter-label">Task Status</label>
                                    <select name="task_status" id="task_status" class="form-select dg-select dg-filter-control">
                                        <option value="" @selected($selectedTaskStatus === '')>Active Tasks</option>
                                        @foreach ($statusOptions as $option)
                                            <option value="{{ $option->config_key }}" @selected($selectedTaskStatus === $option->config_key)>{{ $option->config_label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-actions">
                                    <button type="submit" class="btn btn-primary dg-btn">Filter</button>
                                    <a href="{{ route('company.crm-tasks.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Task List</h2>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Activity No</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Due Date</th>
                                        <th scope="col">Relationship / Opportunity</th>
                                        <th scope="col">Employee</th>
                                        <th scope="col">Priority</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="220">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($tasks as $task)
                                        @php
                                            $typeLabel = $typeOptions->firstWhere('config_key', $task->task_type)?->config_label ?? ucfirst(str_replace('_', ' ', $task->task_type));
                                            $statusLabel = $statusOptions->firstWhere('config_key', $task->task_status)?->config_label ?? ucfirst(str_replace('_', ' ', $task->task_status));
                                            $isActive = !$task->archived_at && !$task->cancelled_at;
                                            $canComplete = $isActive && !$task->completed_at;
                                        @endphp
                                        <tr class="dg-row">
                                            <td>{{ $tasks->firstItem() + $loop->index }}</td>
                                            <td>{{ $task->activity_no }}</td>
                                            <td>{{ $typeLabel }}</td>
                                            <td>{{ $task->due_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>
                                                @if ($task->lead)
                                                    <span class="d-block">{{ $task->lead->customer?->name ?? '-' }}</span>
                                                    <small class="text-muted">{{ $task->lead->lead_no }}</small>
                                                @elseif ($task->opportunity)
                                                    <span class="d-block">{{ $task->opportunity->title }}</span>
                                                    <small class="text-muted">{{ $task->opportunity->opportunity_no }}</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $task->assignedEmployee->full_name ?? '-' }}</td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $task->priority)) }}</td>
                                            <td><span class="badge bg-secondary">{{ $statusLabel }}</span></td>
                                            <td>
                                                <div class="btn-group flex-wrap" role="group">
                                                    @if (userCan('edit_crm_task') && $isActive)
                                                        <a href="{{ route('company.crm-tasks.edit', $task->id) }}" class="btn btn-sm btn-outline-primary dg-btn">Edit</a>
                                                    @endif
                                                    @if (userCan('edit_crm_task') && $canComplete)
                                                        <form method="POST" action="{{ route('company.crm-tasks.complete', $task->id) }}" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success dg-btn" onclick="return confirm('Mark this task as completed?')">Complete</button>
                                                        </form>
                                                    @endif
                                                    @if (userCan('archive_crm_task') && $isActive)
                                                        <button type="button" class="btn btn-sm btn-outline-secondary dg-btn" data-bs-toggle="modal" data-bs-target="#dgTaskArchiveModal{{ $task->id }}">Archive</button>
                                                    @endif
                                                    @if (userCan('cancel_crm_task') && $isActive)
                                                        <button type="button" class="btn btn-sm btn-outline-warning dg-btn" data-bs-toggle="modal" data-bs-target="#dgTaskCancelModal{{ $task->id }}">Cancel</button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No task records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $tasks->firstItem() ?? 0 }} to {{ $tasks->lastItem() ?? 0 }} of {{ $tasks->total() }} records
                            </p>
                            {{ $tasks->links() }}
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@foreach ($tasks as $task)
    @php
        $isActive = !$task->archived_at && !$task->cancelled_at;
    @endphp
    @if ($isActive && userCan('archive_crm_task'))
        <div class="modal fade" id="dgTaskArchiveModal{{ $task->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-tasks.archive', $task->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Archive Task</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <p class="small text-muted">{{ $task->activity_no }}</p>
                            <label for="task_archive_reason_{{ $task->id }}" class="form-label">Archive Reason <span class="text-danger">*</span></label>
                            <textarea name="archive_reason" id="task_archive_reason_{{ $task->id }}" class="form-control dg-input" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Back</button>
                            <button type="submit" class="btn btn-secondary dg-btn">Archive</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    @if ($isActive && userCan('cancel_crm_task'))
        <div class="modal fade" id="dgTaskCancelModal{{ $task->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-tasks.cancel', $task->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Cancel Task</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <p class="small text-muted">{{ $task->activity_no }}</p>
                            <label for="task_cancel_reason_{{ $task->id }}" class="form-label">Cancel Reason <span class="text-danger">*</span></label>
                            <textarea name="cancel_reason" id="task_cancel_reason_{{ $task->id }}" class="form-control dg-input" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Back</button>
                            <button type="submit" class="btn btn-warning dg-btn">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

@endsection
