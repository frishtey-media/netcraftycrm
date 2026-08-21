<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Calling Panel Login</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at 15% 20%, rgba(13, 110, 253, 0.35), transparent 30%),
                radial-gradient(circle at 85% 80%, rgba(0, 198, 255, 0.25), transparent 30%),
                linear-gradient(135deg, #07111f 0%, #0d1b2e 45%, #102b4a 100%);
            overflow: hidden;
        }

        /* Background circles */
        .bg-circle {
            position: fixed;
            border-radius: 50%;
            filter: blur(2px);
            opacity: 0.35;
            pointer-events: none;
        }

        .circle-1 {
            width: 280px;
            height: 280px;
            background: #0d6efd;
            top: -100px;
            left: -80px;
        }

        .circle-2 {
            width: 220px;
            height: 220px;
            background: #00c6ff;
            bottom: -80px;
            right: -50px;
        }

        .circle-3 {
            width: 100px;
            height: 100px;
            background: #ffffff;
            top: 25%;
            right: 15%;
            opacity: 0.06;
        }

        /* Main wrapper */
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        /* Login card */
        .login-card {
            width: 100%;
            max-width: 430px;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 24px;
            padding: 38px;
            box-shadow:
                0 25px 70px rgba(0, 0, 0, 0.35),
                0 0 0 1px rgba(255, 255, 255, 0.15);
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Logo */
        .logo-box {
            text-align: center;
            margin-bottom: 22px;
        }

        .logo-box img {
            width: 190px;
            max-width: 75%;
            height: auto;
        }

        /* Calling icon */
        .calling-icon {
            width: 72px;
            height: 72px;
            margin: 5px auto 15px;
            border-radius: 20px;
            background: linear-gradient(135deg, #0d6efd, #00a8ff);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            box-shadow: 0 10px 25px rgba(13, 110, 253, 0.35);
        }

        .login-title {
            font-size: 25px;
            font-weight: 700;
            color: #172033;
            margin-bottom: 5px;
        }

        .login-subtitle {
            color: #7a8494;
            font-size: 14px;
            margin-bottom: 28px;
        }

        /* Form */
        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #344054;
            margin-bottom: 8px;
        }

        .input-group-custom {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #8b95a5;
            font-size: 18px;
            z-index: 5;
        }

        .form-control {
            height: 52px;
            border: 1px solid #d9dee7;
            border-radius: 12px !important;
            padding-left: 45px;
            padding-right: 45px;
            font-size: 15px;
            background: #f8fafc;
            transition: all 0.25s ease;
        }

        .form-control:focus {
            background: #fff;
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.10);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #7d8796;
            font-size: 18px;
            z-index: 5;
            cursor: pointer;
        }

        /* Login button */
        .login-btn {
            height: 52px;
            border: 0;
            border-radius: 12px;
            width: 100%;
            background: linear-gradient(135deg, #0d6efd, #0062d9);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.25);
            transition: all 0.25s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(13, 110, 253, 0.35);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        /* Alert */
        .alert {
            border-radius: 12px;
            font-size: 14px;
            border: 0;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: #98a2b3;
        }

        .secure-login {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #667085;
            margin-top: 10px;
        }

        /* Mobile */
        @media (max-width: 576px) {

            body {
                overflow-y: auto;
            }

            .login-wrapper {
                min-height: 100vh;
                padding: 18px;
            }

            .login-card {
                padding: 28px 22px;
                border-radius: 20px;
            }

            .logo-box img {
                width: 165px;
            }

            .calling-icon {
                width: 64px;
                height: 64px;
                font-size: 29px;
            }

            .login-title {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

    <!-- Background -->
    <div class="bg-circle circle-1"></div>
    <div class="bg-circle circle-2"></div>
    <div class="bg-circle circle-3"></div>

    <div class="login-wrapper">

        <div class="login-card">

            <!-- Logo -->
            <div class="logo-box">
                <a href="/">
                    <img src="{{ asset('images/netc2.png') }}" alt="Netcrafty">
                </a>
            </div>

            <!-- Calling Icon -->
            <div class="calling-icon">
                <i class="bi bi-headset"></i>
            </div>

            <div class="text-center">
                <div class="login-title">
                    Calling Panel
                </div>

                <div class="login-subtitle">
                    Sign in to manage your calling orders
                </div>
            </div>

            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    <div class="fw-semibold mb-1">
                        <i class="bi bi-exclamation-triangle"></i>
                        Please check your details
                    </div>

                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ url('/calling/login') }}">
                @csrf

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label">
                        Email Address
                    </label>

                    <div class="input-group-custom">

                        <i class="bi bi-envelope input-icon"></i>

                        <input type="email" name="email" class="form-control" placeholder="Enter your email"
                            value="{{ old('email') }}" autocomplete="email" required>

                    </div>
                </div>

                <!-- Password -->
                <div class="mb-4">

                    <label class="form-label">
                        Password
                    </label>

                    <div class="input-group-custom">

                        <i class="bi bi-lock input-icon"></i>

                        <input type="password" name="password" id="password" class="form-control"
                            placeholder="Enter your password" autocomplete="current-password" required>

                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye" id="passwordIcon"></i>
                        </button>

                    </div>

                </div>

                <!-- Login -->
                <button type="submit" class="login-btn">

                    <i class="bi bi-box-arrow-in-right me-2"></i>

                    Sign In

                </button>

            </form>

            <!-- Footer -->
            <div class="login-footer">

                <div>
                    © {{ date('Y') }} Netcrafty
                </div>

                <div class="secure-login">
                    <i class="bi bi-shield-check"></i>
                    Secure Calling Panel
                </div>

            </div>

        </div>

    </div>

    <script>
        function togglePassword() {

            const password = document.getElementById('password');
            const icon = document.getElementById('passwordIcon');

            if (password.type === 'password') {

                password.type = 'text';

                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');

            } else {

                password.type = 'password';

                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');

            }
        }
    </script>

</body>

</html>
