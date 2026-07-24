@extends('company.layout')

@section('title', 'Party Account Profile')

@section('content')

@php
    $company = auth()->user()->company;
@endphp

<div class="dg-page party-show-page">

    <div class="dg-toolbar d-flex justify-content-end align-items-center">
        <div class="d-flex gap-2">
            <a href="{{ route('company.party-account.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
            <a href="{{ route('company.party-account.index') }}" class="btn btn-outline-primary dg-btn">Party List</a>
            <button
                type="button"
                class="btn btn-outline-primary dg-btn"
                data-bs-toggle="modal"
                data-bs-target="#editPartyModal">
                Edit
            </button>
            <form
                method="POST"
                action="{{ route('company.party-account.delete', $party->id) }}"
                class="d-inline"
                onsubmit="return confirm('Delete this party account?')">
                @csrf
                <button type="submit" class="btn btn-outline-danger dg-btn">Delete</button>
            </form>
        </div>
    </div>

    <div class="dg-container">

        @if (session('success'))
            <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
        @endif

        <div id="printArea">

            <div class="dg-section">
                <div class="card dg-card">
                    <div class="card-header dg-card-header py-1">
                        <h6 class="mb-0">Party Account Profile</h6>
                    </div>

                    <div class="card-body dg-card-body p-2">
                        <div class="row g-2">

                            <div class="col-md-6">
                                <h6 class="mb-1">Company Information</h6>

                                <div class="d-flex align-items-center gap-2">
                                    <div>
                                        @if ($company && $company->logo_path)
                                            <img
                                                src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                                                alt="Company Logo"
                                                width="80"
                                                height="80"
                                                class="rounded border">
                                        @else
                                            <img
                                                src="{{ asset('images/no-image.png') }}"
                                                alt="No logo available"
                                                width="80"
                                                height="80"
                                                class="rounded border">
                                        @endif
                                    </div>

                                    <div>
                                        <div>{{ $company->company_name ?? '-' }}</div>
                                        <div>{{ $company->email ?? '-' }}</div>
                                        <div>{{ $company->mobile ?? '-' }}</div>
                                        <div>{{ $company->address ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-1">Party Profile</h6>

                                <div class="d-flex align-items-center gap-2">
                                    @if ($party->photo)
                                        <div>
                                            <img
                                                src="{{ asset($party->photo) }}"
                                                alt="{{ $party->name }} photo"
                                                width="80"
                                                height="80"
                                                class="rounded border">
                                        </div>
                                    @endif

                                    <div>
                                        <div>{{ $party->name }}</div>
                                        <div>{{ $party->account_no }}</div>

                                        <div class="mb-1">
                                            @if ($party->isActive())
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </div>

                                        <div>{{ $party->phone ?: '-' }}</div>
                                        <div>{{ ucfirst($party->type) }}</div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="dg-section">
                <div class="card dg-card">
                    <div class="card-header dg-card-header py-1">
                        <h6 class="mb-0">Profile Details</h6>
                    </div>

                    <div class="card-body dg-card-body p-2">
                        <div class="row g-2">

                            <div class="col-md-6">
                                <h6 class="mb-1">Basic Information</h6>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Account No :</span>
                                            {{ $party->account_no }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Name :</span>
                                            {{ $party->name ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Phone :</span>
                                            {{ $party->phone ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Type :</span>
                                            {{ ucfirst($party->type) }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Due Date :</span>
                                            {{ optional($party->due_date)->format('Y-m-d') ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Status :</span>
                                            {{ $party->isActive() ? 'Active' : 'Inactive' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-1">Financial Information</h6>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Current Balance :</span>
                                            {{ number_format($party->current_balance, 2) }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Created Date :</span>
                                            {{ optional($party->created_at)->format('Y-m-d') ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Updated Date :</span>
                                            {{ optional($party->updated_at)->format('Y-m-d') ?: '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="dg-section">
                <div class="card dg-card">
                    <div class="card-header dg-card-header py-1">
                        <h6 class="mb-0">Documents &amp; Other Information</h6>
                    </div>

                    <div class="card-body dg-card-body p-2">
                        <div class="row g-2">

                            <div class="col-md-4">
                                <h6 class="mb-1">Photo</h6>
                                @if ($party->photo)
                                    <img
                                        src="{{ asset($party->photo) }}"
                                        alt="{{ $party->name }} photo"
                                        width="120"
                                        class="rounded border">
                                @else
                                    -
                                @endif
                            </div>

                            <div class="col-md-4">
                                <h6 class="mb-1">PDF Document</h6>
                                @if ($party->document)
                                    <a
                                        href="{{ asset($party->document) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-sm btn-outline-secondary dg-btn">
                                        View PDF
                                    </a>
                                @else
                                    -
                                @endif
                            </div>

                            <div class="col-md-4">
                                <h6 class="mb-1">ID Card</h6>
                                @if ($party->id_card)
                                    @if (str_ends_with(strtolower($party->id_card), '.pdf'))
                                        <a
                                            href="{{ asset($party->id_card) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-sm btn-outline-secondary dg-btn">
                                            View PDF
                                        </a>
                                    @else
                                        <img
                                            src="{{ asset($party->id_card) }}"
                                            alt="ID Card"
                                            width="120"
                                            class="rounded border">
                                    @endif
                                @else
                                    -
                                @endif
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-1">Address</h6>
                                {{ $party->address ?: '-' }}
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-1">Note</h6>
                                {{ $party->note ?: '-' }}
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<div class="modal fade" id="editPartyModal" tabindex="-1" aria-labelledby="editPartyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form
                method="POST"
                enctype="multipart/form-data"
                action="{{ route('company.party-account.update', $party->id) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="editPartyModalLabel">Edit Party Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @include('company.party-account.form', ['mode' => 'edit', 'party' => $party])
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary dg-btn">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
