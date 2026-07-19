@php

$company = \App\Models\Company::find(
auth()->user()->company_id
);

@endphp

<div class="dg-print-footer dg-print-landscape">
    @if (!empty($company->print_note))
        <div class="dg-print-note">
            {!! nl2br(e($company->print_note)) !!}
        </div>
    @endif

    <table class="dg-print-signature-table">
        <tr>
            <td class="text-center">
                <div class="dg-signature-line"></div>
                <div class="dg-signature-label">Prepared By</div>
            </td>
            <td class="text-center">
                <div class="dg-signature-line"></div>
                <div class="dg-signature-label">Checked By</div>
            </td>
            <td class="text-center">
                <div class="dg-signature-line"></div>
                <div class="dg-signature-label">Authorized By</div>
            </td>
        </tr>
    </table>

    <div class="dg-print-meta-footer text-end">
        <span class="fw-semibold">Printed By:</span> {{ auth()->user()->name ?? '' }}
        &nbsp;|&nbsp;
        <span class="fw-semibold">Printed:</span> {{ now()->format('d M Y H:i') }}
    </div>
</div>
