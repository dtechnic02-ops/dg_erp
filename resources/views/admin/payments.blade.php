@extends('admin.layout')

@section('content')

<h2 style="margin-bottom:10px;">💳 Payment Requests</h2>

{{-- ✅ SUCCESS MESSAGE --}}
@if(session('success'))
    <p style="color:lightgreen;">{{ session('success') }}</p>
@endif

{{-- 🔍 SEARCH + FILTER --}}
<div style="margin-bottom:15px; display:flex; gap:10px;">

    <form method="GET" style="display:flex; gap:10px;">

        <input type="text" name="search" placeholder="Search company..."
            value="{{ request('search') }}"
            style="padding:6px;border-radius:5px;border:none;">

        <button style="padding:6px 12px;background:#3b82f6;color:white;border:none;border-radius:5px;">
            🔍 Search
        </button>

    </form>

    {{-- FILTER BUTTONS --}}
    <a href="?status=all"><button style="padding:6px;">All</button></a>
    <a href="?status=pending"><button style="padding:6px;background:orange;">Pending</button></a>
    <a href="?status=approved"><button style="padding:6px;background:green;color:white;">Approved</button></a>
    <a href="?status=rejected"><button style="padding:6px;background:red;color:white;">Rejected</button></a>

</div>

{{-- 📊 TABLE --}}
<table style="width:100%;background:#1e293b;color:white;border-collapse:collapse;">

<tr style="background:#020617;">
    <th>ID</th>
    <th>Company</th>
    <th>Plan</th>
    <th>Amount</th>
    <th>Screenshot</th>
    <th>Status</th>
    <th>Action</th>
</tr>

@foreach($payments as $p)

<tr style="text-align:center;border-bottom:1px solid #334155;">

    <td>{{ $p->id }}</td>

    <td>{{ $p->company->company_name ?? 'N/A' }}</td>

    <td>{{ $p->plan->name ?? 'N/A' }}</td>

    <td>{{ $p->amount }}</td>

    {{-- Screenshot --}}
    <td>
        @if($p->screenshot)
            <a href="{{ asset('storage/'.$p->screenshot) }}" target="_blank" style="color:#38bdf8;">
                View
            </a>
        @else
            N/A
        @endif
    </td>

    {{-- Status --}}
    <td>
        @if($p->status == 'approved')
            <span style="color:lightgreen;">Approved</span>
        @elseif($p->status == 'pending')
            <span style="color:orange;">Pending</span>
        @else
            <span style="color:red;">Rejected</span>
        @endif
    </td>

    {{-- Actions --}}
    <td>

       <td>

    @php
        $status = trim(strtolower($p->status));
    @endphp

    {{-- Pending only --}}
    @if($status == 'pending')

        {{-- Approve --}}
        <form method="POST" action="{{ url('admin/payment/approve/'.$p->id) }}" style="display:inline;">
            @csrf
            <button style="background:#16a34a;color:white;border:none;padding:6px 10px;border-radius:5px;">
                ✔
            </button>
        </form>

        {{-- Reject --}}
        <form method="POST" action="{{ url('admin/payment/reject/'.$p->id) }}" style="display:inline;">
            @csrf
            <button style="background:#dc2626;color:white;border:none;padding:6px 10px;border-radius:5px;">
                ✖
            </button>
        </form>

    @else
        <span style="color:gray;">No Action</span>
    @endif

    {{-- Invoice --}}
    @if(Route::has('admin.invoice'))
        <a href="{{ route('admin.invoice', $p->id) }}" target="_blank">
            <button style="padding:5px 10px;margin-left:5px;">
                📄 Invoice
            </button>
        </a>
    @endif

</td>

</tr>

@endforeach

</table>

@endsection