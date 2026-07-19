@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">

                <div class="flex-fill">
                    <h1 class="h4 mb-0">Brand Management</h1>
                </div>

                <div class="flex-shrink-0">
                    <div class="dg-summary mb-0">
                        <div class="dg-summary-item mb-0">
                            <span>Total Brands</span>
                            <span class="fw-bold">{{ $totalBrands }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <label for="search" class="visually-hidden">Search Brand</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search Brand" class="form-control dg-input">
                        <button type="submit" class="btn btn-primary dg-btn">Search</button>
                    </form>

                    <a href="{{ route('company.brands.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>

                    <button type="button" class="btn btn-success dg-btn" data-bs-toggle="modal" data-bs-target="#brandModal">Add Brand</button>
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Brand List</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <form method="GET" class="d-flex justify-content-end align-items-center gap-2 mb-2">
                            <input type="hidden" name="search" value="{{ request('search') }}">

                            <label for="per_page" class="mb-0 fw-bold">Show</label>
                            <select name="per_page" id="per_page" class="form-select form-select-sm dg-select w-auto" onchange="this.form.submit()">
                                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                                <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>200</option>
                                <option value="500" {{ $perPage == 500 ? 'selected' : '' }}>500</option>
                            </select>
                        </form>

                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">Image</th>
                                        <th scope="col">Brand Name</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="170">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($brands as $b)
                                        <tr class="dg-row">
                                            <td>
                                                @if ($b->image)
                                                    <img src="{{ asset($b->image) }}" alt="{{ $b->name }}" width="40" height="40" class="dg-image">
                                                @endif
                                            </td>
                                            <td>{{ $b->name }}</td>
                                            <td>{{ $b->description }}</td>
                                            <td>
                                                @if ($b->status)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Brand actions">
                                                    <button type="button" class="btn btn-sm btn-outline-success dg-btn" data-bs-toggle="modal" data-bs-target="#edit{{ $b->id }}">Edit</button>

                                                    <a href="{{ route('company.brands.show', $b->id) }}" class="btn btn-sm btn-outline-info dg-btn">View</a>

                                                    <form method="POST" action="{{ route('company.brands.delete', $b->id) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger dg-btn" onclick="return confirm('Delete this brand?')">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="5" class="text-center">No Brands Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                            <p class="mb-0 text-muted">
                                Showing {{ $brands->firstItem() ?? 0 }} to {{ $brands->lastItem() ?? 0 }} of {{ $brands->total() }} records
                            </p>

                            <nav aria-label="Brand list pagination">
                                {{ $brands->links() }}
                            </nav>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

{{-- Edit Brand Modals --}}
@foreach ($brands as $b)
    <div class="modal fade" id="edit{{ $b->id }}" tabindex="-1" aria-labelledby="editBrandLabel{{ $b->id }}" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" action="{{ route('company.brands.update', $b->id) }}">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="editBrandLabel{{ $b->id }}">Edit Brand</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        @include('company.brands.form', ['brand' => $b])
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary dg-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

{{-- Add Brand Modal --}}
<div class="modal fade" id="brandModal" tabindex="-1" aria-labelledby="addBrandLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" action="{{ route('company.brands.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="addBrandLabel">Add Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @include('company.brands.form')
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary dg-btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
