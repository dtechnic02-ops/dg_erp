@extends('admin.layout')

@section('content')

<h2 style="color:#e2e8f0;">🔐 Role Permissions</h2>

@if(session('success'))
    <p style="color:#22c55e;">{{ session('success') }}</p>
@endif

<form method="POST" action="{{ route('role.permission.update') }}">
    @csrf

    <!-- ROLE SELECT -->
    <select name="role_id" required style="padding:8px;margin-bottom:10px;">
        <option value="2">Company Admin</option>
        <option value="3">Staff</option>
    </select>

    <br>

    @foreach($permissions as $perm)
        <label style="display:block;margin-bottom:6px;">
            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}">
            {{ ucfirst(str_replace('_',' ', $perm->name)) }}
        </label>
    @endforeach

    <button style="margin-top:10px;background:#3b82f6;color:white;padding:8px 20px;border:none;">
        Save
    </button>

</form>

@endsection