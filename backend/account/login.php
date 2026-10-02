
<?php

/* =========================
   GET REDIRECT
========================= */

$redirect = $_GET['redirect'] ?? '';

/*
 * Allow only internal destinations
 */
$allowed_redirects = [
    'cart',
    'checkout',
    'shop'
];

if (!in_array($redirect, $allowed_redirects, true)) {
    $redirect = '';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - ShopHub</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #080b18;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .login-container {
            width: 100%;
            max-width: 1150px;
            min-height: 680px;
            display: flex;
            background: #0d1122;
            border: 1px solid #252b45;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        /* LEFT SIDE */

        .left-side {
            width: 50%;
            padding: 60px;
            background: linear-gradient(145deg, #0c1025, #15104a);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .left-side::after {
            content: "";
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: #693cff;
            opacity: 0.08;
            bottom: -200px;
            left: -100px;
        }

        .logo {
            font-size: 30px;
            font-weight: bold;
            position: relative;
            z-index: 2;
        }

        .logo span {
            color: #8b5cf6;
        }

        .small-title {
            color: #8b5cf6;
            margin-top: 10px;
            font-size: 14px;
        }

        .welcome {
            position: relative;
            z-index: 2;
        }

        .welcome h1 {
            font-size: 45px;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .welcome h1 span {
            color: #8b5cf6;
        }

        .welcome p {
            color: #aeb4ca;
            line-height: 1.8;
            max-width: 470px;
            font-size: 16px;
        }

        .features {
            margin-top: 35px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 22px;
        }

        .feature-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: rgba(139, 92, 246, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9b6cff;
            font-size: 20px;
        }

        .feature h3 {
            font-size: 15px;
            margin-bottom: 5px;
        }

        .feature p {
            color: #8f96ad;
            font-size: 13px;
        }

        .copyright {
            color: #686f86;
            font-size: 13px;
            position: relative;
            z-index: 2;
        }

        /* RIGHT SIDE */

        .right-side {
            width: 50%;
            padding: 55px 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            width: 100%;
            max-width: 470px;
        }

        .lock {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            background: rgba(139, 92, 246, 0.12);
            border: 1px solid rgba(139, 92, 246, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
            margin: 0 auto 25px;
        }

        .login-title {
            text-align: center;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .login-subtitle {
            text-align: center;
            color: #858ca2;
            margin-bottom: 35px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            font-size: 14px;
            font-weight: bold;
        }

        .input-box {
            width: 100%;
            height: 55px;
            background: #111626;
            border: 1px solid #303750;
            border-radius: 10px;
            color: white;
            padding: 0 17px;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }

        .input-box:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        .input-box::placeholder {
            color: #62697d;
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 5px 0 25px;
            font-size: 13px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #aab0c1;
        }

        .remember input {
            accent-color: #8b5cf6;
        }

        .options a {
            color: #9b6cff;
            text-decoration: none;
        }

        .login-btn {
            width: 100%;
            height: 55px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(90deg, #7c3aed, #4f6df5);
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(124, 58, 237, 0.3);
        }

        .or {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 28px 0;
            color: #6e7488;
            font-size: 13px;
        }

        .or::before,
        .or::after {
            content: "";
            height: 1px;
            background: #292f43;
            flex: 1;
        }

        .google-btn {
            width: 100%;
            height: 52px;
            border: 1px solid #303750;
            background: transparent;
            color: white;
            border-radius: 10px;
            font-size: 15px;
            cursor: pointer;
        }

        .google-btn:hover {
            background: #151a2c;
        }

        .create-account {
            text-align: center;
            margin-top: 28px;
            color: #858ca2;
            font-size: 14px;
        }

        .create-account a {
            color: #9b6cff;
            text-decoration: none;
            font-weight: bold;
        }

        /* MOBILE */

        @media (max-width: 850px) {
            .login-container {
                flex-direction: column;
            }

            .left-side,
            .right-side {
                width: 100%;
            }

            .left-side {
                padding: 40px;
                min-height: 400px;
            }

            .right-side {
                padding: 45px 30px;
            }

            .welcome h1 {
                font-size: 35px;
            }
        }

        @media (max-width: 500px) {
            body {
                padding: 10px;
            }

            .left-side {
                padding: 30px;
            }

            .right-side {
                padding: 35px 20px;
            }

            .login-title {
                font-size: 27px;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <!-- LEFT SIDE -->

    <div class="left-side">

        <div>
            <div class="logo">
                🛍️ Shop<span>Hub</span>
            </div>

            <div class="small-title">
                E-Commerce Dashboard
            </div>
        </div>


        <div class="welcome">

            <h1>
                Manage your store.<br>
                Grow your <span>business.</span>
            </h1>

            <p>
                ShopHub is a powerful e-commerce management
                platform for admins and vendors to manage
                products, orders, customers and analytics.
            </p>

            <div class="features">

                <div class="feature">
                    <div class="feature-icon">🔒</div>

                    <div>
                        <h3>Secure & Reliable</h3>
                        <p>Secure access to your dashboard.</p>
                    </div>
                </div>


                <div class="feature">
                    <div class="feature-icon">📊</div>

                    <div>
                        <h3>Powerful Analytics</h3>
                        <p>Manage your business easily.</p>
                    </div>
                </div>


                <div class="feature">
                    <div class="feature-icon">⚡</div>

                    <div>
                        <h3>Easy to Use</h3>
                        <p>Everything in one place.</p>
                    </div>
                </div>

            </div>

        </div>


        <div class="copyright">
            © 2026 ShopHub. All rights reserved.
        </div>

    </div>


    <!-- RIGHT SIDE -->

    <div class="right-side">

        <div class="login-box">

            <div class="lock">
                🔐
            </div>

            <h2 class="login-title">
                Welcome Back
            </h2>

            <p class="login-subtitle">
                Sign in to your account to continue
            </p>


            <!-- LOGIN FORM -->

            <form action="../fun/do_login.php" method="post">

                <!-- KEEP REDIRECT -->

                <input
                    type="hidden"
                    name="redirect"
                    value="<?= htmlspecialchars($redirect) ?>"
                >


                <div class="form-group">

                    <label>Email Address</label>

                    <input
                        type="email"
                        name="email"
                        class="input-box"
                        placeholder="Enter your email"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        class="input-box"
                        placeholder="Enter your password"
                        required
                    >

                </div>


                <div class="options">

                    <label class="remember">
                        <input type="checkbox" name="remember_me">
                        Remember me
                    </label>

                    <a href="forgot-password.php">
                        Forgot password?
                    </a>

                </div>


                <button type="submit" class="login-btn">
                    Sign In →
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>

