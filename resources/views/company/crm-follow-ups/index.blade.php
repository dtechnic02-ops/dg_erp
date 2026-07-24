@extends('company.layout')

@section('title', 'CRM Follow-ups')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">CRM Follow-ups</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-nowrap">
                    <a href="{{ route('company.crm.dashboard.index') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                    @if (userCan('create_crm_follow_up'))
                        <a href="{{ route('company.crm-follow-ups.create') }}" class="btn btn-success dg-btn">New Follow-up</a>
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
                        <form method="GET" action="{{ route('company.crm-follow-ups.index') }}" class="dg-filter-form">
                            <div class="dg-filter-grid">
                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="start_date" class="dg-filter-label">Date From</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control dg-input dg-filter-control" value="{{ request('start_date') }}">
                                </div>

                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="end_date" class="dg-filter-label">Date To</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control dg-input dg-filter-control" value="{{ request('end_date') }}">
                                </div>

                                <div class="dg-filter-actions">
                                    <button type="submit" class="btn btn-primary dg-btn">Filter</button>
                                    <a href="{{ route('company.crm-follow-ups.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Follow-up List</h2>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Activity No</th>
                                        <th scope="col">Follow-up Date</th>
                                        <th scope="col">Next Follow-up</th>
                                        <th scope="col">Relationship / Opportunity</th>
                                        <th scope="col">Employee</th>
                                        <th scope="col">Priority</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="200">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($followUps as $followUp)
                                        @php
                                            $isActive = !$followUp->archived_at && !$followUp->cancelled_at;
                                        @endphp
                                        <tr class="dg-row">
                                            <td>{{ $followUps->firstItem() + $loop->index }}</td>
                                            <td>{{ $followUp->activity_no }}</td>
                                            <td>{{ $followUp->follow_up_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>{{ $followUp->next_follow_up_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>
                                                @if ($followUp->lead)
                                                    <span class="d-block">{{ $followUp->lead->customer?->name ?? '-' }}</span>
                                                    <small class="text-muted">{{ $followUp->lead->lead_no }}</small>
                                                @elseif ($followUp->opportunity)
                                                    <span class="d-block">{{ $followUp->opportunity->title }}</span>
                                                    <small class="text-muted">{{ $followUp->opportunity->opportunity_no }}</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $followUp->assignedEmployee->full_name ?? '-' }}</td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $followUp->priority)) }}</td>
                                            <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $followUp->status)) }}</span></td>
                                            <td>
                                                <div class="btn-group flex-wrap" role="group">
                                                    @if (userCan('edit_crm_follow_up') && $isActive)
                                                        <a href="{{ route('company.crm-follow-ups.edit', $followUp->id) }}" class="btn btn-sm btn-outline-primary dg-btn">Edit</a>
                                                    @endif
                                                    @if (userCan('archive_crm_follow_up') && $isActive)
                                                        <button type="button" class="btn btn-sm btn-outline-secondary dg-btn" data-bs-toggle="modal" data-bs-target="#dgFollowUpArchiveModal{{ $followUp->id }}">Archive</button>
                                                    @endif
                                                    @if (userCan('cancel_crm_follow_up') && $isActive)
                                                        <button type="button" class="btn btn-sm btn-outline-warning dg-btn" data-bs-toggle="modal" data-bs-target="#dgFollowUpCancelModal{{ $followUp->id }}">Cancel</button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No follow-up records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $followUps->firstItem() ?? 0 }} to {{ $followUps->lastItem() ?? 0 }} of {{ $followUps->total() }} records
                            </p>
                            {{ $followUps->links() }}
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@foreach ($followUps as $followUp)
    @php
        $isActive = !$followUp->archived_at && !$followUp->cancelled_at;
    @endphp
    @if ($isActive && userCan('archive_crm_follow_up'))
        <div class="modal fade" id="dgFollowUpArchiveModal{{ $followUp->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-follow-ups.archive', $followUp->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Archive Follow-up</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <p class="small text-muted">{{ $followUp->activity_no }}</p>
                            <label for="archive_reason_{{ $followUp->id }}" class="form-label">Archive Reason <span class="text-danger">*</span></label>
                            <textarea name="archive_reason" id="archive_reason_{{ $followUp->id }}" class="form-control dg-input" rows="3" required></textarea>
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
    @if ($isActive && userCan('cancel_crm_follow_up'))
        <div class="modal fade" id="dgFollowUpCancelModal{{ $followUp->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-follow-ups.cancel', $followUp->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Cancel Follow-up</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <p class="small text-muted">{{ $followUp->activity_no }}</p>
                            <label for="cancel_reason_{{ $followUp->id }}" class="form-label">Cancel Reason <span class="text-danger">*</span></label>
                            <textarea name="cancel_reason" id="cancel_reason_{{ $followUp->id }}" class="form-control dg-input" rows="3" required></textarea>
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
