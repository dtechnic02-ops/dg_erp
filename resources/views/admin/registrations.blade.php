@extends('admin.layout')

@section('content')
<div class="dg-page">
    <div class="dg-page-header">
        <div>
            <h2 class="dg-page-title">Company Registrations</h2>
            <p class="dg-page-subtitle">Review company registration requests and process pending applications.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
    @endif

    <div class="dg-card card">
        <div class="dg-card-header card-header d-flex justify-content-between align-items-center gap-2">
            <span>Registration requests</span>
            <span class="text-muted small">{{ $registrations->total() }} total</span>
        </div>
        <div class="dg-card-body card-body">
            <div class="dg-table-scroll dg-table-wrap">
                <table class="table table-hover align-middle dg-table">
                    <thead class="dg-head"><tr><th scope="col">Company</th><th scope="col">Applicant</th><th scope="col">Email</th><th scope="col" class="dg-col-date">Submitted</th><th scope="col" class="dg-col-status">Status</th><th scope="col" class="dg-action-col">Actions</th></tr></thead>
                    <tbody class="dg-body">
                        @forelse($registrations as $r)
                            <tr class="dg-row">
                                <td><strong>{{ $r->company_name }}</strong></td>
                                <td>{{ $r->full_name }}</td>
                                <td>{{ $r->email }}</td>
                                <td class="dg-col-date">{{ $r->created_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                                <td class="dg-col-status"><span class="dg-badge dg-badge-status {{ $r->status === 'pending' ? 'dg-badge-warning' : ($r->status === 'approved' ? 'dg-badge-success' : 'dg-badge-danger') }}">{{ ucfirst($r->status) }}</span></td>
                                <td class="dg-action-col">
                                    <div class="dg-action-group">
                                    <a href="{{ route('admin.registration.show', $r) }}" class="btn btn-outline-primary dg-btn dg-action-btn">View</a>
                                    @if($r->status === 'pending' && auth()->user()->hasPermission('approve_company'))
                                        <button type="button" class="btn btn-success dg-btn dg-action-btn" data-bs-toggle="modal" data-bs-target="#registration-approve-modal-{{ $r->id }}">Approve</button><button type="button" class="btn btn-danger dg-btn dg-action-btn" data-bs-toggle="modal" data-bs-target="#registration-reject-modal-{{ $r->id }}">Reject</button>
                                    @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="dg-empty-state">No company registrations are available.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="dg-pagination">{{ $registrations->links() }}</div>
        </div>
    </div>
</div>

@foreach($registrations as $r)
    @if($r->status === 'pending' && auth()->user()->hasPermission('approve_company'))
        <div class="modal fade dg-modal" id="registration-approve-modal-{{ $r->id }}" tabindex="-1" aria-labelledby="registration-approve-title-{{ $r->id }}" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="registration-approve-title-{{ $r->id }}">Approve registration</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">Approve <strong>{{ $r->company_name }}</strong> for {{ $r->full_name }}? This will run the existing approval workflow.</div><div class="modal-footer"><button type="button" class="btn btn-light dg-btn dg-btn-light" data-bs-dismiss="modal">Cancel</button><form method="POST" action="{{ route('admin.approve', $r->id) }}">@csrf<button type="submit" class="btn btn-success dg-btn dg-btn-success">Approve</button></form></div></div></div></div>
        <div class="modal fade dg-modal" id="registration-reject-modal-{{ $r->id }}" tabindex="-1" aria-labelledby="registration-reject-title-{{ $r->id }}" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="registration-reject-title-{{ $r->id }}">Reject registration</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">Reject the registration for <strong>{{ $r->company_name }}</strong>? This cannot be automatically reversed.</div><div class="modal-footer"><button type="button" class="btn btn-light dg-btn dg-btn-light" data-bs-dismiss="modal">Cancel</button><form method="POST" action="{{ route('admin.reject', $r->id) }}">@csrf<button type="submit" class="btn btn-danger dg-btn dg-btn-danger">Reject</button></form></div></div></div></div>
    @endif
@endforeach
@endsection
