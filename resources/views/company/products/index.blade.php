@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Product Management</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-nowrap">
                    <form method="GET" class="d-flex gap-2">
                        <label for="search" class="visually-hidden">Search Name / Barcode</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search Name / Barcode" class="form-control dg-input">

                        <label for="stock_filter" class="visually-hidden">Stock Filter</label>
                        <select name="stock_filter" id="stock_filter" class="form-select dg-select">
                            <option value="">All Stock</option>
                            <option value="out" {{ request('stock_filter') == 'out' ? 'selected' : '' }}>Out</option>
                            <option value="low" {{ request('stock_filter') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="available" {{ request('stock_filter') == 'available' ? 'selected' : '' }}>Available</option>
                        </select>

                        <label for="brand_id" class="visually-hidden">Brand Filter</label>
                        <select name="brand_id" id="brand_id" class="form-select dg-select">
                            <option value="">All Brands</option>
                            @foreach ($brands as $b)
                                <option value="{{ $b->id }}" {{ request('brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-primary dg-btn">Search</button>
                    </form>

                    <a href="{{ route('company.products.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>

                    <a href="{{ route('company.products.export.excel', request()->query()) }}" class="btn btn-outline-success dg-btn">Excel</a>

                    <a href="{{ route('company.products.export.pdf', request()->query()) }}" class="btn btn-outline-secondary dg-btn">PDF</a>

                    <a href="{{ route('company.products.create') }}" class="btn btn-success dg-btn">Add Product</a>
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            <section class="dg-section">
                <div class="dg-summary d-flex flex-row flex-nowrap justify-content-center align-items-center gap-3 mb-0 w-100">

                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Total Products :</span>
                        <span class="fw-bold">{{ $totalProducts }}</span>
                    </div>

                    <span>|</span>

                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Total Stock Quantity :</span>
                        <span class="fw-bold">{{ $totalStockQuantity }}</span>
                    </div>

                    <span>|</span>

                    <div class="dg-summary-item mb-0 border-0 p-0">
                        <span>Out of Stock :</span>
                        <span class="fw-bold">{{ $totalOutOfStock }}</span>
                    </div>

                </div>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Product List</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <form method="GET" class="d-flex justify-content-end align-items-center gap-2 mb-2">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="stock_filter" value="{{ request('stock_filter') }}">
                            <input type="hidden" name="brand_id" value="{{ request('brand_id') }}">

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
                                        <th scope="col">Name</th>
                                        <th scope="col">Barcode</th>
                                        <th scope="col">Brand</th>
                                        <th scope="col">Cost</th>
                                        <th scope="col">Retail</th>
                                        <th scope="col">Wholesale</th>
                                        <th scope="col">Stock</th>
                                        <th scope="col">Batch No</th>
                                        <th scope="col">Manufacture Date</th>
                                        <th scope="col">Expiry Date</th>
                                        <th scope="col">Online</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="170">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($products as $p)
                                        <tr class="dg-row">
                                            <td>
                                                @if ($p->image)
                                                    <img src="{{ asset($p->image) }}" alt="{{ $p->name }}" width="40" height="40" class="dg-image">
                                                @endif
                                            </td>
                                            <td>{{ $p->name }}</td>
                                            <td>{{ $p->barcode ?? '-' }}</td>
                                            <td>{{ $p->brand->name ?? '-' }}</td>
                                            <td>{{ number_format($p->cost_price, 2) }}</td>
                                            <td>{{ number_format($p->retail_price, 2) }}</td>
                                            <td>{{ number_format($p->wholesale_price ?? 0, 2) }}</td>
                                            <td>
                                                @if ($p->current_stock <= 0)
                                                    <span class="badge bg-danger">Out</span>
                                                @elseif ($p->stock_alert && $p->current_stock <= $p->stock_alert)
                                                    <span class="badge bg-warning text-dark">{{ $p->current_stock }}</span>
                                                @else
                                                    <span class="badge bg-success">{{ $p->current_stock }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $p->batch_no ?? '-' }}</td>
                                            <td>{{ optional($p->manufacture_date)->format('Y-m-d') ?? '-' }}</td>
                                            <td>{{ optional($p->expiry_date)->format('Y-m-d') ?? '-' }}</td>
                                            <td>
                                                @if ($p->allow_online)
                                                    <span class="badge bg-success">Yes</span>
                                                @else
                                                    <span class="badge bg-secondary">No</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($p->status == 'active')
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Product actions">
                                                    <a href="{{ route('company.products.edit', $p->id) }}" class="btn btn-sm btn-outline-success dg-btn">Edit</a>

                                                    <a href="{{ route('company.products.show', $p->id) }}" class="btn btn-sm btn-outline-info dg-btn">View</a>

                                                    <form method="POST" action="{{ route('company.products.destroy', $p->id) }}" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger dg-btn" onclick="return confirm('Delete Product?')">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="14" class="text-center">No Products Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                            <p class="mb-0 text-muted">
                                Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} records
                            </p>

                            <nav aria-label="Product list pagination">
                                {{ $products->links() }}
                            </nav>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@endsection
