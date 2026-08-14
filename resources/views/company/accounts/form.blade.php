@php
    $account = $account ?? null;
    $formPrefix = $formPrefix ?? 'account';
    $formMode = $formMode ?? ($account ? 'edit' : 'create');

    $fieldValue = function (string $key, $default = '') use ($formMode, $account) {
        if ($formMode === 'create') {
            return old('_account_form') === 'create' ? old($key, $default) : $default;
        }

        return old($key, optional($account)->{$key} ?? $default);
    };
@endphp

<div class="row g-2">

    <div class="col-lg-4 col-md-6 col-12">
        <label for="{{ $formPrefix }}_account_group" class="dg-label">Account Group</label>
        <select name="account_group" id="{{ $formPrefix }}_account_group" class="form-select dg-select" required>
            <option value="">Select Group</option>
            @foreach ($accountGroups as $value => $label)
                <option value="{{ $value }}" {{ $fieldValue('account_group') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="{{ $formPrefix }}_account_type" class="dg-label">Account Type</label>
        <select name="account_type" id="{{ $formPrefix }}_account_type" class="form-select dg-select" required>
            <option value="">Select Type</option>
            @foreach ($accountTypes as $value => $label)
                <option value="{{ $value }}" {{ $fieldValue('account_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="{{ $formPrefix }}_account_name" class="dg-label">Account Name</label>
        <input type="text" name="account_name" id="{{ $formPrefix }}_account_name" value="{{ $fieldValue('account_name') }}" class="form-control dg-input" required>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="{{ $formPrefix }}_sub_ledger_type" class="dg-label">Sub Ledger Type</label>
        <select name="sub_ledger_type" id="{{ $formPrefix }}_sub_ledger_type" class="form-select dg-select">
            <option value="">None</option>
            <option value="customer" {{ $fieldValue('sub_ledger_type') == 'customer' ? 'selected' : '' }}>Accounts Receivable (Customer)</option>
            <option value="supplier" {{ $fieldValue('sub_ledger_type') == 'supplier' ? 'selected' : '' }}>Accounts Payable (Supplier)</option>
            <option value="employee" {{ $fieldValue('sub_ledger_type') == 'employee' ? 'selected' : '' }}>Salary Payable (Employee)</option>
            <option value="party" {{ $fieldValue('sub_ledger_type') == 'party' ? 'selected' : '' }}>Party Ledger (Party)</option>
        </select>
    </div>

    <div class="col-lg-4 col-md-6 col-12 account-bank-field">
        <label for="{{ $formPrefix }}_bank_name" class="dg-label">Bank Name</label>
        <input type="text" name="bank_name" id="{{ $formPrefix }}_bank_name" value="{{ $fieldValue('bank_name') }}" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12 account-bank-field">
        <label for="{{ $formPrefix }}_branch" class="dg-label">Branch</label>
        <input type="text" name="branch" id="{{ $formPrefix }}_branch" value="{{ $fieldValue('branch') }}" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12 account-bank-field">
        <label for="{{ $formPrefix }}_account_no" class="dg-label">Account Number</label>
        <input type="text" name="account_no" id="{{ $formPrefix }}_account_no" value="{{ $fieldValue('account_no') }}" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12 account-bank-field">
        <label for="{{ $formPrefix }}_iban" class="dg-label">IBAN</label>
        <input type="text" name="iban" id="{{ $formPrefix }}_iban" value="{{ $fieldValue('iban') }}" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12 account-bank-field">
        <label for="{{ $formPrefix }}_swift_code" class="dg-label">Swift Code</label>
        <input type="text" name="swift_code" id="{{ $formPrefix }}_swift_code" value="{{ $fieldValue('swift_code') }}" class="form-control dg-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="{{ $formPrefix }}_currency" class="dg-label">Currency</label>
        <select name="currency" id="{{ $formPrefix }}_currency" class="form-select dg-select">
            @foreach (['AED', 'USD', 'NPR', 'INR', 'EUR', 'GBP'] as $currency)
                <option value="{{ $currency }}" {{ ($fieldValue('currency', 'AED') ?: 'AED') == $currency ? 'selected' : '' }}>{{ $currency }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="{{ $formPrefix }}_current_balance" class="dg-label">Current Balance</label>
        <input
            type="number"
            step="0.01"
            id="{{ $formPrefix }}_current_balance"
            value="{{ $formMode === 'edit' ? (optional($account)->current_balance ?? 0) : '0' }}"
            class="form-control dg-input"
            readonly
            aria-readonly="true">
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="{{ $formPrefix }}_status" class="dg-label">Status</label>
        <select name="status" id="{{ $formPrefix }}_status" class="form-select dg-select">
            <option value="active" {{ ($fieldValue('status', 'active') ?: 'active') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $fieldValue('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <label for="{{ $formPrefix }}_image_path" class="dg-label">Image</label>
        <input type="file" name="image_path" id="{{ $formPrefix }}_image_path" class="form-control dg-input account-image-input">
    </div>

    <div class="col-lg-4 col-md-6 col-12">
        <span class="dg-label d-block">Preview</span>
        <span class="account-image-preview">
            @if ($formMode === 'edit' && optional($account)->image_path)
                <img
                    src="{{ asset($account->image_path) }}"
                    alt="{{ $account->account_name }} image"
                    width="60"
                    height="60"
                    class="rounded border account-image-preview-img">
            @endif
        </span>
    </div>

    <div class="col-12">
        <label for="{{ $formPrefix }}_note" class="dg-label">Note</label>
        <textarea name="note" id="{{ $formPrefix }}_note" rows="2" class="form-control dg-textarea">{{ $fieldValue('note') }}</textarea>
    </div>

</div>
