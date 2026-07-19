@extends('company.layout')

@section('title', 'Customer Profile')

@section('content')

@php
    $company = auth()->user()->company;
@endphp

<div class="dg-page customer-show-page">

    @unless($print ?? false)
        <div class="dg-toolbar d-flex justify-content-end align-items-center">
            <div class="d-flex gap-2">
                <a href="{{ url()->previous() }}" class="btn btn-secondary dg-btn">Back</a>

                <button type="button" onclick="window.print()" class="btn btn-primary dg-btn">Print</button>

                <a href="{{ route('company.customers.index') }}" class="btn btn-outline-primary dg-btn">Customer List</a>
            </div>
        </div>
    @endunless

    <div class="dg-container">

        @if (!$customer)
            <div class="dg-alert alert alert-danger">
                Customer not found.
            </div>
        @else

            <div id="printArea">

            {{-- =========================================================
            CARD 1 : CUSTOMER PROFILE (COMPANY INFO + CUSTOMER PROFILE)
            ========================================================= --}}

            <div class="dg-section">
                <div class="card dg-card">
                    <div class="card-header dg-card-header py-1">
                        <h6 class="mb-0">Customer Profile</h6>
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
                                <h6 class="mb-1">Customer Profile</h6>

                                <div class="d-flex align-items-center gap-2">

                                    @if ($customer->image_path)
                                        <div>
                                            <img
                                                src="{{ asset($customer->image_path) }}"
                                                alt="{{ $customer->name }} image"
                                                width="80"
                                                height="80"
                                                class="rounded border">
                                        </div>
                                    @endif

                                    <div>
                                        <div>{{ $customer->name }}</div>

                                        <div class="dg-label mb-1">{{ $customer->authority_name ?: '-' }}</div>

                                        <div class="mb-1">
                                            @if ($customer->status == 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </div>

                                        <div>{{ $customer->mobile ?: '-' }}</div>
                                        <div>{{ $customer->email ?: '-' }}</div>
                                        <div>{{ $customer->website ?: '-' }}</div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- =========================================================
            CARD 2 : CUSTOMER DETAILS (BASIC INFO + FINANCIAL INFO)
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
                                            <span class="dg-label d-inline mb-0">Customer Name :</span>
                                            {{ $customer->name ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Authority Name :</span>
                                            {{ $customer->authority_name ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Mobile :</span>
                                            {{ $customer->mobile ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Telephone :</span>
                                            {{ $customer->telephone ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Fax :</span>
                                            {{ $customer->fax_no ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Email :</span>
                                            {{ $customer->email ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Website :</span>
                                            {{ $customer->website ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Tax Number :</span>
                                            {{ $customer->tax_no ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Status :</span>
                                            {{ $customer->status == 'active' ? 'Active' : 'Inactive' }}
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-1">Financial Information</h6>

                                <div class="row">

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Credit Days :</span>
                                            {{ (int) ($customer->credit_days ?? 0) }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Opening Balance :</span>
                                            {{ number_format($customer->opening_balance, 2) }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Current Balance :</span>
                                            {{ number_format($customer->current_balance, 2) }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Bank Name :</span>
                                            {{ $customer->bank_name ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Bank Account :</span>
                                            {{ $customer->bank_account_no ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Created Date :</span>
                                            {{ optional($customer->created_at)->format('Y-m-d') ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Updated Date :</span>
                                            {{ optional($customer->updated_at)->format('Y-m-d') ?: '-' }}
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
                                {{ $customer->address ?: '-' }}
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-1">Note</h6>
                                {{ $customer->note ?: '-' }}
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
