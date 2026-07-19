@extends('company.layout')

@section('title', 'Service Details')

@section('content')

@php
    $company = auth()->user()->company;
@endphp

<div class="dg-page service-show-page">

    @unless($print ?? false)
        <header class="dg-toolbar no-print">
            <div class="container-fluid">
                <div class="d-flex justify-content-end align-items-center gap-2">
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    <a href="{{ route('company.services.index') }}" class="btn btn-outline-primary dg-btn">Service List</a>
                    <a href="{{ route('company.services.edit', $service->id) }}" class="btn btn-outline-success dg-btn">Edit</a>
                    <button type="button" class="btn btn-primary dg-btn" onclick="window.print()">Print</button>
                </div>
            </div>
        </header>
    @endunless

    <main class="dg-container">
        <div class="container-fluid">

            {{-- Card 1: Company Information / Service Profile --}}
            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Service Profile</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <div class="row g-2">

                            <div class="col-md-6">
                                <h6 class="mb-2">Company Information</h6>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Company Name</span>
                                    <span class="fw-bold">{{ $company->company_name ?? '-' }}</span>
                                </div>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Address</span>
                                    <span class="fw-bold">{{ $company->address ?? '-' }}</span>
                                </div>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Mobile</span>
                                    <span class="fw-bold">{{ $company->mobile ?? '-' }}</span>
                                </div>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Email</span>
                                    <span class="fw-bold">{{ $company->email ?? '-' }}</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-2">Service Profile</h6>

                                @if ($service->upload_path)
                                    <div class="mb-2">
                                        <img
                                            src="{{ asset($service->upload_path) }}"
                                            alt="{{ $service->name }}"
                                            width="60"
                                            height="60"
                                            class="dg-image">
                                    </div>
                                @endif

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Service Name</span>
                                    <span class="fw-bold">{{ $service->name }}</span>
                                </div>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Category</span>
                                    <span class="fw-bold">{{ $service->category->name ?? '-' }}</span>
                                </div>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Price</span>
                                    <span class="fw-bold">{{ number_format($service->price, 2) }}</span>
                                </div>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Status</span>
                                    <span class="fw-bold">
                                        @if ($service->status == 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                </article>
            </section>

            {{-- Card 2: Basic Information / Service Information --}}
            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Service Details</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <div class="row g-2">

                            <div class="col-md-6">
                                <h6 class="mb-2">Basic Information</h6>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Service Name</span>
                                    <span class="fw-bold">{{ $service->name }}</span>
                                </div>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Category</span>
                                    <span class="fw-bold">{{ $service->category->name ?? '-' }}</span>
                                </div>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Status</span>
                                    <span class="fw-bold">
                                        @if ($service->status == 'active')
                                            Active
                                        @else
                                            Inactive
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="mb-2">Service Information</h6>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Price</span>
                                    <span class="fw-bold">{{ number_format($service->price, 2) }}</span>
                                </div>

                                @if ($service->vat)
                                    <div class="dg-row d-flex justify-content-between mb-2">
                                        <span>VAT</span>
                                        <span class="fw-bold">{{ $service->vat->name }}</span>
                                    </div>
                                @endif

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Created Date</span>
                                    <span class="fw-bold">{{ optional($service->created_at)->format('Y-m-d') ?? '-' }}</span>
                                </div>

                                <div class="dg-row d-flex justify-content-between mb-2">
                                    <span>Updated Date</span>
                                    <span class="fw-bold">{{ optional($service->updated_at)->format('Y-m-d') ?? '-' }}</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </article>
            </section>

            {{-- Card 3: Description --}}
            @if (!empty($service->description))
                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Description</h2>
                        </header>

                        <div class="card-body dg-card-body">
                            <p class="mb-0">{{ $service->description }}</p>
                        </div>
                    </article>
                </section>
            @endif

        </div>
    </main>

</div>

@if ($print ?? false)
    <script>
        window.onload = function () {
            window.print();
        };
    </script>
@endif

@endsection
