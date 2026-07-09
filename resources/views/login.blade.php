<!DOCTYPE html>
<html>
<head>
    <title>Login - ERP System</title>

    <style>
        body {
            margin:0;
            font-family: Arial;
            background: linear-gradient(135deg, #1e293b, #0f172a);
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

        .left h1 {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .left p {
            color: #94a3b8;
        }

        .right {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            background: #1e293b;
            padding: 40px;
            border-radius: 10px;
            width: 320px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
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
        }

        button:hover {
            background: #2563eb;
        }

        .register-btn {
            margin-top: 15px;
            display: block;
            text-align: center;
            background: #10b981;
            padding: 10px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
        }

        .register-btn:hover {
            background: #059669;
        }

        .message {
            text-align:center;
            margin-bottom:10px;
            font-size:14px;
        }

        .error { color: #ef4444; }
        .success { color: #22c55e; }
    </style>
</head>

<body>

<div class="container">

    <div class="left">
        <img src="{{ asset('logo.png') }}" width="120">
        <h1>DG ERP</h1>
        <p>Manage Your Business Smartly</p>
    </div>

    <div class="right">

        <div class="login-box">

            <h2 style="text-align:center;">Login</h2>

           

            <!-- ERROR -->
            @if(session('error'))
                <p class="message error">
                    {{ session('error') }}
                </p>
            @endif

            <!-- VALIDATION ERRORS -->
            @if ($errors->any())
                <div class="message error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                <input type="email" name="email" placeholder="Email" required>

                <input type="password" name="password" placeholder="Password" required>

                <button type="submit">Login</button>
            </form>

            <!-- Register -->
            <a href="{{ route('company.register') }}" class="register-btn">
                Register Company
            </a>

        </div>

    </div>

</div>

</body>
</html>