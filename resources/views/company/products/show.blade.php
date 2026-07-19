@extends('company.layout')

@section('title', 'Product Profile')

@section('content')

@php
    $company = auth()->user()->company;
@endphp

<div class="dg-page product-show-page">

    @unless($print ?? false)
        <div class="dg-toolbar d-flex justify-content-end align-items-center">
            <div class="d-flex gap-2">
                <a href="{{ url()->previous() }}" class="btn btn-secondary dg-btn">Back</a>

                <button type="button" onclick="window.print()" class="btn btn-primary dg-btn">Print</button>

                <a href="{{ route('company.products.index') }}" class="btn btn-outline-primary dg-btn">Product List</a>
            </div>
        </div>
    @endunless

    <div class="dg-container">

        @if (!$product)
            <div class="dg-alert alert alert-danger">
                Product not found.
            </div>
        @else

            <div id="printArea" style="page-break-inside: avoid;">

            {{-- =========================================================
            PRODUCT SHEET : HEADER + TITLE + MAIN (AMAZON STYLE)
            ========================================================= --}}

            <div class="dg-section mb-1">
                <div class="card dg-card">
                    <div class="card-body dg-card-body p-2">

                        {{-- Company Header --}}
                        <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom">
                            <div class="flex-shrink-0">
                                @if ($company && $company->logo_path)
                                    <img
                                        src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                                        alt="Company Logo"
                                        width="56"
                                        height="56"
                                        class="rounded border">
                                @else
                                    <img
                                        src="{{ asset('images/no-image.png') }}"
                                        alt="No logo available"
                                        width="56"
                                        height="56"
                                        class="rounded border">
                                @endif
                            </div>

                            <div class="flex-grow-1">
                                <div class="fw-semibold mb-0">{{ $company->company_name ?? '-' }}</div>
                                <div class="small mb-0">{{ $company->email ?? '-' }} &nbsp;|&nbsp; {{ $company->mobile ?? '-' }}</div>
                                <div class="small mb-0">{{ $company->address ?? '-' }}</div>
                            </div>
                        </div>

                        {{-- Product Title --}}
                        <div class="pb-2 mb-2 border-bottom">
                            <h5 class="mb-0 fw-bold lh-sm">{{ $product->name ?: '-' }}</h5>
                            <div class="small dg-label mb-0 mt-1">
                                Product Code :
                                P-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}
                            </div>
                        </div>

                        {{-- Main Section : Image (40%) + Information Grid (60%) --}}
                        <div class="row g-2 align-items-start">

                            <div class="col-5">
                                <div class="dg-product-image">
                                    @if ($product->image)
                                        <img
                                            src="{{ asset($product->image) }}"
                                            alt="{{ $product->name }} image">
                                    @else
                                        <img
                                            src="{{ asset('images/no-image.png') }}"
                                            alt="No product image available">
                                    @endif
                                </div>
                            </div>

                            <div class="col-7">
                                <h6 class="mb-1 fw-semibold">Product Information</h6>

                                <div class="row g-1">

                                    <div class="col-6">
                                        <div class="dg-row small mb-0 py-0">
                                            <span class="dg-label d-inline mb-0">Category :</span>
                                            {{ $product->category->name ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="dg-row small mb-0 py-0">
                                            <span class="dg-label d-inline mb-0">Brand :</span>
                                            {{ $product->brand->name ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="dg-row small mb-0 py-0">
                                            <span class="dg-label d-inline mb-0">Unit :</span>
                                            {{ $product->unit->name ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="dg-row small mb-0 py-0">
                                            <span class="dg-label d-inline mb-0">Purchase Price :</span>
                                            {{ number_format($product->cost_price, 2) }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="dg-row small mb-0 py-0">
                                            <span class="dg-label d-inline mb-0">Selling Price :</span>
                                            {{ number_format($product->retail_price, 2) }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="dg-row small mb-0 py-0">
                                            <span class="dg-label d-inline mb-0">Current Stock :</span>
                                            {{ $product->current_stock }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="dg-row small mb-0 py-0">
                                            <span class="dg-label d-inline mb-0">Minimum Stock :</span>
                                            {{ $product->stock_alert ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="dg-row small mb-0 py-0">
                                            <span class="dg-label d-inline mb-0">Status :</span>
                                            @if ($product->status == 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="dg-row small mb-0 py-0">
                                            <span class="dg-label d-inline mb-0">Barcode :</span>
                                            {{ $product->barcode ?: '-' }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="dg-row small mb-0 py-0 d-flex align-items-center">
                                            <span class="dg-label d-inline mb-0 me-1">QR Code :</span>
                                            @if ($product->barcode)
                                                <img
                                                    src="https://api.qrserver.com/v1/create-qr-code/?size=72x72&data={{ urlencode($product->barcode) }}"
                                                    alt="Product QR Code"
                                                    width="72"
                                                    height="72"
                                                    class="border rounded">
                                            @else
                                                -
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
            BOTTOM : DESCRIPTION + NOTES
            ========================================================= --}}

            <div class="dg-section mb-0">
                <div class="card dg-card">
                    <div class="card-body dg-card-body p-2">
                        <div class="row g-2">

                            <div class="col-md-8">
                                <h6 class="mb-1 fw-semibold">Description</h6>
                                <div class="small mb-0">{{ $product->description ?: '-' }}</div>
                            </div>

                            <div class="col-md-4">
                                <h6 class="mb-1 fw-semibold">Notes</h6>
                                <div class="small mb-0">-</div>
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
