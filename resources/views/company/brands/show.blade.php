@extends('company.layout')

@section('title', 'Brand Profile')

@section('content')

@php
    $company = auth()->user()->company;
@endphp

<div class="dg-page brand-show-page">

    @unless($print ?? false)
        <div class="dg-toolbar d-flex justify-content-end align-items-center">
            <div class="d-flex gap-2">
                <a href="{{ url()->previous() }}" class="btn btn-secondary dg-btn">Back</a>

                <button type="button" onclick="window.print()" class="btn btn-primary dg-btn">Print</button>

                <a href="{{ route('company.brands.index') }}" class="btn btn-outline-primary dg-btn">Brand List</a>
            </div>
        </div>
    @endunless

    <div class="dg-container">

        @if (!$brand)
            <div class="dg-alert alert alert-danger">
                Brand not found.
            </div>
        @else

            <div id="printArea">

            {{-- =========================================================
            CARD 1 : BRAND PROFILE (COMPANY INFO + BRAND PROFILE)
            ========================================================= --}}

            <div class="dg-section">
                <div class="card dg-card">
                    <div class="card-header dg-card-header py-1">
                        <h6 class="mb-0">Brand Profile</h6>
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
                                <h6 class="mb-1">Brand Profile</h6>

                                <div class="d-flex align-items-center gap-2">

                                    @if ($brand->image)
                                        <div>
                                            <img
                                                src="{{ asset($brand->image) }}"
                                                alt="{{ $brand->name }} image"
                                                width="80"
                                                height="80"
                                                class="dg-image rounded border">
                                        </div>
                                    @endif

                                    <div>
                                        <div>{{ $brand->name }}</div>

                                        <div class="mb-1">
                                            @if ($brand->status)
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
            CARD 2 : BRAND DETAILS (BASIC INFO + BRAND INFO)
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
                                            <span class="dg-label d-inline mb-0">Brand Name :</span>
                                            {{ $brand->name ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Status :</span>
                                            {{ $brand->status ? 'Active' : 'Inactive' }}
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-1">Brand Information</h6>

                                <div class="row">

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Created Date :</span>
                                            {{ optional($brand->created_at)->format('Y-m-d') ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Updated Date :</span>
                                            {{ optional($brand->updated_at)->format('Y-m-d') ?: '-' }}
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- =========================================================
            CARD 3 : DESCRIPTION (FULL WIDTH)
            ========================================================= --}}

            @if ($brand->description)
                <div class="dg-section">
                    <div class="card dg-card">
                        <div class="card-header dg-card-header py-1">
                            <h6 class="mb-0">Description</h6>
                        </div>

                        <div class="card-body dg-card-body p-2">
                            {{ $brand->description }}
                        </div>
                    </div>
                </div>
            @endif

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
