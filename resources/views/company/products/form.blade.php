@extends('company.layout')

@section('content')

<div class="dg-page">

    <main class="dg-container">
        <div class="container-fluid">

            <h1 class="h4 mb-3">{{ $product ? 'Edit Product' : 'Add Product' }}</h1>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" enctype="multipart/form-data" action="{{ $product ? route('company.products.update', $product->id) : route('company.products.store') }}">
                @csrf

                @if ($product)
                    @method('PUT')
                @endif

                <div class="dg-section">
                    <div class="card dg-card">
                        <div class="card-header dg-card-header">
                            <h6 class="mb-0">Product Details</h6>
                        </div>

                        <div class="card-body dg-card-body">
                            <div class="row g-2">

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="name" class="form-label dg-label">
                                        Product Name
                                    </label>

                                    <input
                                        type="text"
                                        name="name"
                                        id="name"
                                        class="form-control dg-input"
                                        value="{{ $product->name ?? '' }}"
                                        required>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="barcode" class="form-label dg-label">
                                        Barcode
                                    </label>

                                    <input
                                        type="text"
                                        name="barcode"
                                        id="barcode"
                                        class="form-control dg-input"
                                        value="{{ $product->barcode ?? '' }}">
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="category_id" class="form-label dg-label">
                                        Category
                                    </label>

                                    <select
                                        name="category_id"
                                        id="category_id"
                                        class="form-select dg-select"
                                        required>

                                        @foreach ($categories as $cat)
                                            <option
                                                value="{{ $cat->id }}"
                                                {{ isset($product) && $product->category_id == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="unit_id" class="form-label dg-label">
                                        Unit
                                    </label>

                                    <select
                                        name="unit_id"
                                        id="unit_id"
                                        class="form-select dg-select"
                                        required>

                                        @foreach ($units as $u)
                                            <option
                                                value="{{ $u->id }}"
                                                {{ isset($product) && $product->unit_id == $u->id ? 'selected' : '' }}>
                                                {{ $u->name }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="brand_id" class="form-label dg-label">
                                        Brand
                                    </label>

                                    <select
                                        name="brand_id"
                                        id="brand_id"
                                        class="form-select dg-select">

                                        <option value="">-- Select Brand --</option>

                                        @foreach ($brands as $b)
                                            <option
                                                value="{{ $b->id }}"
                                                {{ isset($product) && $product->brand_id == $b->id ? 'selected' : '' }}>
                                                {{ $b->name }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="status" class="form-label dg-label">
                                        Status
                                    </label>

                                    <select
                                        name="status"
                                        id="status"
                                        class="form-select dg-select">

                                        <option
                                            value="active"
                                            {{ ($product->status ?? '') == 'active' ? 'selected' : '' }}>
                                            Active
                                        </option>

                                        <option
                                            value="inactive"
                                            {{ ($product->status ?? '') == 'inactive' ? 'selected' : '' }}>
                                            Inactive
                                        </option>

                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="cost_price" class="form-label dg-label">
                                        Cost Price
                                    </label>

                                    <input
                                        type="number"
                                        step="0.01"
                                        name="cost_price"
                                        id="cost_price"
                                        class="form-control dg-input"
                                        value="{{ $product->cost_price ?? '' }}"
                                        required>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="retail_price" class="form-label dg-label">
                                        Retail Price
                                    </label>

                                    <input
                                        type="number"
                                        step="0.01"
                                        name="retail_price"
                                        id="retail_price"
                                        class="form-control dg-input"
                                        value="{{ $product->retail_price ?? '' }}"
                                        required>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="wholesale_price" class="form-label dg-label">
                                        Wholesale Price
                                    </label>

                                    <input
                                        type="number"
                                        step="0.01"
                                        name="wholesale_price"
                                        id="wholesale_price"
                                        class="form-control dg-input"
                                        value="{{ $product->wholesale_price ?? '' }}">
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="stock_alert" class="form-label dg-label">
                                        Stock Alert
                                    </label>

                                    <input
                                        type="number"
                                        step="1"
                                        min="0"
                                        name="stock_alert"
                                        id="stock_alert"
                                        class="form-control dg-input"
                                        value="{{ $product->stock_alert ?? '' }}">
                                </div>

                                @unless ($product)
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <label for="opening_stock" class="form-label dg-label">
                                            Opening Stock
                                        </label>

                                        <input
                                            type="number"
                                            step="0.01"
                                            name="opening_stock"
                                            id="opening_stock"
                                            class="form-control dg-input"
                                            value="0">
                                    </div>
                                @endunless

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="batch_no" class="form-label dg-label">
                                        Batch No
                                    </label>

                                    <input
                                        type="text"
                                        name="batch_no"
                                        id="batch_no"
                                        class="form-control dg-input"
                                        value="{{ $product->batch_no ?? '' }}">
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="manufacture_date" class="form-label dg-label">
                                        Manufacture Date
                                    </label>

                                    <input
                                        type="date"
                                        name="manufacture_date"
                                        id="manufacture_date"
                                        class="form-control dg-input"
                                        value="{{ optional($product->manufacture_date ?? null)->format('Y-m-d') }}">
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="expiry_date" class="form-label dg-label">
                                        Expiry Date
                                    </label>

                                    <input
                                        type="date"
                                        name="expiry_date"
                                        id="expiry_date"
                                        class="form-control dg-input"
                                        value="{{ optional($product->expiry_date ?? null)->format('Y-m-d') }}">
                                </div>

                                <div class="col-lg-4 col-md-6 col-12 d-flex align-items-center">
                                    <div class="form-check dg-check mt-4">
                                        <input
                                            type="checkbox"
                                            name="allow_online"
                                            id="allow_online"
                                            class="form-check-input"
                                            value="1"
                                            {{ old('allow_online', $product->allow_online ?? false) ? 'checked' : '' }}>

                                        <label for="allow_online" class="form-check-label dg-label">
                                            Allow Online
                                        </label>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="image" class="form-label dg-label">
                                        Image
                                    </label>

                                    <input
                                        type="file"
                                        name="image"
                                        id="image"
                                        class="form-control dg-input">
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <span class="form-label dg-label">
                                        Preview
                                    </span>

                                    @if (!empty($product->image))
                                        <div>
                                            <img
                                                src="{{ asset($product->image) }}"
                                                alt="{{ $product->name ?? 'Product' }} image"
                                                width="60"
                                                height="60"
                                                class="dg-image rounded object-fit-cover">
                                        </div>
                                    @endif
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="description" class="form-label dg-label">
                                        Description
                                    </label>

                                    <textarea
                                        name="description"
                                        id="description"
                                        rows="2"
                                        class="form-control dg-input">{{ $product->description ?? '' }}</textarea>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-2">
                    <button type="submit" class="btn btn-primary dg-btn">
                        {{ $product ? 'Update' : 'Save' }}
                    </button>

                    <a href="{{ route('company.products.index') }}" class="btn btn-outline-secondary dg-btn">
                        Back
                    </a>
                </div>

            </form>

        </div>
    </main>

</div>

@endsection
