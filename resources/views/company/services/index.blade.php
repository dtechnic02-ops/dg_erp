@extends('company.layout')

@section('content')

<div class="container-fluid py-3">

    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>
            <h4 class="mb-0">🔧 Services</h4>

            <small class="text-muted">
                Manage service list
            </small>
        </div>

        <button type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#addModal">

            ➕ Add Service

        </button>

    </div>


    <!-- SUCCESS -->

    @if(session('success'))

        <div class="alert alert-success">

            {{ session('success') }}

        </div>

    @endif


    <!-- FILTER -->

    <div class="card border-0 shadow-sm mb-3">

        <div class="card-body">

            <form method="GET"
                  action="{{ route('company.services.index') }}">

                <div class="row g-2">


                    <!-- SEARCH -->

                    <div class="col-md-4">

                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Search service..."
                               value="{{ request('search') }}">

                    </div>


                    <!-- CATEGORY -->

                    <div class="col-md-3">

                        <select name="category_id"
                                class="form-select">

                            <option value="">
                                All Categories
                            </option>

                            @foreach($categories as $category)

                                <option value="{{ $category->id }}"
                                    {{ request('category_id') == $category->id ? 'selected' : '' }}>

                                    {{ $category->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    <!-- STATUS -->

                    <div class="col-md-2">

                        <select name="status"
                                class="form-select">

                            <option value="">
                                All Status
                            </option>

                            <option value="active"
                                {{ request('status') == 'active' ? 'selected' : '' }}>

                                Active

                            </option>

                            <option value="inactive"
                                {{ request('status') == 'inactive' ? 'selected' : '' }}>

                                Inactive

                            </option>

                        </select>

                    </div>


                    <!-- BUTTON -->

                    <div class="col-md-3 text-end">

                        <button class="btn btn-dark">

                            🔍 Search

                        </button>

                        <a href="{{ route('company.services.index') }}"
                           class="btn btn-secondary">

                            Reset

                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- TABLE -->

    <div class="card border-0 shadow-sm">

        <div class="table-responsive">

            <table class="table table-bordered align-middle mb-0">

                <thead class="table-dark">

                    <tr>

                        <th width="60">#</th>

                        <th width="90">Image</th>

                        <th>Service</th>

                        <th>Code</th>

                        <th>Category</th>

                        <th>Price</th>

                        <th>VAT</th>

                        <th>Status</th>

                        <th width="180">Action</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($services as $key => $service)

                        <tr>


                            <!-- SERIAL -->

                            <td>

                                {{ $services->firstItem() + $key }}

                            </td>


                            <!-- IMAGE -->

                            <td>

                                @if($service->upload_path)

                                    <img src="{{ asset($service->upload_path) }}"
                                         width="60"
                                         class="rounded border">

                                @else

                                    <span class="text-muted">

                                        No Image

                                    </span>

                                @endif

                            </td>


                            <!-- NAME -->

                            <td>

                                <strong>

                                    {{ $service->name }}

                                </strong>

                                @if($service->description)

                                    <br>

                                    <small class="text-muted">

                                        {{ Str::limit($service->description, 50) }}

                                    </small>

                                @endif

                            </td>


                            <!-- CODE -->

                            <td>

                                {{ $service->service_code }}

                            </td>


                            <!-- CATEGORY -->

                            <td>

                                {{ $service->category->name ?? '-' }}

                            </td>


                            <!-- PRICE -->

                            <td>

                                {{ number_format($service->price, 2) }}

                            </td>


                            <!-- VAT -->

                            <td>

                                @if($service->vat)

                                    {{ $service->vat->name }}

                                @else

                                    -

                                @endif

                            </td>


                            <!-- STATUS -->

                            <td>

                                @if($service->status == 'active')

                                    <span class="badge bg-success">

                                        Active

                                    </span>

                                @else

                                    <span class="badge bg-danger">

                                        Inactive

                                    </span>

                                @endif

                            </td>


                            <!-- ACTION -->

                            <td>

                                <button type="button"
                                        class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal{{ $service->id }}">

                                    Edit

                                </button>


                                <form action="{{ route('company.services.delete', $service->id) }}"
                                      method="POST"
                                      class="d-inline">

                                    @csrf

                                    <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete service?')">

                                        Delete

                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="9"
                                class="text-center py-4">

                                No services found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <!-- PAGINATION -->

        <div class="card-footer">

            {{ $services->links() }}

        </div>

    </div>

</div>



<!-- EDIT MODALS -->

@foreach($services as $service)

<div class="modal fade"
     id="editModal{{ $service->id }}"
     tabindex="-1"
     data-bs-backdrop="static"
     data-bs-keyboard="false">

    <div class="modal-dialog modal-lg">

        <form action="{{ route('company.services.update', $service->id) }}"
              method="POST"
              enctype="multipart/form-data"
              class="modal-content">

            @csrf

            <div class="modal-header">

                <h5 class="modal-title">

                    Edit Service

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>


            <div class="modal-body">

                <div class="row">


                    <!-- NAME -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Service Name
                        </label>

                        <input type="text"
                               name="name"
                               class="form-control"
                               value="{{ $service->name }}"
                               required>

                    </div>


                    <!-- CODE -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Service Code
                        </label>

                        <input type="text"
                               name="service_code"
                               class="form-control"
                               value="{{ $service->service_code }}">

                    </div>


                    <!-- CATEGORY -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Category
                        </label>

                        <select name="service_category_id"
                                class="form-select">

                            <option value="">
                                Select Category
                            </option>

                            @foreach($categories as $category)

                                <option value="{{ $category->id }}"
                                    {{ $service->service_category_id == $category->id ? 'selected' : '' }}>

                                    {{ $category->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    <!-- PRICE -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Price
                        </label>

                        <input type="number"
                               step="0.01"
                               name="price"
                               class="form-control"
                               value="{{ $service->price }}"
                               required>

                    </div>


                    <!-- VAT -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            VAT
                        </label>

                        <select name="vat_id"
                                class="form-select">

                            <option value="">
                                Select VAT
                            </option>

                            @foreach($vats as $vat)

                                <option value="{{ $vat->id }}"
                                    {{ $service->vat_id == $vat->id ? 'selected' : '' }}>

                                    {{ $vat->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    <!-- STATUS -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select name="status"
                                class="form-select">

                            <option value="active"
                                {{ $service->status == 'active' ? 'selected' : '' }}>

                                Active

                            </option>

                            <option value="inactive"
                                {{ $service->status == 'inactive' ? 'selected' : '' }}>

                                Inactive

                            </option>

                        </select>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea name="description"
                                  class="form-control"
                                  rows="3">{{ $service->description }}</textarea>

                    </div>


                    <!-- IMAGE -->

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Image
                        </label>

                        <input type="file"
                               name="image"
                               class="form-control">

                    </div>

                </div>

            </div>


            <div class="modal-footer">

                <button type="submit"
                        class="btn btn-primary">

                    Update Service

                </button>

            </div>

        </form>

    </div>

</div>

@endforeach



<!-- ADD MODAL -->

<div class="modal fade"
     id="addModal"
     tabindex="-1"
     data-bs-backdrop="static"
     data-bs-keyboard="false">

    <div class="modal-dialog modal-lg">

        <form action="{{ route('company.services.store') }}"
              method="POST"
              enctype="multipart/form-data"
              class="modal-content">

            @csrf

            <div class="modal-header">

                <h5 class="modal-title">

                    Add Service

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>


            <div class="modal-body">

                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Service Name
                        </label>

                        <input type="text"
                               name="name"
                               class="form-control"
                               required>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Service Code
                        </label>

                        <input type="text"
                               name="service_code"
                               class="form-control">

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Category
                        </label>

                        <select name="service_category_id"
                                class="form-select">

                            <option value="">
                                Select Category
                            </option>

                            @foreach($categories as $category)

                                <option value="{{ $category->id }}">

                                    {{ $category->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Price
                        </label>

                        <input type="number"
                               step="0.01"
                               name="price"
                               class="form-control"
                               required>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            VAT
                        </label>

                        <select name="vat_id"
                                class="form-select">

                            <option value="">
                                Select VAT
                            </option>

                            @foreach($vats as $vat)

                                <option value="{{ $vat->id }}">

                                    {{ $vat->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select name="status"
                                class="form-select">

                            <option value="active">

                                Active

                            </option>

                            <option value="inactive">

                                Inactive

                            </option>

                        </select>

                    </div>


                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea name="description"
                                  class="form-control"
                                  rows="3"></textarea>

                    </div>


                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Image
                        </label>

                        <input type="file"
                               name="image"
                               class="form-control">

                    </div>

                </div>

            </div>


            <div class="modal-footer">

                <button type="submit"
                        class="btn btn-primary">

                    Save Service

                </button>

            </div>

        </form>

    </div>

</div>

@endsection
