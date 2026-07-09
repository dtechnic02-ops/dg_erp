<!DOCTYPE html>
<html>
<head>
    <title>DG ERP Admin</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            margin:0;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            background: #30393d;
            color: white;
        }

        .sidebar {
            width: 220px;
            background: #020617;
            height: 100vh;
            padding: 20px;
        }

        .logo {
            width: 140px;
            margin-bottom: 20px;
        }

        .user-box {
            background: rgba(255,255,255,0.05);
            padding:10px;
            border-radius:10px;
            margin-bottom:20px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap:10px;
            color: #cbd5f5;
            text-decoration: none;
            padding: 10px;
            margin: 6px 0;
            border-radius: 8px;
        }

        .sidebar a:hover {
            background: #1e293b;
        }

        .content {
            flex: 1;
            padding: 20px;
            background: #0f172a;
        }

        .topbar {
            background: rgba(255,255,255,0.05);
            padding:10px 15px;
            border-radius:10px;
            margin-bottom:15px;
            display:flex;
            justify-content:space-between;
        }

        .logout-btn {
            width:100%;
            padding:10px;
            background:#ef4444;
            color:white;
            border:none;
            border-radius:8px;
            margin-top:15px;
            cursor:pointer;
        }

        .logout-btn:hover {
            background:#dc2626;
        }
    </style>
</head>

<body>

<!-- 🔥 SIDEBAR -->
<div class="sidebar">

    <img src="{{ asset('logo.png') }}" class="logo">

    @auth
    <div class="user-box">
        <strong>{{ auth()->user()->name }}</strong><br>
        <small>{{ auth()->user()->email }}</small>
    </div>
    @endauth

    <!-- MAIN MENU -->
    <a href="{{ route('admin.dashboard') }}"><i class="fa fa-home"></i> Dashboard</a>
    <a href="{{ route('admin.companies') }}"><i class="fa fa-building"></i> Companies</a>
    <a href="{{ route('admin.registrations') }}"><i class="fa fa-file"></i> Registrations</a>

    <!-- OPTIONAL (only if routes exist) -->
    @if(Route::has('admin.payments'))
        <a href="{{ route('admin.payments') }}"><i class="fa fa-credit-card"></i> Payments</a>
    @endif

    @if(Route::has('admin.users'))
        <a href="{{ route('admin.users') }}"><i class="fa fa-users"></i> Users</a>
    @endif

    {{-- 🔒 ONLY SUPER ADMIN --}}
    @auth
    @if(auth()->user()->role_id == 1)

        @if(Route::has('admin.manual.payment'))
            <a href="{{ route('admin.manual.payment') }}"><i class="fa fa-money-bill"></i> Manual Payment</a>
        @endif

        @if(Route::has('admin.plans'))
            <a href="{{ route('admin.plans') }}"><i class="fa fa-box"></i> Plans</a>
        @endif

    @endif
    @endauth

    <!-- LOGOUT -->
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="logout-btn">
            <i class="fa fa-sign-out-alt"></i> Logout
        </button>
    </form>

</div>

<!-- 🔥 CONTENT -->
<div class="content">

    <!-- 🔥 TOPBAR -->
    <div class="topbar">

        @auth
            <div>Welcome, {{ auth()->user()->name }}</div>
        @else
            <div>Welcome</div>
        @endauth

        <div>{{ now()->format('d M Y') }}</div>

    </div>

    @yield('content')

</div>

</body>
</html>