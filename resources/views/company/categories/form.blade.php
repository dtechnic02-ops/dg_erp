<div class="dg-section">
    <div class="card dg-card">
        <div class="card-header dg-card-header">
            <h6 class="mb-0">Category Details</h6>
        </div>

        <div class="card-body dg-card-body">
            <div class="row g-2">

                <div class="col-lg-6 col-md-6 col-12">
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

                <div class="col-lg-6 col-md-6 col-12">
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

                <div class="col-lg-12 col-md-12 col-12">
                    <label for="description" class="form-label dg-label">
                        Description
                    </label>

                    <textarea
                        name="description"
                        id="description"
                        rows="2"
                        class="form-control dg-input">{{ $category->description ?? '' }}</textarea>
                </div>

            </div>
        </div>
    </div>
</div>
