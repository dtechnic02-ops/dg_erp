<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<title>Product List - Print</title>

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<style>

@page {
    size: A4;
    margin: 10mm;
}

body {
    font-size: 12px;
}

.print-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
}

.print-thumb {
    width: 32px;
    height: 32px;
    object-fit: cover;
}

</style>

</head>
<body onload="window.print()">

@php
    $company = auth()->user()->company;
@endphp

<div class="container-fluid">

    {{-- =========================================================
    COMPANY HEADER
    ========================================================= --}}

    <div class="row align-items-center border-bottom pb-2 mb-2">

        <div class="col-2">
            @if ($company && $company->logo_path)
                <img
                    src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                    alt="Company Logo"
                    class="print-logo">
            @else
                <img
                    src="{{ asset('images/no-image.png') }}"
                    alt="No logo available"
                    class="print-logo">
            @endif
        </div>

        <div class="col-10">
            <div class="fw-bold">{{ $company->company_name ?? '-' }}</div>
            <div>{{ $company->address ?? '-' }}</div>
            <div>Phone: {{ $company->mobile ?? '-' }} &nbsp; Email: {{ $company->email ?? '-' }}</div>
        </div>

    </div>

    {{-- =========================================================
    TITLE
    ========================================================= --}}

    <h5 class="text-center fw-bold mb-2">PRODUCT LIST</h5>

    {{-- =========================================================
    ACTIVE FILTER (only shown when a filter was actually applied)
    ========================================================= --}}

    @if (request()->filled('search'))
        <div class="mb-2">
            <span class="fw-bold">Filtered by Search :</span>
            {{ request('search') }}
        </div>
    @endif

    {{-- =========================================================
    PRODUCT LIST TABLE
    ========================================================= --}}

    <table class="table table-sm table-bordered mb-2">
        <thead>
            <tr>
                <th>#</th>
                <th>Image</th>
                <th>Name</th>
                <th>Barcode</th>
                <th>Brand</th>
                <th class="text-end">Retail</th>
                <th class="text-end">Stock</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

            @forelse ($products as $p)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        @if ($p->image)
                            <img
                                src="{{ asset($p->image) }}"
                                alt="{{ $p->name }}"
                                class="print-thumb">
                        @endif
                    </td>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->barcode ?: '-' }}</td>
                    <td>{{ $p->brand->name ?? '-' }}</td>
                    <td class="text-end">{{ number_format($p->retail_price, 2) }}</td>
                    <td class="text-end">{{ $p->current_stock }}</td>
                    <td>{{ $p->status == 'active' ? 'Active' : 'Inactive' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No Products Found</td>
                </tr>
            @endforelse

        </tbody>
    </table>

    {{-- =========================================================
    SUMMARY (filtered result only)
    ========================================================= --}}

    <table class="table table-sm table-bordered mb-2">
        <tbody>
            <tr>
                <th class="w-25">Total Products</th>
                <td>{{ $totalProducts }}</td>
            </tr>
            <tr>
                <th>Total Stock Quantity</th>
                <td>{{ $totalStockQuantity }}</td>
            </tr>
            <tr>
                <th>Out of Stock</th>
                <td>{{ $totalOutOfStock }}</td>
            </tr>
        </tbody>
    </table>

    {{-- =========================================================
    FOOTER
    ========================================================= --}}

    <div class="row border-top pt-2 mt-2 text-muted">
        <div class="col-4">Generated Date: {{ now()->format('Y-m-d H:i') }}</div>
        <div class="col-4">Printed By: {{ auth()->user()->name }}</div>
        <div class="col-4 text-end">Total Records: {{ $products->count() }}</div>
    </div>

</div>

</body>
</html>
