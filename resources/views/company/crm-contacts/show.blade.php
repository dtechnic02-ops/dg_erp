@extends('company.layout')

@section('title', 'Contact Details')

@section('content')

@php
    $canEdit = userCan('edit_crm_contact') && $contact->isEditable($terminalKeys);
    $canArchive = userCan('archive_crm_contact') && $contact->isEditable($terminalKeys);
    $canCancel = userCan('cancel_crm_contact') && $contact->isEditable($terminalKeys);
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Contact Details</h1>
                    <p class="text-muted small mb-0">{{ $contact->contact_no }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group flex-wrap" aria-label="Contact toolbar">
                        <a href="{{ route('company.crm-contacts.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                        @if ($canEdit)
                            <a href="{{ route('company.crm-contacts.edit', $contact->id) }}" class="btn btn-outline-primary dg-btn">Edit</a>
                        @endif
                        @if ($canArchive)
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-toggle="modal" data-bs-target="#dgContactArchiveModal">Archive</button>
                        @endif
                        @if ($canCancel)
                            <button type="button" class="btn btn-outline-warning dg-btn" data-bs-toggle="modal" data-bs-target="#dgContactCancelModal">Cancel</button>
                        @endif
                    </nav>
                </div>
            </div>
        </div>
    </header>

    @if ($canArchive)
        <div class="modal fade" id="dgContactArchiveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-contacts.archive', $contact->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Archive Contact</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <label for="archive_reason" class="form-label">Archive Reason <span class="text-danger">*</span></label>
                            <textarea name="archive_reason" id="archive_reason" class="form-control dg-input" rows="4" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Back</button>
                            <button type="submit" class="btn btn-secondary dg-btn">Archive Contact</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($canCancel)
        <div class="modal fade" id="dgContactCancelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.crm-contacts.cancel', $contact->id) }}">
                        @csrf
                        <div class="modal-header"><h5 class="modal-title">Cancel Contact</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <label for="cancel_reason" class="form-label">Cancel Reason <span class="text-danger">*</span></label>
                            <textarea name="cancel_reason" id="cancel_reason" class="form-control dg-input" rows="4" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Back</button>
                            <button type="submit" class="btn btn-warning dg-btn">Cancel Contact</button>
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
                        <h2 class="h6 mb-0">Contact Information</h2>
                        <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $contact->status)) }}</span>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <tbody>
                                    <tr><th width="220">Contact No</th><td>{{ $contact->contact_no }}</td></tr>
                                    <tr><th>Financial Year</th><td>{{ $contact->financialYear->name ?? '-' }}</td></tr>
                                    <tr><th>Contact Date</th><td>{{ $contact->contact_date?->format('d-m-Y') ?? '-' }}</td></tr>
                                    <tr><th>Person Name</th><td>{{ $contact->name }}</td></tr>
                                    <tr><th>Designation</th><td>{{ $contact->designation ?: '-' }}</td></tr>
                                    <tr><th>Department</th><td>{{ $contact->department ?: '-' }}</td></tr>
                                    <tr><th>Mobile</th><td>{{ $contact->mobile ?: '-' }}</td></tr>
                                    <tr><th>Phone</th><td>{{ $contact->phone ?: '-' }}</td></tr>
                                    <tr><th>Email</th><td>{{ $contact->email ?: '-' }}</td></tr>
                                    <tr><th>Customer</th><td>{{ $contact->customer->name ?? '-' }}</td></tr>
                                    @if ($contact->customer)
                                        <tr><th>Customer Mobile</th><td>{{ $contact->customer->mobile ?: '-' }}</td></tr>
                                        <tr><th>Customer Email</th><td>{{ $contact->customer->email ?: '-' }}</td></tr>
                                        <tr><th>Customer Address</th><td>{{ $contact->customer->address ?: '-' }}</td></tr>
                                    @endif
                                    <tr><th>Customer Relationship</th><td>
                                        @if ($contact->lead)
                                            {{ $contact->lead->lead_no }}
                                        @else
                                            -
                                        @endif
                                    </td></tr>
                                    <tr><th>Assigned Employee</th><td>{{ $contact->assignedEmployee->full_name ?? '-' }}</td></tr>
                                    <tr><th>Priority</th><td>{{ ucfirst(str_replace('_', ' ', $contact->priority)) }}</td></tr>
                                    <tr><th>Remarks</th><td>{{ $contact->remarks ?: '-' }}</td></tr>
                                    <tr><th>Created By</th><td>{{ $contact->creator->name ?? '-' }}</td></tr>
                                    @if ($contact->archived_at)
                                        <tr><th>Archived At</th><td>{{ $contact->archived_at?->format('d-m-Y H:i') ?? '-' }}</td></tr>
                                        <tr><th>Archive Reason</th><td>{{ $contact->archive_reason ?: '-' }}</td></tr>
                                    @endif
                                    @if ($contact->cancelled_at)
                                        <tr><th>Cancelled At</th><td>{{ $contact->cancelled_at?->format('d-m-Y H:i') ?? '-' }}</td></tr>
                                        <tr><th>Cancel Reason</th><td>{{ $contact->cancel_reason ?: '-' }}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

            @include('company.crm.partials.notes-attachments', [
                'entityType' => 'contact',
                'entityId' => $contact->id,
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
