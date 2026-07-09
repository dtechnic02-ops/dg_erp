@extends('admin.layout')

@section('content')

<h2 style="margin-bottom:20px;">💳 Manual Payment</h2>

@if(session('success'))
    <p style="color:#22c55e;">{{ session('success') }}</p>
@endif

@if(session('error'))
    <p style="color:#ef4444;">{{ session('error') }}</p>
@endif

@if($errors->any())
    <div style="color:#ef4444;margin-bottom:10px;">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.manual.payment.store') }}" enctype="multipart/form-data"
      style="background:#1e293b;padding:20px;border-radius:8px;width:400px;">

    @csrf

    <!-- COMPANY -->
    <label>Company</label><br>
    <select name="company_id" required style="width:100%;padding:8px;margin-bottom:15px;">
        <option value="">Select Company</option>

        @forelse($companies as $c)
            <option value="{{ $c->id }}" {{ old('company_id') == $c->id ? 'selected' : '' }}>
                #{{ $c->id }} - {{ $c->company_name }}
            </option>
        @empty
            <option disabled>No company found</option>
        @endforelse
    </select>

    <!-- PLAN -->
    <label>Plan</label><br>
    <select name="plan_id" id="planSelect"
            style="width:100%;padding:8px;margin-bottom:15px;" required>

        <option value="">Select Plan</option>

        @forelse($plans as $p)
            <option value="{{ $p->id }}"
                    data-price="{{ $p->price ?? 0 }}"
                    {{ old('plan_id') == $p->id ? 'selected' : '' }}>
                {{ $p->name }} ({{ $p->user_limit }} Users) - Rs {{ $p->price }}
            </option>
        @empty
            <option disabled>No plans found</option>
        @endforelse

    </select>

    <!-- AMOUNT -->
    <label>Amount</label><br>
    <input type="number" name="amount" id="amountInput"
           value="{{ old('amount') }}"
           style="width:100%;padding:8px;margin-bottom:15px;background:#020617;color:white;"
           readonly required>

    <!-- SCREENSHOT -->
    <label>Screenshot (optional)</label><br>
    <input type="file" name="screenshot" style="margin-bottom:15px;">

    <button type="submit"
            style="background:#3b82f6;color:white;padding:10px;border:none;border-radius:5px;width:100%;">
        Save Payment
    </button>

</form>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const planSelect = document.getElementById('planSelect');
    const amountInput = document.getElementById('amountInput');

    function updateAmount() {
        let selected = planSelect.options[planSelect.selectedIndex];
        let price = selected.getAttribute('data-price');

        if (price && price !== "null" && price !== "") {
            amountInput.value = price;
        } else {
            amountInput.value = '';
        }
    }

    planSelect.addEventListener('change', updateAmount);

    // page load मा पनि run
    updateAmount();
});
</script>

@endsection