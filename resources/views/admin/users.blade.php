@extends('admin.layout')

@section('content')

<h2 style="margin-bottom:15px;">👤 Users Management</h2>

@if(session('success'))
    <p style="color:#22c55e;">{{ session('success') }}</p>
@endif

@if(session('error'))
    <p style="color:#ef4444;">{{ session('error') }}</p>
@endif

<!-- 🔍 FILTER -->
<form method="GET" style="margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap;">

    <input type="text" name="search" value="{{ request('search') }}" 
        placeholder="Search name or email"
        style="padding:8px;border-radius:6px;border:none;">

    <select name="status" style="padding:8px;border-radius:6px;">
        <option value="">All Status</option>
        <option value="active" {{ request('status')=='active'?'selected':'' }}>Active</option>
        <option value="blocked" {{ request('status')=='blocked'?'selected':'' }}>Blocked</option>
    </select>

    <select name="role" style="padding:8px;border-radius:6px;">
        <option value="">All Roles</option>
        <option value="1" {{ request('role')=='1'?'selected':'' }}>Admin</option>
        <option value="2" {{ request('role')=='2'?'selected':'' }}>Company</option>
        <option value="3" {{ request('role')=='3'?'selected':'' }}>Staff</option>
    </select>

    <button style="padding:8px 15px;background:#3b82f6;color:white;border:none;border-radius:6px;">
        Filter
    </button>

</form>

<!-- 🔥 TABLE -->
<table width="100%" style="background:#0f172a;border-radius:10px;border-collapse:collapse;">

<tr style="color:#94a3b8;border-bottom:1px solid #1e293b;">
    <th style="padding:10px;">ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Company</th>
    <th>Role</th>
    <th>Status</th>
    <th>Action</th>
</tr>

@foreach($users as $u)
<tr style="text-align:center;border-bottom:1px solid #1e293b;">


<td style="padding:10px;">

    @if($u->last_seen &&
        \Carbon\Carbon::parse($u->last_seen)
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

    {{ $u->id }}

</td>

    <td>{{ $u->name }}</td>
    <td>{{ $u->email }}</td>
    <td>{{ $u->company->company_name ?? 'N/A' }}</td>

    <td>
        @if($u->role_id == 1) Admin
        @elseif($u->role_id == 2) Company
        @else Staff
        @endif
    </td>

    <td>
        @if($u->account_status == 'blocked')
            <span style="color:#ef4444;">🚫 Blocked</span>
        @else
            <span style="color:#22c55e;">✅ Active</span>
        @endif
    </td>

    <td>

        {{-- 🔒 ONLY SUPER ADMIN --}}
        @if(auth()->user()->role_id == 1)

            {{-- 🔐 BLOCK / UNBLOCK --}}
            @if($u->account_status == 'blocked')
                <a href="{{ route('admin.user.unblock', $u->id) }}" style="color:#22c55e;">Unblock</a>
            @else
                <a href="{{ route('admin.user.block', $u->id) }}" style="color:#facc15;">Block</a>
            @endif

            |

            {{-- 🔐 RESET PASSWORD --}}
            <a href="{{ route('admin.user.reset', $u->id) }}" style="color:#38bdf8;">
                Reset
            </a>

            |

            {{-- 🔐 DELETE --}}
            <form action="{{ route('admin.user.delete', $u->id) }}" method="POST" style="display:inline;">
                @csrf
                <button onclick="return confirm('Delete this user?')" 
                    style="color:#ef4444;background:none;border:none;cursor:pointer;">
                    Delete
                </button>
            </form>

        @endif

    </td>

</tr>
@endforeach

</table>

<!-- 🔥 PAGINATION -->
<div style="margin-top:15px;">
    {{ $users->appends(request()->query())->links() }}
</div>

@endsection