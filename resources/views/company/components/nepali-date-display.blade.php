@php
    $company = auth()->user()?->company;
    $bsDate = null;

    if ($company?->countryMaster?->iso_code === 'NP' && isset($adDate) && $adDate !== null && $adDate !== '') {
        try {
            $bsDate = app(\App\Services\NepaliDateService::class)->adToBs($adDate);
        } catch (\InvalidArgumentException) {
            $bsDate = null;
        }
    }
@endphp

@if ($bsDate !== null)
    <span class="dg-bs-date">{{ $bsDate }} BS</span>
@endif
