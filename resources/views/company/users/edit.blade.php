@extends('company.layout')

@section('content')

<!-- ALERT -->
@if(session('success'))
    <p style="color:#22c55e;">{{ session('success') }}</p>
@endif

@if($errors->any())
    <div style="color:#ef4444; margin-bottom:10px;">
        {{ $errors->first() }}
    </div>
@endif

<h2 style="color:#e2e8f0;">✏️ Edit Staff</h2>

<div style="background:#0f172a; padding:20px; border-radius:10px; max-width:500px;">

<form method="POST" action="{{ route('company.users.update', $user->id) }}">
    @csrf

    <!-- NAME -->
    <div style="margin-bottom:10px;">
        <label>Name</label><br>
        <input type="text" name="name" value="{{ $user->name }}" required
            style="width:100%; padding:8px; border-radius:6px; border:none;">
    </div>

    <!-- ROLE -->
    <div style="margin-bottom:10px;">
        <label>Role</label><br>
        <select name="job_role" required
            style="width:100%; padding:8px; border-radius:6px; border:none;">

            <option value="cashier" {{ $user->job_role=='cashier'?'selected':'' }}>Cashier</option>
            <option value="receiver" {{ $user->job_role=='receiver'?'selected':'' }}>Receiver</option>
            <option value="accountant" {{ $user->job_role=='accountant'?'selected':'' }}>Accountant</option>
            <option value="manager" {{ $user->job_role=='manager'?'selected':'' }}>Manager</option>

        </select>
    </div>

    <!-- BUTTON -->
    <button style="background:#3b82f6; color:white; padding:8px 20px; border:none; border-radius:6px;">
        💾 Update
    </button>

 <a href="{{ route('company.users.index') }}" style="margin-left:10px; color:#94a3b8;">
    Cancel
</a>
    </a>

</form>

</div>

@endsection