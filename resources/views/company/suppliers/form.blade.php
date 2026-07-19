<div class="dg-section">
    <div class="card dg-card">
        <div class="card-header dg-card-header">
            <h6 class="mb-0">Supplier Details</h6>
        </div>

        <div class="card-body dg-card-body">
            <div class="row g-2">

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="name" class="form-label dg-label">
                        Supplier Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control dg-input"
                        value="{{ $supplier->name ?? '' }}"
                        required>
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="authority_name" class="form-label dg-label">
                        Authority Name
                    </label>

                    <input
                        type="text"
                        name="authority_name"
                        id="authority_name"
                        class="form-control dg-input"
                        value="{{ $supplier->authority_name ?? '' }}">
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
                            {{ ($supplier->status ?? '') == 'active' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option
                            value="inactive"
                            {{ ($supplier->status ?? '') == 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>

                    </select>
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="mobile" class="form-label dg-label">
                        Mobile
                    </label>

                    <input
                        type="text"
                        name="mobile"
                        id="mobile"
                        class="form-control dg-input"
                        value="{{ $supplier->mobile ?? '' }}">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="telephone" class="form-label dg-label">
                        Telephone
                    </label>

                    <input
                        type="text"
                        name="telephone"
                        id="telephone"
                        class="form-control dg-input"
                        value="{{ $supplier->telephone ?? '' }}">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="fax_no" class="form-label dg-label">
                        Fax Number
                    </label>

                    <input
                        type="text"
                        name="fax_no"
                        id="fax_no"
                        class="form-control dg-input"
                        value="{{ $supplier->fax_no ?? '' }}">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="email" class="form-label dg-label">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control dg-input"
                        value="{{ $supplier->email ?? '' }}">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="website" class="form-label dg-label">
                        Website
                    </label>

                    <input
                        type="url"
                        name="website"
                        id="website"
                        class="form-control dg-input"
                        value="{{ $supplier->website ?? '' }}">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="tax_no" class="form-label dg-label">
                        Tax Number
                    </label>

                    <input
                        type="text"
                        name="tax_no"
                        id="tax_no"
                        class="form-control dg-input"
                        value="{{ $supplier->tax_no ?? '' }}">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="opening_balance" class="form-label dg-label">
                        Opening Balance
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="opening_balance"
                        id="opening_balance"
                        class="form-control dg-input"
                        value="{{ $supplier->opening_balance ?? 0 }}">
                </div>

                @isset($supplier)
                    <div class="col-lg-4 col-md-6 col-12">
                        <label for="current_balance" class="form-label dg-label">
                            Current Balance
                        </label>

                        <input
                            type="text"
                            id="current_balance"
                            class="form-control dg-input"
                            value="{{ number_format($supplier->current_balance, 2) }}"
                            readonly>
                    </div>
                @endisset

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="bank_name" class="form-label dg-label">
                        Bank Name
                    </label>

                    <input
                        type="text"
                        name="bank_name"
                        id="bank_name"
                        class="form-control dg-input"
                        value="{{ $supplier->bank_name ?? '' }}">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="bank_account_no" class="form-label dg-label">
                        Bank Account Number
                    </label>

                    <input
                        type="text"
                        name="bank_account_no"
                        id="bank_account_no"
                        class="form-control dg-input"
                        value="{{ $supplier->bank_account_no ?? '' }}">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="image_path" class="form-label dg-label">
                        Image
                    </label>

                    <input
                        type="file"
                        name="image_path"
                        id="image_path"
                        class="form-control dg-input">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <span class="form-label dg-label">
                        Preview
                    </span>

                    @if (!empty($supplier->image_path))
                        <div>
                            <img
                                src="{{ asset($supplier->image_path) }}"
                                alt="{{ $supplier->name ?? 'Supplier' }} image"
                                width="60"
                                height="60">
                        </div>
                    @endif
                </div>

                @if (!isset($supplier))
                    <div class="col-lg-4 d-none d-lg-block"></div>
                @endif

                <div class="col-lg-6 col-md-6 col-12">
                    <label for="address" class="form-label dg-label">
                        Address
                    </label>

                    <textarea
                        name="address"
                        id="address"
                        rows="2"
                        class="form-control dg-input">{{ $supplier->address ?? '' }}</textarea>
                </div>

                <div class="col-lg-6 col-md-6 col-12">
                    <label for="note" class="form-label dg-label">
                        Note
                    </label>

                    <textarea
                        name="note"
                        id="note"
                        rows="2"
                        class="form-control dg-input">{{ $supplier->note ?? '' }}</textarea>
                </div>

            </div>
        </div>
    </div>
</div>
