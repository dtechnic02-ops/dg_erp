<div class="dg-section">
    <div class="card dg-card">
        <div class="card-header dg-card-header">
            <h6 class="mb-0">Unit Details</h6>
        </div>

        <div class="card-body dg-card-body">
            <div class="row g-2">

                <div class="col-lg-6 col-md-6 col-12">
                    <label for="name" class="form-label dg-label">
                        Unit Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control dg-input"
                        value="{{ $unit->name ?? '' }}"
                        required>
                </div>

                <div class="col-lg-6 col-md-6 col-12">
                    <label for="short_name" class="form-label dg-label">
                        Short Name
                    </label>

                    <input
                        type="text"
                        name="short_name"
                        id="short_name"
                        class="form-control dg-input"
                        value="{{ $unit->short_name ?? '' }}"
                        required>
                </div>

            </div>
        </div>
    </div>
</div>
