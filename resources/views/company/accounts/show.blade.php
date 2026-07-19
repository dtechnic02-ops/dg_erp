@extends('company.layout')

@section('title', 'Account Profile')

@section('content')

@php
    $company = auth()->user()->company;
@endphp

<div class="dg-page account-show-page">

    @unless($print ?? false)
        <div class="dg-toolbar d-flex justify-content-end align-items-center">
            <div class="d-flex gap-2">
                <a href="{{ url()->previous() }}" class="btn btn-secondary dg-btn">Back</a>

                <button type="button" onclick="window.print()" class="btn btn-primary dg-btn">Print</button>

                <a href="{{ route('company.accounts.index') }}" class="btn btn-outline-primary dg-btn">Account List</a>
            </div>
        </div>
    @endunless

    <div class="dg-container">

        @if (!$account)
            <div class="dg-alert alert alert-danger">
                Account not found.
            </div>
        @else

            <div id="printArea">

            {{-- =========================================================
            CARD 1 : ACCOUNT PROFILE (COMPANY INFO + ACCOUNT PROFILE)
            ========================================================= --}}

            <div class="dg-section">
                <div class="card dg-card">
                    <div class="card-header dg-card-header py-1">
                        <h6 class="mb-0">Account Profile</h6>
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
                                <h6 class="mb-1">Account Profile</h6>

                                <div class="d-flex align-items-center gap-2">

                                    @if ($account->image_path)
                                        <div>
                                            <img
                                                src="{{ asset($account->image_path) }}"
                                                alt="{{ $account->account_name }} image"
                                                width="80"
                                                height="80"
                                                class="rounded border">
                                        </div>
                                    @endif

                                    <div>
                                        <div>{{ $account->account_name }}</div>

                                        <div class="dg-label mb-1">{{ $account->account_type ?: '-' }}</div>

                                        <div class="mb-1">
                                            @if ($account->status == 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- =========================================================
            CARD 2 : ACCOUNT DETAILS (BASIC INFO + FINANCIAL INFO)
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
                                            <span class="dg-label d-inline mb-0">Account Name :</span>
                                            {{ $account->account_name ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Account Type :</span>
                                            {{ $account->account_type ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Bank Name :</span>
                                            {{ $account->bank_name ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Branch :</span>
                                            {{ $account->branch ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Account No :</span>
                                            {{ $account->account_no ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">IBAN :</span>
                                            {{ $account->iban ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Swift Code :</span>
                                            {{ $account->swift_code ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Currency :</span>
                                            {{ $account->currency ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Status :</span>
                                            {{ $account->status == 'active' ? 'Active' : 'Inactive' }}
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
                                            {{ number_format($account->opening_balance, 2) }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Current Balance :</span>
                                            {{ number_format($account->current_balance, 2) }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Account Code :</span>
                                            A-{{ str_pad($account->id, 5, '0', STR_PAD_LEFT) }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Created Date :</span>
                                            {{ optional($account->created_at)->format('Y-m-d') ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Updated Date :</span>
                                            {{ optional($account->updated_at)->format('Y-m-d') ?: '-' }}
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- =========================================================
            CARD 3 : OTHER INFORMATION (NOTE)
            ========================================================= --}}

            <div class="dg-section">
                <div class="card dg-card">
                    <div class="card-header dg-card-header py-1">
                        <h6 class="mb-0">Other Information</h6>
                    </div>

                    <div class="card-body dg-card-body p-2">
                        <div class="row g-2">

                            <div class="col-md-12">
                                <h6 class="mb-1">Note</h6>
                                {{ $account->note ?: '-' }}
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
