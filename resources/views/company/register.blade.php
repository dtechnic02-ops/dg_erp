<!DOCTYPE html>
<html>
<head>
    <title>Register Company - DG ERP</title>

    <style>
        body {
            margin:0;
            font-family: Arial;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .left {
            flex: 1;
            background: #020617;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .left img {
            width: 120px;
            margin-bottom: 20px;
        }

        .left h1 {
            font-size: 40px;
        }

        .right {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .form-box {
            background: #1e293b;
            padding: 30px;
            border-radius: 10px;
            width: 350px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: none;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #3b82f6;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .login-link {
            text-align: center;
            margin-top: 15px;
        }

        .login-link a {
            color: #22c55e;
            text-decoration: none;
        }

    </style>
</head>

<body>

<div class="container">

    <!-- LEFT SIDE -->
    <div class="left">
        <img src="{{ asset('logo.png') }}">
        <h1>DG ERP</h1>
        <p>Start Your Business Journey</p>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right">

        <div class="form-box">
<h2>Register</h2>

{{-- 🔥 ERROR MESSAGE --}}
@if ($errors->any())
    <div style="color:red; margin-bottom:10px;">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('company.register.post') }}">
            @if ($errors->any())
    <div style="color:red; margin-bottom:10px;">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

           <form method="POST" action="/company/register">
                @csrf

                <input type="text" name="company_name" placeholder="Company Name" required>

                <input type="text" name="full_name" placeholder="Owner Name" required>

                <input type="email" name="email" placeholder="Email" required>

                <input type="text" name="username" placeholder="Username" required>

                <input type="password" name="password" placeholder="Password" required>

               <input type="text" name="mobile_no" placeholder="Mobile Number" required>

                <input type="text" name="country" placeholder="Country" required>

                <button type="submit">Register</button>
            </form>

            <div class="login-link">
                <a href="{{ url('/login') }}">← Back to Login</a>
            </div>

        </div>

    </div>

</div>

</body>
</html>