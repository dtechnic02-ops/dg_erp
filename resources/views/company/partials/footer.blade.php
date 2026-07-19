@php
$company = auth()->user()?->company;
@endphp

<footer class="dg-footer" role="contentinfo">
    <div class="dg-footer-inner">
        <span class="dg-footer-text">© {{ date('Y') }} DG ERP. All rights reserved.</span>
        <span class="dg-footer-meta">{{ $company->company_name ?? 'Company Panel' }}</span>
    </div>
</footer>
