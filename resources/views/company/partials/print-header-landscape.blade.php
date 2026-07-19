@php

$company = \App\Models\Company::find(
auth()->user()->company_id
);

@endphp

<div class="dg-print-header dg-print-landscape">
    <table class="dg-print-header-table">
        <tr>
            <td class="dg-print-logo-cell">
                @if ($company && $company->logo_path)
                    <img
                        src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                        alt="{{ $company->company_name ?? 'Company Logo' }}"
                        class="dg-print-logo">
                @endif
            </td>

            <td class="dg-print-company text-start">
                <div class="dg-print-company-name">{{ $company->company_name ?? '' }}</div>
                <div class="dg-print-company-meta">
                    @if (!empty($company->address))
                        {{ $company->address }}<br>
                    @endif
                    @if (!empty($company->telephone) || !empty($company->mobile))
                        <span class="fw-semibold">Phone:</span>
                        {{ $company->telephone ?? $company->mobile }}
                        @if (!empty($company->telephone) && !empty($company->mobile) && $company->telephone !== $company->mobile)
                            / {{ $company->mobile }}
                        @endif
                        <br>
                    @endif
                    @if (!empty($company->email))
                        <span class="fw-semibold">Email:</span> {{ $company->email }}<br>
                    @endif
                    @if (!empty($company->vat_number) || !empty($company->pan_number))
                        @if (!empty($company->vat_number))
                            <span class="fw-semibold">VAT:</span> {{ $company->vat_number }}
                        @endif
                        @if (!empty($company->vat_number) && !empty($company->pan_number))
                            &nbsp;|&nbsp;
                        @endif
                        @if (!empty($company->pan_number))
                            <span class="fw-semibold">PAN:</span> {{ $company->pan_number }}
                        @endif
                        <br>
                    @endif
                    @if (!empty($company->website))
                        <span class="fw-semibold">Website:</span> {{ $company->website }}
                    @endif
                </div>
            </td>

            <td class="dg-print-meta text-end">
                <div><span class="fw-semibold">Print Date:</span> {{ now()->format('d M Y') }}</div>
                <div><span class="fw-semibold">Print Time:</span> {{ now()->format('H:i') }}</div>
            </td>
        </tr>

        @if (!empty($documentTitle ?? null))
            <tr class="dg-print-title-row">
                <td colspan="3" class="text-center">
                    <div class="dg-print-title">{{ $documentTitle }}</div>
                </td>
            </tr>
        @endif
    </table>
</div>
