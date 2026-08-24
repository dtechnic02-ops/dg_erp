@php
    $adInputId = $adInputId ?? 'sale_date';
    $bsInputId = $bsInputId ?? $adInputId . '_bs';
    $company = auth()->user()?->company;
    $isNepalCompany = $isNepalCompany ?? ($company?->countryMaster?->iso_code === 'NP');
    $resolvedBsDate = $saleDateBs ?? null;
@endphp

@if ($isNepalCompany)
    <div class="{{ $columnClass ?? 'col-md-2' }}" data-nepali-date-field data-ad-input-id="{{ $adInputId }}">
        <label for="{{ $bsInputId }}" class="form-label">मिति (BS)</label>
        <input type="text" id="{{ $bsInputId }}" class="form-control dg-input" value="{{ $resolvedBsDate }}" readonly aria-readonly="true">
        <small class="form-text text-muted dg-note">Derived automatically from Business Date (AD).</small>
    </div>

    @once
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('[data-nepali-date-field]').forEach((field) => {
                        const adInput = document.getElementById(field.dataset.adInputId);
                        const bsOutput = field.querySelector('input[readonly]');

                        if (!adInput || !bsOutput) {
                            return;
                        }

                        const synchronizeBsDate = async () => {
                            bsOutput.value = '';

                            if (!adInput.value) {
                                return;
                            }

                            const url = new URL(@json(route('company.calendar.ad-to-bs')), window.location.origin);
                            url.searchParams.set('date', adInput.value);

                            try {
                                const response = await fetch(url, {
                                    headers: { 'Accept': 'application/json' },
                                    credentials: 'same-origin',
                                });
                                const result = await response.json();

                                bsOutput.value = response.ok && result.applicable ? result.bs_date : '';
                            } catch (error) {
                                bsOutput.value = '';
                            }
                        };

                        adInput.addEventListener('change', synchronizeBsDate);
                        synchronizeBsDate();
                    });
                });
            </script>
        @endpush
    @endonce
@endif
