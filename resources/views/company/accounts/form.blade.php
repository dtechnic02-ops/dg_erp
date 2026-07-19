@php
    $account = $account ?? null;
@endphp

<div class="row g-2">

    <div class="col-lg-4 col-md-6 col-12">
        <label for="account_type" class="dg-label">Account Type</label>
        <select name="account_type" id="account_type" class="form-select dg-select" required>
            <option value="">Select Type</option>
            <option value="Cash" {{ optional($account)->account_type == 'Cash' ? 'selected' : '' }}>Cash</option>
            <option value="Bank" {{ optional($account)->account_type == 'Bank' ? 'selected' : '' }}>Bank</option>
            <option value="Wallet" {{ optional($account)->account_type == 'Wallet' ? 'selected' : '' }}>Wallet</option>
            <option value="ATM" {{ optional($account)->account_type == 'ATM' ? 'selected' : '' }}>ATM</option>
        </select>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="bank_name" class="dg-label">Bank Name</label>
        <input type="text" name="bank_name" id="bank_name" value="{{ optional($account)->bank_name }}" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="account_name" class="dg-label">Account Name</label>
        <input type="text" name="account_name" id="account_name" value="{{ optional($account)->account_name }}" class="form-control dg-input" required>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="branch" class="dg-label">Branch</label>
        <input type="text" name="branch" id="branch" value="{{ optional($account)->branch }}" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="account_no" class="dg-label">Account Number</label>
        <input type="text" name="account_no" id="account_no" value="{{ optional($account)->account_no }}" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="iban" class="dg-label">IBAN</label>
        <input type="text" name="iban" id="iban" value="{{ optional($account)->iban }}" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="swift_code" class="dg-label">Swift Code</label>
        <input type="text" name="swift_code" id="swift_code" value="{{ optional($account)->swift_code }}" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="currency" class="dg-label">Currency</label>
        <select name="currency" id="currency" class="form-select dg-select">
            @foreach (['AED', 'USD', 'NPR', 'INR', 'EUR', 'GBP'] as $currency)
                <option value="{{ $currency }}" {{ (optional($account)->currency ?: 'AED') == $currency ? 'selected' : '' }}>{{ $currency }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="opening_balance" class="dg-label">Opening Balance</label>
        <input type="number" step="0.01" name="opening_balance" id="opening_balance" value="{{ optional($account)->opening_balance ?? 0 }}" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="status" class="dg-label">Status</label>
        <select name="status" id="status" class="form-select dg-select">
            <option value="active" {{ (optional($account)->status ?: 'active') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ optional($account)->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="image_path" class="dg-label">Image</label>
        <input type="file" name="image_path" id="image_path" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <span class="dg-label d-block">Preview</span>
        @if (optional($account)->image_path)
            <img
                src="{{ asset($account->image_path) }}"
                alt="{{ $account->account_name }} image"
                width="60"
                height="60"
                class="rounded border">
        @endif
    </div>

    <div class="col-12">
        <label for="note" class="dg-label">Note</label>
        <textarea name="note" id="note" rows="2" class="form-control dg-textarea">{{ optional($account)->note }}</textarea>
    </div>

</div>
