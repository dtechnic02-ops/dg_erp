<div class="dg-section">
    <div class="card dg-card">
        <div class="card-header dg-card-header">
            <h6 class="mb-0">Customer Details</h6>
        </div>

        <div class="card-body dg-card-body">
            <div class="row g-2">

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="name" class="form-label dg-label">
                        Customer Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control dg-input"
                        value="{{ $customer->name ?? '' }}"
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
                        value="{{ $customer->authority_name ?? '' }}">
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
                            {{ ($customer->status ?? '') == 'active' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option
                            value="inactive"
                            {{ ($customer->status ?? '') == 'inactive' ? 'selected' : '' }}>
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
                        value="{{ $customer->mobile ?? '' }}">
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
                        value="{{ $customer->telephone ?? '' }}">
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
                        value="{{ $customer->fax_no ?? '' }}">
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
                        value="{{ $customer->email ?? '' }}">
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
                        value="{{ $customer->website ?? '' }}">
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
                        value="{{ $customer->tax_no ?? '' }}">
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                    <label for="credit_days" class="form-label dg-label">
                        Credit Days
                    </label>

                    <input
                        type="number"
                        min="0"
                        step="1"
                        name="credit_days"
                        id="credit_days"
                        class="form-control dg-input"
                        value="{{ old('credit_days', $customer->credit_days ?? 0) }}">
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
                        value="{{ $customer->opening_balance ?? 0 }}">
                </div>

                @isset($customer)
                    <div class="col-lg-4 col-md-6 col-12">
                        <label for="current_balance" class="form-label dg-label">
                            Current Balance
                        </label>

                        <input
                            type="text"
                            id="current_balance"
                            class="form-control dg-input"
                            value="{{ number_format($customer->current_balance, 2) }}"
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
                        value="{{ $customer->bank_name ?? '' }}">
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
                        value="{{ $customer->bank_account_no ?? '' }}">
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

                <div class="col-lg-4 col-md-6 col-12">
                    <span class="form-label dg-label">
                        Preview
                    </span>

                    @if (!empty($customer->image_path))
                        <div>
                            <img
                                src="{{ asset($customer->image_path) }}"
                                alt="{{ $customer->name ?? 'Customer' }} image"
                                width="60"
                                height="60">
                        </div>
                    @endif
                </div>

                @if (!isset($customer))
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
                        class="form-control dg-input">{{ $customer->address ?? '' }}</textarea>
                </div>

                <div class="col-lg-6 col-md-6 col-12">
                    <label for="note" class="form-label dg-label">
                        Note
                    </label>

                    <textarea
                        name="note"
                        id="note"
                        rows="2"
                        class="form-control dg-input">{{ $customer->note ?? '' }}</textarea>
                </div>

            </div>
        </div>
    </div>
</div>
