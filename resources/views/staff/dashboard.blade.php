@section('content')
@extends('company.layout')
<h2 style="color:#e2e8f0;">🧑‍💻 Staff Dashboard</h2>

<div style="display:flex; gap:15px; flex-wrap:wrap;">

    <div style="background:#1e293b;padding:15px;border-radius:8px;">
        👥 Customers<br>
        <b>View & Manage</b>
    </div>

    <div style="background:#1e293b;padding:15px;border-radius:8px;">
        📦 Orders<br>
        <b>Handle Orders</b>
    </div>

    <div style="background:#1e293b;padding:15px;border-radius:8px;">
        💬 Messages<br>
        <b>Customer Support</b>
    </div>

</div>

@endsection