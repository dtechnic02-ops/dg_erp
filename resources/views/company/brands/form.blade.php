<div class="dg-section">
    <div class="card dg-card">
        <div class="card-header dg-card-header">
            <h6 class="mb-0">Brand Details</h6>
        </div>

        <div class="card-body dg-card-body">
            <div class="row g-2">

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="name" class="form-label dg-label">
                        Brand Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control dg-input"
                        value="{{ $brand->name ?? '' }}"
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
                            value="1"
                            {{ ($brand->status ?? 1) ? 'selected' : '' }}>
                            Active
                        </option>

                        <option
                            value="0"
                            {{ isset($brand) && !$brand->status ? 'selected' : '' }}>
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

                @if (!empty($brand->image))
                    <div class="col-lg-4 col-md-6 col-12">
                        <span class="form-label dg-label">
                            Preview
                        </span>

                        <div>
                            <img
                                src="{{ asset($brand->image) }}"
                                alt="{{ $brand->name ?? 'Brand' }} image"
                                width="60"
                                height="60"
                                class="dg-image">
                        </div>
                    </div>
                @endif

                <div class="col-lg-6 col-md-6 col-12">
                    <label for="description" class="form-label dg-label">
                        Description
                    </label>

                    <textarea
                        name="description"
                        id="description"
                        rows="2"
                        class="form-control dg-input">{{ $brand->description ?? '' }}</textarea>
                </div>

            </div>
        </div>
    </div>
</div>
