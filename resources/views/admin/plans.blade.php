@extends('admin.layout')

@section('content')

<h2 style="margin-bottom:15px;">📦 Plans Management</h2>

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

@php
    $edit = request('edit');
    $editPlan = $plans->where('id', $edit)->first();
@endphp

<!-- 🔥 FORM -->
<div style="background:#0f172a;padding:15px;border-radius:10px;margin-bottom:20px;">

<form method="POST" action="{{ $editPlan ? route('admin.plans.update', $editPlan->id) : route('admin.plans.store') }}">
    @csrf

    <div style="display:flex; gap:10px; flex-wrap:wrap;">

        <input type="text" name="name"
            value="{{ old('name', $editPlan->name ?? '') }}"
            placeholder="Plan Name"
            required
            style="padding:8px;border-radius:6px;border:none;flex:1;">

        <input type="number" name="user_limit"
            value="{{ old('user_limit', $editPlan->user_limit ?? '') }}"
            placeholder="Users"
            required
            style="padding:8px;border-radius:6px;border:none;width:120px;">

        <input type="number" name="customer_limit"
            value="{{ old('customer_limit', $editPlan->customer_limit ?? '') }}"
            placeholder="Customers"
            required
            style="padding:8px;border-radius:6px;border:none;width:120px;">

        <input type="number" name="price" id="priceInput"
            value="{{ old('price', $editPlan->price ?? '') }}"
            placeholder="Price"
            style="padding:8px;border-radius:6px;border:none;width:120px;">

        <select name="type" id="typeSelect" required style="padding:8px;border-radius:6px;border:none;">
            <option value="trial" {{ old('type', $editPlan->type ?? '') == 'trial' ? 'selected' : '' }}>Trial</option>
            <option value="monthly" {{ old('type', $editPlan->type ?? '') == 'monthly' ? 'selected' : '' }}>Monthly</option>
            <option value="yearly" {{ old('type', $editPlan->type ?? '') == 'yearly' ? 'selected' : '' }}>Yearly</option>
        </select>

        <button style="background:#3b82f6;color:white;padding:8px 20px;border:none;border-radius:6px;">
            {{ $editPlan ? 'Update' : 'Add' }}
        </button>

        @if($editPlan)
            <a href="{{ route('admin.plans') }}" style="color:#ef4444;align-self:center;">Cancel</a>
        @endif

    </div>
</form>

</div>

<!-- 🔥 TABLE -->
<table width="100%" style="background:#0f172a;border-radius:10px;border-collapse:collapse;">

<tr style="color:#94a3b8;border-bottom:1px solid #1e293b;">
    <th style="padding:10px;">Name</th>
    <th>Users</th>
    <th>Customers</th>
    <th>Price</th>
    <th>Days</th>
    <th>Type</th>
    <th>Action</th>
</tr>

@foreach($plans as $p)
<tr style="text-align:center;border-bottom:1px solid #1e293b;">

    <td style="padding:10px;">{{ $p->name }}</td>
    <td>{{ $p->user_limit }}</td>
    <td>{{ $p->customer_limit }}</td>
    <td>{{ $p->price }}</td>
    <td>{{ $p->duration_days }}</td>
    <td>{{ ucfirst($p->type) }}</td>

    <td>
        <a href="{{ route('admin.plans', ['edit' => $p->id]) }}" style="color:#3b82f6;">✏ Edit</a>
        |

        <form action="{{ route('admin.plans.delete', $p->id) }}" method="POST" style="display:inline;">
            @csrf
            <button onclick="return confirm('Delete this plan?')" 
                style="color:#ef4444;background:none;border:none;cursor:pointer;">
                Delete
            </button>
        </form>
    </td>

</tr>
@endforeach

</table>

<script>
// 🔥 AUTO PRICE ZERO FOR TRIAL
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('typeSelect');
    const priceInput = document.getElementById('priceInput');

    function handleType() {
        if (typeSelect.value === 'trial') {
            priceInput.value = 0;
            priceInput.readOnly = true;
        } else {
            priceInput.readOnly = false;
        }
    }

    typeSelect.addEventListener('change', handleType);
    handleType();
});
</script>

@endsection