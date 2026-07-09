@extends('company.layout')

@section('content')

<h2 style="color:#e2e8f0; margin-bottom:20px;">🔐 Staff Permission Control</h2>

@if(session('success'))
    <p style="color:#22c55e;">{{ session('success') }}</p>
@endif

<form action="{{ route('company.permissions.update') }}" method="POST">
    @csrf

    <div style="background:#0f172a; padding:20px; border-radius:10px;">

        @foreach($permissions as $perm)
            <div style="margin-bottom:10px;">
                <label style="color:#e2e8f0;">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                        {{ in_array($perm->id, $rolePermissions) ? 'checked' : '' }}>
                    
                    {{ $perm->name }}
                </label>
            </div>
        @endforeach

        <br>

        <button style="background:#3b82f6; color:white; padding:10px 20px; border:none; border-radius:6px;">
            💾 Save Permissions
        </button>

    </div>

</form>

@endsection