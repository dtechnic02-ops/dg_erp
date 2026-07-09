@extends('admin.layout')

@section('content')

<h2 style="margin-bottom:10px;">🏢 Companies Management</h2>

@if(session('success'))
    <p style="color:lightgreen;">{{ session('success') }}</p>
@endif

@if(session('error'))
    <p style="color:red;">{{ session('error') }}</p>
@endif

<!-- FILTER -->
<form method="GET" style="margin-bottom:15px;">
    
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..."
        style="padding:6px;border-radius:5px;border:none;">

    <select name="status" style="padding:6px;border-radius:5px;">
        <option value="">All</option>
        <option value="active" {{ request('status')=='active'?'selected':'' }}>Active</option>
        <option value="blocked" {{ request('status')=='blocked'?'selected':'' }}>Blocked</option>
    </select>

    <button style="padding:6px;background:#3b82f6;color:white;border:none;border-radius:5px;">
        Filter
    </button>
</form>

<table style="width:100%;border-collapse:collapse;background:#1e293b;color:white;font-size:14px;">

<tr style="background:#020617;">
    <th>ID</th>
    <th>Company</th>
    <th>Email</th>
    <th>User Limit</th>
    <th>Customer Limit</th>
    <th>Status</th>
    <th>Expiry Date</th>
    <th>Actions</th>
</tr>

@foreach($companies as $c)

<tr style="text-align:center;border-bottom:1px solid #334155;">
    
    <td>{{ $c->id }}</td>
    <td>{{ $c->company_name }}</td>
    <td>{{ $c->email }}</td>

    <!-- USER LIMIT -->
    <td>
        <form method="POST" action="{{ route('admin.company.limit', $c->id) }}">
            @csrf
            <input type="number" name="limit" value="{{ $c->selected_user_limit }}" style="width:50px;">
            <button style="background:#3b82f6;color:white;border:none;padding:3px 6px;border-radius:4px;">
                ✔
            </button>
        </form>
    </td>

    <!-- CUSTOMER LIMIT -->
    <td>
        <form method="POST" action="{{ route('admin.company.customer.limit', $c->id) }}">
            @csrf
            <input type="number" name="customer_limit" value="{{ $c->selected_customer_limit }}" style="width:50px;">
            <button style="background:#3b82f6;color:white;border:none;padding:3px 6px;">
                ✔
            </button>
        </form>
    </td>

    <!-- STATUS -->
    <td>
        @if($c->status == 'active')
            <span style="color:lightgreen;">Active</span>
        @else
            <span style="color:red;">Blocked</span>
        @endif
    </td>

    <!-- EXPIRY -->
    <td>
        {{ $c->expiry_date ?? 'N/A' }}

        @if(isset($c->days) && $c->days <= 3 && $c->days >= 0)
            <br><span style="color:orange;">Expiring Soon</span>
        @endif

        @if(isset($c->days) && $c->days < 0)
            <br><span style="color:red;">Expired</span>
        @endif
    </td>

    <!-- ACTIONS -->
    <td>

        @if(auth()->user()->role_id == 1)

            <!-- RESET PASSWORD -->
            <a href="{{ route('admin.company.reset', $c->id) }}">🔐</a>

            <!-- BLOCK / UNBLOCK -->
            @if($c->status == 'active')
                <a href="{{ route('admin.company.block', $c->id) }}" 
                   onclick="return confirm('Block this company?')">🚫</a>
            @else
                <a href="{{ route('admin.company.unblock', $c->id) }}" 
                   onclick="return confirm('Unblock this company?')">✅</a>
            @endif

            <!-- DELETE -->
            <form method="POST" action="{{ route('admin.company.delete', $c->id) }}" style="display:inline;">
                @csrf
                <input type="password" name="admin_password" placeholder="Password" required style="width:80px;">
                <button onclick="return confirm('Delete company?')" 
                    style="background:red;color:white;border:none;padding:4px 8px;">
                    ❌
                </button>
            </form>

        @endif

    </td>

</tr>

@endforeach

</table>

<!-- PAGINATION -->
<div style="margin-top:15px;">
    {{ $companies->links() }}
</div>

@endsection