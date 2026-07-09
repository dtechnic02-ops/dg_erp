@extends('company.layout')

@section('content')

@if(session('success'))
    <p style="color:#22c55e;">{{ session('success') }}</p>
@endif

@if($errors->any())
    <div style="color:#ef4444; margin-bottom:9px;">
        {{ $errors->first() }}
    </div>
@endif

<!-- HEADER -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:7px;">
    <h3 style="color:#e2e8f0; margin:0;">👨‍💻 Staff Management</h3>

    <a href="{{ route('company.permissions.index') }}">🔐 Permissions</a>

    <div style="color:#facc5;">
        👥 {{ $users->count() }} / {{ auth()->user()->company->selected_user_limit }}
    </div>
</div>

<!-- ADD STAFF -->
<div style="background:#0f172a; padding:10px; border-radius:5px; margin-bottom:10px;">
<form method="POST" action="{{ route('company.users.store') }}" style="display:flex; gap:10px;">
    @csrf

    <input type="text" name="name" placeholder="Name" required style="flex:1; padding:8px; border-radius:6px; border:none;">

    <input type="email" name="email" placeholder="Email" required style="flex:1; padding:8px; border-radius:6px; border:none;">

    <input type="password" name="password" placeholder="Password" required style="flex:1; padding:8px; border-radius:6px; border:none;">

    <select name="job_role" required style="flex:1; padding:8px; border-radius:6px; border:none;">
        <option value="">Select Role</option>
        <option value="cashier">Cashier</option>
        <option value="receiver">Receiver</option>
        <option value="accountant">Accountant</option>
        <option value="manager">Manager</option>
    </select>

    <button style="background:#3b82f6; color:white; padding:8px 20px; border:none; border-radius:6px;">
        ➕ Add
    </button>
</form>
</div>

<!-- STAFF LIST -->
<table width="100%" style="background:#0f172a; border-radius:10px; border-collapse:collapse;">

<tr style="color:#94a3b8; border-bottom:1px solid #1e293b;">
    <th style="padding:10px;">Name</th>
    <th>Email</th>
    <th>Status</th>
    <th>Actions</th>
    <th>Role</th>
</tr>

@foreach($users as $user)
<tr style="text-align:center; border-bottom:1px solid #1e293b;">
<td style="padding:10px;">

@if($user->last_seen &&
    \Carbon\Carbon::parse($user->last_seen)
    ->gt(now()->subMinutes(2)))


        <span style="
            display:inline-block;
            width:10px;
            height:10px;
            background:#22c55e;
            border-radius:50%;
            margin-right:6px;
        "></span>

    @else

        <span style="
            display:inline-block;
            width:10px;
            height:10px;
            background:#ef4444;
            border-radius:50%;
            margin-right:6px;
        "></span>

    @endif


</td>

    <td style="padding:10px;">{{ $user->name }}</td>
    <td>{{ $user->email }}</td>

    <td>
        @if($user->account_status == 'active')
            <span style="color:#22c55e;">Active</span>
        @else
            <span style="color:#ef4444;">Blocked</span>
        @endif
    </td>

    <td>

        @if(auth()->user()->role_id == 2 || auth()->user()->hasPermission('block_user'))
            @if($user->account_status == 'active')
                <a href="{{ route('company.users.block', $user->id) }}" style="color:#facc15;">Block</a>
            @else
                <a href="{{ route('company.users.unblock', $user->id) }}" style="color:#22c55e;">Unblock</a>
            @endif
            |
        @endif

        @if(auth()->user()->role_id == 2 || auth()->user()->hasPermission('delete_user'))
        <form action="{{ route('company.users.delete', $user->id) }}" method="POST" style="display:inline;">
            @csrf
            <button style="color:#ef4444; background:none; border:none; cursor:pointer;">
                Delete
            </button>
        </form>
        |
        @endif

        @if(auth()->user()->role_id == 2 || auth()->user()->hasPermission('reset_password'))
            <a href="{{ route('company.users.reset', $user->id) }}" style="color:#3b82f6;">
                Reset
            </a>
            |
        @endif

        <a href="{{ route('company.permissions.index') }}" style="color:#a855f7;">
            Permission
        </a>
        |

        <a href="{{ route('company.users.edit', $user->id) }}" style="color:#facc15;">
            Edit
        </a>

    </td>

    <td>{{ ucfirst($user->job_role ?? 'N/A') }}</td>

</tr>
@endforeach

</table>

@endsection