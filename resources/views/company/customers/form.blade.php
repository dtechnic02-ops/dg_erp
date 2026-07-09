<div class="form-grid">

    <div class="form-group">
        <label class="form-label">
            Customer Name
        </label>

        <input
            name="name"
            class="form-input"
            value="{{ $customer->name ?? '' }}"
            required>
    </div>

    <div class="form-group">
        <label class="form-label">
            Authority Name
        </label>

        <input
            name="authority_name"
            class="form-input"
            value="{{ $customer->authority_name ?? '' }}">
    </div>

    <div class="form-group">
        <label class="form-label">
            Status
        </label>

        <select
            name="status"
            class="form-input">

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

    <div class="form-group">
        <label class="form-label">
            Mobile
        </label>

        <input
            name="mobile"
            class="form-input"
            value="{{ $customer->mobile ?? '' }}">
    </div>

    <div class="form-group">
        <label class="form-label">
            Telephone
        </label>

        <input
            name="telephone"
            class="form-input"
            value="{{ $customer->telephone ?? '' }}">
    </div>

    <div class="form-group">
        <label class="form-label">
            Fax Number
        </label>

        <input
            name="fax_no"
            class="form-input"
            value="{{ $customer->fax_no ?? '' }}">
    </div>

    <div class="form-group">
        <label class="form-label">
            Email
        </label>

        <input
            type="email"
            name="email"
            class="form-input"
            value="{{ $customer->email ?? '' }}">
    </div>

    <div class="form-group">
        <label class="form-label">
            Website
        </label>

        <input
            type="url"
            name="website"
            class="form-input"
            value="{{ $customer->website ?? '' }}">
    </div>

    <div class="form-group-number">
        <label class="form-label">
            Opening Balance
        </label>

        <input
            type="number"
            step="0.01"
            name="opening_balance"
            class="form-input1"
            value="{{ $customer->opening_balance ?? 0 }}">
    </div>

    @isset($customer)
    <div class="form-group-number">
        <label class="form-label">
            Current Balance
        </label>

        <input
            type="text"
            class="form-input1"
            value="{{ number_format($customer->current_balance, 2) }}"
            readonly>
    </div>
    @endisset

    <div class="form-group">
        <label class="form-label">
            Tax Number
        </label>

        <input
            name="tax_no"
            class="form-input"
            value="{{ $customer->tax_no ?? '' }}">
    </div>

    <div class="form-group">
        <label class="form-label">
            Bank Name
        </label>

        <input
            name="bank_name"
            class="form-input"
            value="{{ $customer->bank_name ?? '' }}">
    </div>

    <div class="form-group">
        <label class="form-label">
            Bank Account Number
        </label>

        <input
            name="bank_account_no"
            class="form-input"
            value="{{ $customer->bank_account_no ?? '' }}">
    </div>

    <div class="form-group">
        <label class="form-label">
            Image
        </label>

        <input
            type="file"
            name="image"
            class="form-input">
    </div>

    <div class="form-group">
        <label class="form-label">
            Preview
        </label>

        @if(!empty($customer->image_path))
            <img
                src="{{ asset($customer->image_path) }}"
                class="form-image">
        @else
            <div
                class="form-image"
                style="opacity:.3">
            </div>
        @endif
    </div>

    <div class="form-group form-full">
        <label class="form-label">
            Address
        </label>

        <textarea
            name="address"
            rows="2"
            class="form-input">{{ $customer->address ?? '' }}</textarea>
    </div>

    <div class="form-group form-full">
        <label class="form-label">
            Note
        </label>

        <textarea
            name="note"
            rows="3"
            class="form-input">{{ $customer->note ?? '' }}</textarea>
    </div>

</div>