@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">

                <div class="flex-fill">
                    <h1 class="h4 mb-0">Product Categories</h1>
                </div>

                <div class="flex-shrink-0">
                    <div class="dg-summary mb-0">
                        <div class="dg-summary-item mb-0">
                            <span>Total Categories</span>
                            <span class="fw-bold">{{ $totalCategories }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <label for="search" class="visually-hidden">Search Category</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search Category" class="form-control dg-input">
                        <button type="submit" class="btn btn-primary dg-btn">Search</button>
                    </form>

                    <a href="{{ route('company.categories.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>

                    <button type="button" class="btn btn-success dg-btn" data-bs-toggle="modal" data-bs-target="#categoryModal">Add Category</button>
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Category List</h2>
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
                                        <th scope="col">Category Name</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="170">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($categories as $cat)
                                        <tr class="dg-row">
                                            <td>{{ $cat->name }}</td>
                                            <td>{{ $cat->description ?: '-' }}</td>
                                            <td>
                                                @if ($cat->status == 'active')
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Category actions">
                                                    <button type="button" class="btn btn-sm btn-outline-success dg-btn" data-bs-toggle="modal" data-bs-target="#edit{{ $cat->id }}">Edit</button>

                                                    <form method="POST" action="{{ route('company.categories.delete', $cat->id) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger dg-btn" onclick="return confirm('Delete Category?')">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="4" class="text-center">No Categories Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                            <p class="mb-0 text-muted">
                                Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }} records
                            </p>

                            <nav aria-label="Category list pagination">
                                {{ $categories->links() }}
                            </nav>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

{{-- Edit Category Modals --}}
@foreach ($categories as $cat)
    <div class="modal fade" id="edit{{ $cat->id }}" tabindex="-1" aria-labelledby="editCategoryLabel{{ $cat->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('company.categories.update', $cat->id) }}">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="editCategoryLabel{{ $cat->id }}">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        @include('company.categories.form', ['category' => $cat])
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary dg-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

{{-- Add Category Modal --}}
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="addCategoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.categories.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryLabel">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @include('company.categories.form')
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary dg-btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
