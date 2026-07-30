@php
    $account = $account ?? null;
@endphp

<div class="row g-2">

    <div class="col-lg-4 col-md-6 col-12">
        <label for="account_group" class="dg-label">Account Group</label>
        <select name="account_group" id="account_group" class="form-select dg-select" required>
            <option value="">Select Group</option>
            @foreach ($accountGroups as $value => $label)
                <option value="{{ $value }}" {{ old('account_group', optional($account)->account_group) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="account_type" class="dg-label">Account Type</label>
        <select name="account_type" id="account_type" class="form-select dg-select" required>
            <option value="">Select Type</option>
            @foreach ($accountTypes as $value => $label)
                <option value="{{ $value }}" {{ old('account_type', optional($account)->account_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="account_name" class="dg-label">Account Name</label>
        <input type="text" name="account_name" id="account_name" value="{{ old('account_name', optional($account)->account_name) }}" class="form-control dg-input" required>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="sub_ledger_type" class="dg-label">Sub Ledger Type</label>
        <select name="sub_ledger_type" id="sub_ledger_type" class="form-select dg-select">
            <option value="">None</option>
            <option value="customer" {{ optional($account)->sub_ledger_type == 'customer' ? 'selected' : '' }}>Accounts Receivable (Customer)</option>
            <option value="supplier" {{ optional($account)->sub_ledger_type == 'supplier' ? 'selected' : '' }}>Accounts Payable (Supplier)</option>
            <option value="employee" {{ optional($account)->sub_ledger_type == 'employee' ? 'selected' : '' }}>Salary Payable (Employee)</option>
            <option value="party" {{ optional($account)->sub_ledger_type == 'party' ? 'selected' : '' }}>Party Ledger (Party)</option>
        </select>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="bank_name" class="dg-label">Bank Name</label>
        <input type="text" name="bank_name" id="bank_name" value="{{ optional($account)->bank_name }}" class="form-control dg-input">
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
        <label for="current_balance" class="dg-label">Current Balance</label>
        <input type="number" step="0.01" id="current_balance" value="{{ optional($account)->current_balance ?? 0 }}" class="form-control dg-input" readonly aria-readonly="true">
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
