@extends('company.layout')

@section('title', 'Supplier Profile')

@section('content')

@php
    $company = auth()->user()->company;
@endphp

<div class="dg-page supplier-show-page">

    @unless($print ?? false)
        <div class="dg-toolbar d-flex justify-content-end align-items-center">
            <div class="d-flex gap-2">
                <a href="{{ url()->previous() }}" class="btn btn-secondary dg-btn">Back</a>

                <button type="button" onclick="window.print()" class="btn btn-primary dg-btn">Print</button>

                <a href="{{ route('company.suppliers.index') }}" class="btn btn-outline-primary dg-btn">Supplier List</a>
            </div>
        </div>
    @endunless

    <div class="dg-container">

        @if (!$supplier)
            <div class="dg-alert alert alert-danger">
                Supplier not found.
            </div>
        @else

            <div id="printArea">

            {{-- =========================================================
            CARD 1 : SUPPLIER PROFILE (COMPANY INFO + SUPPLIER PROFILE)
            ========================================================= --}}

            <div class="dg-section">
                <div class="card dg-card">
                    <div class="card-header dg-card-header py-1">
                        <h6 class="mb-0">Supplier Profile</h6>
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
                                <h6 class="mb-1">Supplier Profile</h6>

                                <div class="d-flex align-items-center gap-2">

                                    @if ($supplier->image_path)
                                        <div>
                                            <img
                                                src="{{ asset($supplier->image_path) }}"
                                                alt="{{ $supplier->name }} image"
                                                width="80"
                                                height="80"
                                                class="rounded border">
                                        </div>
                                    @endif

                                    <div>
                                        <div>{{ $supplier->name }}</div>

                                        <div class="dg-label mb-1">{{ $supplier->authority_name ?: '-' }}</div>

                                        <div class="mb-1">
                                            @if ($supplier->status == 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </div>

                                        <div>{{ $supplier->mobile ?: '-' }}</div>
                                        <div>{{ $supplier->email ?: '-' }}</div>
                                        <div>{{ $supplier->website ?: '-' }}</div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- =========================================================
            CARD 2 : SUPPLIER DETAILS (BASIC INFO + FINANCIAL INFO)
            ========================================================= --}}

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
                                            <span class="dg-label d-inline mb-0">Supplier Name :</span>
                                            {{ $supplier->name ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Authority Name :</span>
                                            {{ $supplier->authority_name ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Mobile :</span>
                                            {{ $supplier->mobile ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Telephone :</span>
                                            {{ $supplier->telephone ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Fax :</span>
                                            {{ $supplier->fax_no ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Email :</span>
                                            {{ $supplier->email ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Website :</span>
                                            {{ $supplier->website ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Tax Number :</span>
                                            {{ $supplier->tax_no ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Status :</span>
                                            {{ $supplier->status == 'active' ? 'Active' : 'Inactive' }}
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-1">Financial Information</h6>

                                <div class="row">

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Opening Balance :</span>
                                            {{ number_format($supplier->opening_balance, 2) }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Current Balance :</span>
                                            {{ number_format($supplier->current_balance, 2) }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Bank Name :</span>
                                            {{ $supplier->bank_name ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Bank Account :</span>
                                            {{ $supplier->bank_account_no ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Created Date :</span>
                                            {{ optional($supplier->created_at)->format('Y-m-d') ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Updated Date :</span>
                                            {{ optional($supplier->updated_at)->format('Y-m-d') ?: '-' }}
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- =========================================================
            CARD 3 : OTHER INFORMATION (ADDRESS + NOTE)
            ========================================================= --}}

            <div class="dg-section">
                <div class="card dg-card">
                    <div class="card-header dg-card-header py-1">
                        <h6 class="mb-0">Other Information</h6>
                    </div>

                    <div class="card-body dg-card-body p-2">
                        <div class="row g-2">

                            <div class="col-md-6">
                                <h6 class="mb-1">Address</h6>
                                {{ $supplier->address ?: '-' }}
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-1">Note</h6>
                                {{ $supplier->note ?: '-' }}
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            </div>

        @endif

    </div>

</div>

@if($print ?? false)
    <script>
        window.onload = function () {
            window.print();
        };
    </script>
@endif

@endsection
