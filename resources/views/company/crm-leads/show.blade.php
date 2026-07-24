@extends('company.layout')

@section('title', 'Relationship Details')

@section('content')

@php
    $canEdit = userCan('edit_crm_lead') && $lead->isEditable($terminalKeys);
    $canClose = userCan('close_crm_lead') && $lead->isEditable($terminalKeys);
    $canArchive = userCan('archive_crm_lead') && $lead->isEditable($terminalKeys);
    $canCancel = userCan('cancel_crm_lead') && $lead->isEditable($terminalKeys);
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Relationship Details</h1>
                    <p class="text-muted small mb-0">{{ $lead->lead_no }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group flex-wrap" aria-label="Relationship toolbar">
                        <a href="{{ route('company.crm-leads.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                        @if ($canEdit)
                            <a href="{{ route('company.crm-leads.edit', $lead->id) }}" class="btn btn-outline-primary dg-btn">Edit</a>
                        @endif
                        @if (userCan('create_crm_follow_up'))
                            <a href="{{ route('company.crm-follow-ups.create', ['crm_lead_id' => $lead->id]) }}" class="btn btn-outline-secondary dg-btn">Add Follow-up</a>
                        @endif
                        @if ($canClose)
                            <button type="button" class="btn btn-outline-danger dg-btn" data-bs-toggle="modal" data-bs-target="#dgLeadCloseModal">Close</button>
                        @endif
                        @if ($canArchive)
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-toggle="modal" data-bs-target="#dgLeadArchiveModal">Archive</button>
                        @endif
                        @if ($canCancel)
                            <button type="button" class="btn btn-outline-warning dg-btn" data-bs-toggle="modal" data-bs-target="#dgLeadCancelModal">Cancel</button>
                        @endif
                    </nav>
                </div>
            </div>
        </div>
    </header>

    @if ($canClose)
        <div class="modal fade" id="dgLeadCloseModal" tabindex="-1" aria-labelledby="dgLeadCloseModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-leads.close', $lead->id) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="dgLeadCloseModalLabel">Close Relationship</h5>
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
                            <button type="submit" class="btn btn-danger dg-btn">Close Relationship</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($canArchive)
        <div class="modal fade" id="dgLeadArchiveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-leads.archive', $lead->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Archive Relationship</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <label for="archive_reason" class="form-label">Archive Reason <span class="text-danger">*</span></label>
                            <textarea name="archive_reason" id="archive_reason" class="form-control dg-input" rows="4" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Back</button>
                            <button type="submit" class="btn btn-secondary dg-btn">Archive Relationship</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($canCancel)
        <div class="modal fade" id="dgLeadCancelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-leads.cancel', $lead->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Cancel Relationship</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <label for="cancel_reason" class="form-label">Cancel Reason <span class="text-danger">*</span></label>
                            <textarea name="cancel_reason" id="cancel_reason" class="form-control dg-input" rows="4" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Back</button>
                            <button type="submit" class="btn btn-warning dg-btn">Cancel Relationship</button>
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
                        <h2 class="h6 mb-0">Relationship Information</h2>
                        <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $lead->status)) }}</span>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <tbody>
                                    <tr><th width="220">Relationship No</th><td>{{ $lead->lead_no }}</td></tr>
                                    <tr><th>Financial Year</th><td>{{ $lead->financialYear->name ?? '-' }}</td></tr>
                                    <tr><th>Relationship Date</th><td>{{ $lead->lead_date?->format('d-m-Y') ?? '-' }}</td></tr>
                                    <tr><th>Customer Code</th><td>{{ $lead->customer ? 'CUST-' . str_pad((string) $lead->customer->id, 6, '0', STR_PAD_LEFT) : '-' }}</td></tr>
                                    <tr><th>Customer Name</th><td>{{ $lead->customer->name ?? '-' }}</td></tr>
                                    <tr><th>Company / Authority</th><td>{{ $lead->customer->authority_name ?? '-' }}</td></tr>
                                    <tr><th>Mobile</th><td>{{ $lead->customer->mobile ?? '-' }}</td></tr>
                                    <tr><th>Email</th><td>{{ $lead->customer->email ?? '-' }}</td></tr>
                                    <tr><th>Address</th><td>{{ $lead->customer->address ?? '-' }}</td></tr>
                                    <tr><th>Assigned Employee</th><td>{{ $lead->assignedEmployee->full_name ?? '-' }}</td></tr>
                                    <tr><th>Priority</th><td>{{ ucfirst(str_replace('_', ' ', $lead->priority)) }}</td></tr>
                                    <tr><th>Expected Value</th><td>{{ number_format($lead->expected_value, 2) }}</td></tr>
                                    <tr><th>Remarks</th><td>{{ $lead->remarks ?: '-' }}</td></tr>
                                    <tr><th>Created By</th><td>{{ $lead->creator->name ?? '-' }}</td></tr>
                                    @if ($lead->customer)
                                        <tr><th>Customer Profile</th><td><a href="{{ route('company.customers.show', $lead->customer_id) }}" class="btn btn-sm btn-outline-info dg-btn">View Customer</a></td></tr>
                                    @endif
                                    @if ($lead->closed_at)
                                        <tr><th>Closed At</th><td>{{ $lead->closed_at?->format('d-m-Y H:i') ?? '-' }}</td></tr>
                                        <tr><th>Close Reason</th><td>{{ $lead->close_reason ?: '-' }}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h2 class="h6 mb-0">Follow-ups</h2>
                        @if (userCan('view_crm_follow_up'))
                            <a href="{{ route('company.crm-follow-ups.index', ['crm_lead_id' => $lead->id]) }}" class="btn btn-sm btn-outline-secondary dg-btn">View All</a>
                        @endif
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead>
                                    <tr>
                                        <th>Activity No</th>
                                        <th>Date</th>
                                        <th>Next Follow-up</th>
                                        <th>Employee</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($lead->followUps->sortByDesc('follow_up_date') as $followUp)
                                        <tr>
                                            <td>{{ $followUp->activity_no }}</td>
                                            <td>{{ $followUp->follow_up_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>{{ $followUp->next_follow_up_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>{{ $followUp->assignedEmployee->full_name ?? '-' }}</td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $followUp->status)) }}</td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $followUp->priority)) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No follow-ups recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

            @if ($lead->opportunities->count())
                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Related Opportunities</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="table-responsive">
                                <table class="table dg-table">
                                    <thead>
                                        <tr>
                                            <th>Opportunity No</th>
                                            <th>Title</th>
                                            <th>Stage</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lead->opportunities as $opportunity)
                                            <tr>
                                                <td>{{ $opportunity->opportunity_no }}</td>
                                                <td>{{ $opportunity->title }}</td>
                                                <td>{{ ucfirst(str_replace('_', ' ', $opportunity->stage)) }}</td>
                                                <td>{{ ucfirst(str_replace('_', ' ', $opportunity->status)) }}</td>
                                                <td>
                                                    @if (userCan('view_crm_opportunity'))
                                                        <a href="{{ route('company.crm-opportunities.show', $opportunity->id) }}" class="btn btn-sm btn-outline-info dg-btn">View</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </article>
                </section>
            @endif

            @include('company.crm.partials.notes-attachments', [
                'entityType' => 'lead',
                'entityId' => $lead->id,
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
