@extends('company.layout')

@section('title', 'CRM Meetings')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">CRM Meetings</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-nowrap">
                    <a href="{{ route('company.crm.dashboard.index') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                    @if (userCan('create_crm_meeting'))
                        <a href="{{ route('company.crm-meetings.create') }}" class="btn btn-success dg-btn">New Meeting</a>
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
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Meeting List</h2>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Activity No</th>
                                        <th scope="col">Meeting Date</th>
                                        <th scope="col">Time</th>
                                        <th scope="col">Relationship / Opportunity</th>
                                        <th scope="col">Employee</th>
                                        <th scope="col">Location</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="220">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($meetings as $meeting)
                                        @php
                                            $isActive = !$meeting->archived_at && !$meeting->cancelled_at;
                                            $canComplete = $isActive && !$meeting->completed_at;
                                        @endphp
                                        <tr class="dg-row">
                                            <td>{{ $meetings->firstItem() + $loop->index }}</td>
                                            <td>{{ $meeting->activity_no }}</td>
                                            <td>{{ $meeting->meeting_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>{{ $meeting->meeting_time ?: '-' }}</td>
                                            <td>
                                                @if ($meeting->lead)
                                                    <span class="d-block">{{ $meeting->lead->customer?->name ?? '-' }}</span>
                                                    <small class="text-muted">{{ $meeting->lead->lead_no }}</small>
                                                @elseif ($meeting->opportunity)
                                                    <span class="d-block">{{ $meeting->opportunity->title }}</span>
                                                    <small class="text-muted">{{ $meeting->opportunity->opportunity_no }}</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $meeting->assignedEmployee->full_name ?? '-' }}</td>
                                            <td>{{ $meeting->location ?: '-' }}</td>
                                            <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $meeting->status)) }}</span></td>
                                            <td>
                                                <div class="btn-group flex-wrap" role="group">
                                                    @if (userCan('edit_crm_meeting') && $isActive)
                                                        <a href="{{ route('company.crm-meetings.edit', $meeting->id) }}" class="btn btn-sm btn-outline-primary dg-btn">Edit</a>
                                                    @endif
                                                    @if (userCan('edit_crm_meeting') && $canComplete)
                                                        <form method="POST" action="{{ route('company.crm-meetings.complete', $meeting->id) }}" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success dg-btn" onclick="return confirm('Mark this meeting as completed?')">Complete</button>
                                                        </form>
                                                    @endif
                                                    @if (userCan('archive_crm_meeting') && $isActive)
                                                        <button type="button" class="btn btn-sm btn-outline-secondary dg-btn" data-bs-toggle="modal" data-bs-target="#dgMeetingArchiveModal{{ $meeting->id }}">Archive</button>
                                                    @endif
                                                    @if (userCan('cancel_crm_meeting') && $isActive)
                                                        <button type="button" class="btn btn-sm btn-outline-warning dg-btn" data-bs-toggle="modal" data-bs-target="#dgMeetingCancelModal{{ $meeting->id }}">Cancel</button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No meeting records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $meetings->firstItem() ?? 0 }} to {{ $meetings->lastItem() ?? 0 }} of {{ $meetings->total() }} records
                            </p>
                            {{ $meetings->links() }}
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@foreach ($meetings as $meeting)
    @php
        $isActive = !$meeting->archived_at && !$meeting->cancelled_at;
    @endphp
    @if ($isActive && userCan('archive_crm_meeting'))
        <div class="modal fade" id="dgMeetingArchiveModal{{ $meeting->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-meetings.archive', $meeting->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Archive Meeting</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <p class="small text-muted">{{ $meeting->activity_no }}</p>
                            <label for="meeting_archive_reason_{{ $meeting->id }}" class="form-label">Archive Reason <span class="text-danger">*</span></label>
                            <textarea name="archive_reason" id="meeting_archive_reason_{{ $meeting->id }}" class="form-control dg-input" rows="3" required></textarea>
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
    @if ($isActive && userCan('cancel_crm_meeting'))
        <div class="modal fade" id="dgMeetingCancelModal{{ $meeting->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-meetings.cancel', $meeting->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Cancel Meeting</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <p class="small text-muted">{{ $meeting->activity_no }}</p>
                            <label for="meeting_cancel_reason_{{ $meeting->id }}" class="form-label">Cancel Reason <span class="text-danger">*</span></label>
                            <textarea name="cancel_reason" id="meeting_cancel_reason_{{ $meeting->id }}" class="form-control dg-input" rows="3" required></textarea>
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
