<div class="dg-section">
    <div class="card dg-card">
        <div class="card-header dg-card-header">
            <h6 class="mb-0">Service Category Details</h6>
        </div>

        <div class="card-body dg-card-body">
            <div class="row g-2">

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="name" class="form-label dg-label">
                        Category Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control dg-input"
                        value="{{ $category->name ?? '' }}"
                        required>
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
                            {{ ($category->status ?? 'active') == 'active' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option
                            value="inactive"
                            {{ isset($category) && $category->status == 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>

                    </select>
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

                @if (!empty($category->upload_path))
                    <div class="col-lg-4 col-md-6 col-12">
                        <span class="form-label dg-label">
                            Preview
                        </span>

                        <div>
                            <img
                                src="{{ asset($category->upload_path) }}"
                                alt="{{ $category->name ?? 'Service Category' }} image"
                                width="60"
                                height="60"
                                class="dg-image">
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
