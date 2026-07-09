@extends('admin.layout')

@section('content')

<h2>Company Registrations</h2>

@if(session('success'))
    <p style="color:lightgreen;">{{ session('success') }}</p>
@endif

@if(session('error'))
    <p style="color:red;">{{ session('error') }}</p>
@endif

<table style="width:100%;border-collapse:collapse;background:#1e293b;color:white;">

<tr style="background:#020617;">
    <th>Company</th>
    <th>Name</th>
    <th>Email</th>
    <th>Status</th>
    <th>Action</th>
</tr>

@foreach($registrations as $r)

<tr style="text-align:center;border-bottom:1px solid #334155;">

    <td>{{ $r->company_name }}</td>
    <td>{{ $r->full_name }}</td>
    <td>{{ $r->email }}</td>
    <td>{{ $r->status }}</td>

    <td>

        <!-- APPROVE -->
        <form method="POST" action="{{ route('admin.approve', $r->id) }}" style="display:inline;">
            @csrf
            <button style="background:#10b981;color:white;">Approve</button>
        </form>

        <!-- REJECT -->
        <form method="POST" action="{{ route('admin.reject', $r->id) }}" style="display:inline;">
            @csrf
            <button style="background:red;color:white;">Reject</button>
        </form>

    </td>

</tr>

@endforeach

</table>

@endsection