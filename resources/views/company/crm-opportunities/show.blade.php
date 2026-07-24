@extends('company.layout')

@section('title', 'Opportunity Details')

@section('content')

@php
    $canEdit = userCan('edit_crm_opportunity') && $opportunity->isEditable($terminalKeys);
    $canClose = userCan('close_crm_opportunity') && $opportunity->isEditable($terminalKeys);
    $canWon = userCan('close_crm_opportunity') && $opportunity->isEditable($terminalKeys);
    $canLost = userCan('close_crm_opportunity') && $opportunity->isEditable($terminalKeys);
    $canArchive = userCan('archive_crm_opportunity') && $opportunity->isEditable($terminalKeys);
    $canCancel = userCan('cancel_crm_opportunity') && $opportunity->isEditable($terminalKeys);
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Opportunity Details</h1>
                    <p class="text-muted small mb-0">{{ $opportunity->opportunity_no }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group flex-wrap" aria-label="Opportunity toolbar">
                        <a href="{{ route('company.crm-opportunities.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                        @if ($canEdit)
                            <a href="{{ route('company.crm-opportunities.edit', $opportunity->id) }}" class="btn btn-outline-primary dg-btn">Edit</a>
                        @endif
                        @if (userCan('create_crm_follow_up'))
                            <a href="{{ route('company.crm-follow-ups.create', ['crm_opportunity_id' => $opportunity->id]) }}" class="btn btn-outline-secondary dg-btn">Add Follow-up</a>
                        @endif
                        @if ($opportunity->lead && userCan('view_crm_lead'))
                            <a href="{{ route('company.crm-leads.show', $opportunity->lead->id) }}" class="btn btn-outline-info dg-btn">View Relationship</a>
                        @endif
                        @if ($canClose)
                            <button type="button" class="btn btn-outline-danger dg-btn" data-bs-toggle="modal" data-bs-target="#dgOpportunityCloseModal">Close</button>
                        @endif
                        @if ($canWon)
                            <button type="button" class="btn btn-success dg-btn" data-bs-toggle="modal" data-bs-target="#dgOpportunityWonModal">Won</button>
                        @endif
                        @if ($canLost)
                            <button type="button" class="btn btn-outline-warning dg-btn" data-bs-toggle="modal" data-bs-target="#dgOpportunityLostModal">Lost</button>
                        @endif
                        @if ($canArchive)
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-toggle="modal" data-bs-target="#dgOpportunityArchiveModal">Archive</button>
                        @endif
                        @if ($canCancel)
                            <button type="button" class="btn btn-outline-warning dg-btn" data-bs-toggle="modal" data-bs-target="#dgOpportunityCancelModal">Cancel</button>
                        @endif
                    </nav>
                </div>
            </div>
        </div>
    </header>

    @if ($canClose)
        <div class="modal fade" id="dgOpportunityCloseModal" tabindex="-1" aria-labelledby="dgOpportunityCloseModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-opportunities.close', $opportunity->id) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="dgOpportunityCloseModalLabel">Close Opportunity</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-0">
                                <label for="close_reason" class="form-label">Close Reason <span class="text-danger">*</span></label>
                                <textarea name="close_reason" id="close_reason" class="form-control dg-input" rows="4" required>{{ old('close_reason') }}</textarea>
                                @error('close_reason')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger dg-btn">Close Opportunity</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($canWon)
        <div class="modal fade" id="dgOpportunityWonModal" tabindex="-1" aria-labelledby="dgOpportunityWonModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-opportunities.won', $opportunity->id) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="dgOpportunityWonModalLabel">Mark Opportunity as Won</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-0">
                                <label for="won_remarks" class="form-label">Remarks</label>
                                <textarea name="remarks" id="won_remarks" class="form-control dg-input" rows="3">{{ old('remarks') }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success dg-btn">Mark as Won</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($canLost)
        <div class="modal fade" id="dgOpportunityLostModal" tabindex="-1" aria-labelledby="dgOpportunityLostModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-opportunities.lost', $opportunity->id) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="dgOpportunityLostModalLabel">Mark Opportunity as Lost</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-0">
                                <label for="lost_close_reason" class="form-label">Close Reason <span class="text-danger">*</span></label>
                                <textarea name="close_reason" id="lost_close_reason" class="form-control dg-input" rows="4" required>{{ old('close_reason') }}</textarea>
                                @error('close_reason')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning dg-btn">Mark as Lost</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($canArchive)
        <div class="modal fade" id="dgOpportunityArchiveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-opportunities.archive', $opportunity->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Archive Opportunity</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <label for="archive_reason" class="form-label">Archive Reason <span class="text-danger">*</span></label>
                            <textarea name="archive_reason" id="archive_reason" class="form-control dg-input" rows="4" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Back</button>
                            <button type="submit" class="btn btn-secondary dg-btn">Archive Opportunity</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($canCancel)
        <div class="modal fade" id="dgOpportunityCancelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-opportunities.cancel', $opportunity->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Cancel Opportunity</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <label for="cancel_reason" class="form-label">Cancel Reason <span class="text-danger">*</span></label>
                            <textarea name="cancel_reason" id="cancel_reason" class="form-control dg-input" rows="4" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Back</button>
                            <button type="submit" class="btn btn-warning dg-btn">Cancel Opportunity</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

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
                    <header class="card-header dg-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h2 class="h6 mb-0">Opportunity Information</h2>
                        <div class="d-flex gap-2">
                            <span class="badge bg-info text-dark">{{ ucfirst(str_replace('_', ' ', $opportunity->stage)) }}</span>
                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $opportunity->status)) }}</span>
                        </div>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <tbody>
                                    <tr><th width="220">Opportunity No</th><td>{{ $opportunity->opportunity_no }}</td></tr>
                                    <tr><th>Financial Year</th><td>{{ $opportunity->financialYear->name ?? '-' }}</td></tr>
                                    <tr><th>Title</th><td>{{ $opportunity->title }}</td></tr>
                                    <tr><th>Customer</th><td>{{ $opportunity->customer->name ?? '-' }}</td></tr>
                                    <tr><th>Customer Relationship</th><td>{{ $opportunity->lead->lead_no ?? '-' }}</td></tr>
                                    <tr><th>Assigned Employee</th><td>{{ $opportunity->assignedEmployee->full_name ?? '-' }}</td></tr>
                                    <tr><th>Potential Value</th><td>{{ number_format($opportunity->potential_value, 2) }}</td></tr>
                                    <tr><th>Expected Closing Date</th><td>{{ $opportunity->expected_closing_date?->format('d-m-Y') ?? '-' }}</td></tr>
                                    <tr><th>Probability</th><td>{{ number_format($opportunity->probability, 2) }}%</td></tr>
                                    <tr><th>Remarks</th><td>{{ $opportunity->remarks ?: '-' }}</td></tr>
                                    @if ($opportunity->closed_at)
                                        <tr><th>Closed At</th><td>{{ $opportunity->closed_at?->format('d-m-Y H:i') ?? '-' }}</td></tr>
                                        <tr><th>Close Reason</th><td>{{ $opportunity->close_reason ?: '-' }}</td></tr>
                                    @endif
                                    @if ($opportunity->archived_at)
                                        <tr><th>Archived At</th><td>{{ $opportunity->archived_at?->format('d-m-Y H:i') ?? '-' }}</td></tr>
                                        <tr><th>Archive Reason</th><td>{{ $opportunity->archive_reason ?: '-' }}</td></tr>
                                    @endif
                                    @if ($opportunity->cancelled_at)
                                        <tr><th>Cancelled At</th><td>{{ $opportunity->cancelled_at?->format('d-m-Y H:i') ?? '-' }}</td></tr>
                                        <tr><th>Cancel Reason</th><td>{{ $opportunity->cancel_reason ?: '-' }}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

            @include('company.crm.partials.notes-attachments', [
                'entityType' => 'opportunity',
                'entityId' => $opportunity->id,
                'notes' => $notes,
                'attachments' => $attachments,
            ])

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Status History</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Event</th>
                                        <th>Previous</th>
                                        <th>Current</th>
                                        <th>Changed By</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($histories as $history)
                                        <tr>
                                            <td>{{ $history->changed_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                            <td>{{ $history->event }}</td>
                                            <td>{{ $history->previous_value ? ucfirst(str_replace('_', ' ', $history->previous_value)) : '-' }}</td>
                                            <td>{{ $history->current_value ? ucfirst(str_replace('_', ' ', $history->current_value)) : '-' }}</td>
                                            <td>{{ $history->changer->name ?? '-' }}</td>
                                            <td>{{ $history->remarks ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No status history recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>

@endsection
