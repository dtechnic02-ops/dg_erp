@extends('company.layout')

@section('title', isset($service) ? 'Edit Service' : 'Add Service')

@section('content')

<div class="dg-page">
    <div class="dg-container">
        <div class="container-fluid">

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h1 class="h6 mb-0">{{ isset($service) ? 'Edit Service' : 'Add Service' }}</h1>
                    </header>

                    <div class="card-body dg-card-body">
                        <form
                            method="POST"
                            enctype="multipart/form-data"
                            action="{{ isset($service) ? route('company.services.update', $service->id) : route('company.services.store') }}">

                            @csrf

                            <div class="dg-section">
                                <div class="card dg-card">
                                    <div class="card-header dg-card-header">
                                        <h6 class="mb-0">Service Details</h6>
                                    </div>

                                    <div class="card-body dg-card-body">
                                        <div class="row g-2">

                                            <div class="col-lg-4 col-md-6 col-12">
                                                <label for="name" class="form-label dg-label">Service Name</label>
                                                <input
                                                    type="text"
                                                    name="name"
                                                    id="name"
                                                    class="form-control dg-input"
                                                    value="{{ old('name', $service->name ?? '') }}"
                                                    required>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-12">
                                                <label for="service_category_id" class="form-label dg-label">Category</label>
                                                <select
                                                    name="service_category_id"
                                                    id="service_category_id"
                                                    class="form-select dg-select">
                                                    <option value="">Select Category</option>
                                                    @foreach ($categories as $category)
                                                        <option
                                                            value="{{ $category->id }}"
                                                            {{ old('service_category_id', $service->service_category_id ?? '') == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-12">
                                                <label for="price" class="form-label dg-label">Price</label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    name="price"
                                                    id="price"
                                                    class="form-control dg-input"
                                                    value="{{ old('price', $service->price ?? '') }}"
                                                    required>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-12">
                                                <label for="status" class="form-label dg-label">Status</label>
                                                <select
                                                    name="status"
                                                    id="status"
                                                    class="form-select dg-select">
                                                    <option
                                                        value="active"
                                                        {{ old('status', $service->status ?? 'active') == 'active' ? 'selected' : '' }}>
                                                        Active
                                                    </option>
                                                    <option
                                                        value="inactive"
                                                        {{ old('status', $service->status ?? '') == 'inactive' ? 'selected' : '' }}>
                                                        Inactive
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-12">
                                                <label for="image" class="form-label dg-label">Image</label>
                                                <input
                                                    type="file"
                                                    name="image"
                                                    id="image"
                                                    class="form-control dg-input">
                                            </div>

                                            @if (isset($service) && !empty($service->upload_path))
                                                <div class="col-lg-4 col-md-6 col-12">
                                                    <span class="form-label dg-label">Preview</span>
                                                    <div>
                                                        <img
                                                            src="{{ asset($service->upload_path) }}"
                                                            alt="{{ $service->name ?? 'Service' }} image"
                                                            width="60"
                                                            height="60"
                                                            class="dg-image">
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="col-lg-12 col-md-12 col-12">
                                                <label for="description" class="form-label dg-label">Description</label>
                                                <textarea
                                                    name="description"
                                                    id="description"
                                                    rows="2"
                                                    class="form-control dg-input">{{ old('description', $service->description ?? '') }}</textarea>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary dg-btn">
                                    {{ isset($service) ? 'Update Service' : 'Save Service' }}
                                </button>

                                <a href="{{ route('company.services.index') }}" class="btn btn-outline-secondary dg-btn">
                                    Cancel
                                </a>
                            </div>

                        </form>
                    </div>
                </article>
            </section>

        </div>
    </div>
</div>

@endsection
