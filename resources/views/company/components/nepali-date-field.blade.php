@php
    $adInputId = $adInputId ?? 'sale_date';
    $bsInputId = $bsInputId ?? $adInputId . '_bs';
    $company = auth()->user()?->company;
    $isNepalCompany = $isNepalCompany ?? ($company?->countryMaster?->iso_code === 'NP');
    $resolvedBsDate = $saleDateBs ?? null;
@endphp

@if ($isNepalCompany)
    <div class="{{ $columnClass ?? 'col-md-2' }}" data-nepali-date-field data-ad-input-id="{{ $adInputId }}" data-conversion-url="{{ route('company.calendar.ad-to-bs') }}">
        <label for="{{ $bsInputId }}" class="form-label">मिति (BS)</label>
        <input type="text" id="{{ $bsInputId }}" class="form-control dg-input" value="{{ $resolvedBsDate }}" readonly aria-readonly="true">
        <small class="form-text text-muted dg-note">Derived automatically from Business Date (AD).</small>
    </div>
@endif
