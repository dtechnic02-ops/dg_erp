@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">

                <div class="flex-fill">
                    <h1 class="h4 mb-0">Unit Management</h1>
                </div>

                <div class="flex-shrink-0">
                    <div class="dg-summary mb-0">
                        <div class="dg-summary-item mb-0">
                            <span>Total Units</span>
                            <span class="fw-bold">{{ $totalUnits }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <label for="search" class="visually-hidden">Search Unit</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search Unit" class="form-control dg-input">
                        <button type="submit" class="btn btn-primary dg-btn">Search</button>
                    </form>

                    <a href="{{ route('company.units.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>

                    <button type="button" class="btn btn-success dg-btn" data-bs-toggle="modal" data-bs-target="#unitModal">Add Unit</button>
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Unit List</h2>
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
                                        <th scope="col">Unit Name</th>
                                        <th scope="col">Short Name</th>
                                        <th scope="col" width="170">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($units as $unit)
                                        <tr class="dg-row">
                                            <td>{{ $unit->name }}</td>
                                            <td>{{ $unit->short_name ?: '-' }}</td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Unit actions">
                                                    <button type="button" class="btn btn-sm btn-outline-success dg-btn" data-bs-toggle="modal" data-bs-target="#edit{{ $unit->id }}">Edit</button>

                                                    <form method="POST" action="{{ route('company.units.destroy', $unit->id) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger dg-btn" onclick="return confirm('Delete this unit?')">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="3" class="text-center">No Units Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                            <p class="mb-0 text-muted">
                                Showing {{ $units->firstItem() ?? 0 }} to {{ $units->lastItem() ?? 0 }} of {{ $units->total() }} records
                            </p>

                            <nav aria-label="Unit list pagination">
                                {{ $units->links() }}
                            </nav>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

{{-- Edit Unit Modals --}}
@foreach ($units as $unit)
    <div class="modal fade" id="edit{{ $unit->id }}" tabindex="-1" aria-labelledby="editUnitLabel{{ $unit->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('company.units.update', $unit->id) }}">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="editUnitLabel{{ $unit->id }}">Edit Unit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        @include('company.units.form', ['unit' => $unit])
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary dg-btn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

{{-- Add Unit Modal --}}
<div class="modal fade" id="unitModal" tabindex="-1" aria-labelledby="addUnitLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('company.units.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="addUnitLabel">Add Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @include('company.units.form')
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary dg-btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
