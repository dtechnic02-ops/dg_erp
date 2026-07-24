@php
    $selectedCustomerId = old('customer_id', $selectedCustomerId ?? null);
    $selectedCustomer = $customers->firstWhere('id', (int) $selectedCustomerId);
@endphp

<div class="col-12">
    <label for="customer_search" class="form-label">Customer <span class="text-danger">*</span></label>
    <input type="text" id="customer_search" class="form-control dg-input mb-2" placeholder="Search customer by name, mobile, or authority..." autocomplete="off">
    <select name="customer_id" id="customer_id" class="form-select dg-select" required>
        <option value="">Select Customer</option>
        @foreach ($customers as $customer)
            <option value="{{ $customer->id }}"
                data-name="{{ $customer->name }}"
                data-code="CUST-{{ str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT) }}"
                data-mobile="{{ $customer->mobile }}"
                data-email="{{ $customer->email }}"
                data-address="{{ $customer->address }}"
                data-authority="{{ $customer->authority_name }}"
                @selected((int) $selectedCustomerId === (int) $customer->id)>
                CUST-{{ str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT) }} — {{ $customer->name }} @if($customer->mobile) — {{ $customer->mobile }} @endif
            </option>
        @endforeach
    </select>
</div>

<div class="col-12 @if(!$selectedCustomer) d-none @endif" id="crmCustomerPreview">
    <article class="card dg-card border">
        <div class="card-body dg-card-body py-3">
            <div class="row g-2 small">
                <div class="col-md-3"><span class="text-muted">Customer Code</span><div id="crmCustomerCode">{{ $selectedCustomer ? 'CUST-' . str_pad((string) $selectedCustomer->id, 6, '0', STR_PAD_LEFT) : '-' }}</div></div>
                <div class="col-md-3"><span class="text-muted">Customer Name</span><div id="crmCustomerName">{{ $selectedCustomer->name ?? '-' }}</div></div>
                <div class="col-md-3"><span class="text-muted">Mobile</span><div id="crmCustomerMobile">{{ $selectedCustomer->mobile ?? '-' }}</div></div>
                <div class="col-md-3"><span class="text-muted">Company / Authority</span><div id="crmCustomerAuthority">{{ $selectedCustomer->authority_name ?? '-' }}</div></div>
                <div class="col-md-6"><span class="text-muted">Email</span><div id="crmCustomerEmail">{{ $selectedCustomer->email ?? '-' }}</div></div>
                <div class="col-md-6"><span class="text-muted">Address</span><div id="crmCustomerAddress">{{ $selectedCustomer->address ?? '-' }}</div></div>
            </div>
        </div>
    </article>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('customer_search');
    var select = document.getElementById('customer_id');
    var preview = document.getElementById('crmCustomerPreview');

    if (!searchInput || !select) {
        return;
    }

    var options = Array.from(select.options).slice(1);

    function updatePreview() {
        var option = select.options[select.selectedIndex];
        if (!option || !option.value) {
            preview.classList.add('d-none');
            return;
        }

        document.getElementById('crmCustomerCode').textContent = option.dataset.code || '-';
        document.getElementById('crmCustomerName').textContent = option.dataset.name || '-';
        document.getElementById('crmCustomerMobile').textContent = option.dataset.mobile || '-';
        document.getElementById('crmCustomerAuthority').textContent = option.dataset.authority || '-';
        document.getElementById('crmCustomerEmail').textContent = option.dataset.email || '-';
        document.getElementById('crmCustomerAddress').textContent = option.dataset.address || '-';
        preview.classList.remove('d-none');
    }

    searchInput.addEventListener('input', function () {
        var term = searchInput.value.trim().toLowerCase();

        options.forEach(function (option) {
            var haystack = [
                option.dataset.code || '',
                option.dataset.name || '',
                option.dataset.mobile || '',
                option.dataset.authority || '',
                option.textContent || ''
            ].join(' ').toLowerCase();

            option.hidden = term !== '' && haystack.indexOf(term) === -1;
        });
    });

    select.addEventListener('change', updatePreview);
    updatePreview();
});
</script>
@endpush
