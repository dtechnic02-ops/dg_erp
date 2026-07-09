```blade id="ukpwjm"
@extends('company.layout')

@section('content')

<div class="container-fluid py-3">

    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h4 class="mb-0">🛠️ Service Categories</h4>

            <small class="text-muted">

                Manage service categories

            </small>

        </div>


        <button type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#addModal">

            ➕ Add Category

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
                  action="{{ route('company.service-categories.index') }}">

                <div class="row g-2">


                    <!-- SEARCH -->

                    <div class="col-md-4">

                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Search category..."
                               value="{{ request('search') }}">

                    </div>


                    <!-- STATUS -->

                    <div class="col-md-3">

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


                    <!-- BUTTONS -->

                    <div class="col-md-5 text-end">

                        <button class="btn btn-dark">

                            🔍 Search

                        </button>

                        <a href="{{ route('company.service-categories.index') }}"
                           class="btn btn-secondary">

                            Reset

                        </a>

                        <a href="{{ route('company.service-categories.print', request()->all()) }}"
                           target="_blank"
                           class="btn btn-success">

                            🖨️ Print

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

                        <th>Name</th>

                        <th>Slug</th>

                        <th>Status</th>

                        <th width="180">Action</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($categories as $key => $category)

                        <tr>


                            <!-- SERIAL -->

                            <td>

                                {{ $categories->firstItem() + $key }}

                            </td>


                            <!-- IMAGE -->

                            <td>

                                @if($category->upload_path)

                                    <img src="{{ asset($category->upload_path) }}"
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

                                {{ $category->name }}

                            </td>


                            <!-- SLUG -->

                            <td>

                                {{ $category->slug }}

                            </td>


                            <!-- STATUS -->

                            <td>

                                @if($category->status == 'active')

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
                                        data-bs-target="#editModal{{ $category->id }}">

                                    Edit

                                </button>


                                <form action="{{ route('company.service-categories.delete', $category->id) }}"
                                      method="POST"
                                      class="d-inline">

                                    @csrf

                                    <button type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete category?')">

                                        Delete

                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6"
                                class="text-center py-4">

                                No categories found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <!-- PAGINATION -->

        <div class="card-footer">

            {{ $categories->links() }}

        </div>

    </div>

</div>



<!-- EDIT MODALS -->

@foreach($categories as $category)

<div class="modal fade"
     id="editModal{{ $category->id }}"
     tabindex="-1"
     data-bs-backdrop="static"
     data-bs-keyboard="false">

    <div class="modal-dialog">

        <form action="{{ route('company.service-categories.update', $category->id) }}"
              method="POST"
              enctype="multipart/form-data"
              class="modal-content">

            @csrf

            <div class="modal-header">

                <h5 class="modal-title">

                    Edit Category

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>


            <div class="modal-body">


                <!-- NAME -->

                <div class="mb-3">

                    <label class="form-label">
                        Category Name
                    </label>

                    <input type="text"
                           name="name"
                           class="form-control"
                           value="{{ $category->name }}"
                           required>

                </div>


                <!-- IMAGE -->

                <div class="mb-3">

                    <label class="form-label">
                        Image
                    </label>

                    <input type="file"
                           name="image"
                           class="form-control">

                </div>


                <!-- STATUS -->

                <div class="mb-3">

                    <label class="form-label">
                        Status
                    </label>

                    <select name="status"
                            class="form-select">

                        <option value="active"
                            {{ $category->status == 'active' ? 'selected' : '' }}>

                            Active

                        </option>

                        <option value="inactive"
                            {{ $category->status == 'inactive' ? 'selected' : '' }}>

                            Inactive

                        </option>

                    </select>

                </div>

            </div>


            <div class="modal-footer">

                <button type="submit"
                        class="btn btn-primary">

                    Update Category

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

    <div class="modal-dialog">

        <form action="{{ route('company.service-categories.store') }}"
              method="POST"
              enctype="multipart/form-data"
              class="modal-content">

            @csrf

            <div class="modal-header">

                <h5 class="modal-title">

                    Add Service Category

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>


            <div class="modal-body">


                <!-- NAME -->

                <div class="mb-3">

                    <label class="form-label">
                        Category Name
                    </label>

                    <input type="text"
                           name="name"
                           class="form-control"
                           required>

                </div>


                <!-- IMAGE -->

                <div class="mb-3">

                    <label class="form-label">
                        Image
                    </label>

                    <input type="file"
                           name="image"
                           class="form-control">

                </div>


                <!-- STATUS -->

                <div class="mb-3">

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

            </div>


            <div class="modal-footer">

                <button type="submit"
                        class="btn btn-primary">

                    Save Category

                </button>

            </div>

        </form>

    </div>

</div>

@endsection
```
