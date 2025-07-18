@extends('layouts.header')
@extends('layouts.navbar')

@section('title', 'Home')

@section('styles')
<style>
    body {
        background: linear-gradient(to bottom, #ffffff 0%, #fef4f7 100%);
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        position: relative;
    }

    .floating-shapes {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .floating-shapes span {
        position: absolute;
        display: block;
        border-radius: 50%;
        opacity: 0.4;
        animation: float 18s linear infinite;
    }

    .shape1 { width: 100px; height: 100px; background-color: #ffe3ed; top: 15%; left: 10%; animation-delay: 0s; }
    .shape2 { width: 60px; height: 60px; background-color: #ffd6ea; top: 40%; left: 85%; animation-delay: 2s; }
    .shape3 { width: 120px; height: 120px; background-color: #ffc6e3; top: 65%; left: 15%; animation-delay: 4s; }
    .shape4 { width: 80px; height: 80px; background-color: #fff0f7; top: 80%; left: 75%; animation-delay: 6s; }
    .shape5 { width: 90px; height: 90px; background-color: #ffe0f0; top: 85%; left: 10%; animation-delay: 8s; }
    .shape6 { width: 70px; height: 70px; background-color: #ffd6eb; top: 92%; left: 50%; animation-delay: 10s; }
    .shape7 { width: 100px; height: 100px; background-color: #ffcce2; top: 97%; left: 80%; animation-delay: 12s; }
    .shape8 { width: 60px; height: 60px; background-color: #fff5fb; top: 105%; left: 30%; animation-delay: 14s; }
    .shape9 { width: 80px; height: 80px; background-color: #ffe9f3; top: 5%; left: 40%; animation-delay: 1.5s; }
    .shape10 { width: 100px; height: 100px; background-color: #ffd4eb; top: 10%; left: 58%; animation-delay: 3s; }
    .shape11 { width: 80px; height: 80px; background-color: #ffeef6; top: 25%; left: 32%; animation-delay: 1s; }

    @keyframes float {
        0% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-30px) rotate(180deg); }
        100% { transform: translateY(0) rotate(360deg); }
    }

    .wrapper {
        margin-top: 120px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 40px 20px;
        gap: 60px;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        flex-wrap: wrap;
        position: relative;
        z-index: 1;
    }

    .hero-section {
        flex: 1;
        min-width: 300px;
         margin-top: 1.1in;
    }

    #typed-text-container {
        display: inline-block;
        min-height: 7rem; /* enough height for 2 lines of large text */
        margin-bottom: 20px;
    }

    #typed-text {
        font-size: 3rem;
        color: #e91e63;
        font-weight: 800;
        white-space: pre-line; /* this allows \n to create new lines */
        border-right: 3px solid #e91e63;
        animation: blink 0.75s step-end infinite;
    }

    @keyframes blink {
        0%, 100% { border-color: transparent; }
        50% { border-color: #e91e63; }
    }

    .subheading {
        font-size: 1.5rem;
        font-weight: 500;
        color: #555;
        line-height: 1.7;
        max-width: 500px;
        margin-top: 25px;
    }


    .auth-container {
        flex: 1;
        background-color: #F5F5F5;
        border: 1px solid #F5F5F5;
        padding: 50px 40px;
        border-radius: 12px;
        max-width: 500px;
        width: 100%;
        box-shadow: 0 8px 20px rgba(190, 157, 157, 0.1);
    }

    .form-toggle {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
    }

    .form-toggle button {
        background-color: transparent;
        border: none;
        font-size: 1.1rem;
        margin: 0 10px;
        padding: 10px 20px;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        color: #888;
    }

    .form-toggle button.active {
        color: #e91e63;
        border-color: #e91e63;
        font-weight: 600;
    }

    .form-group {
        margin-bottom: 20px;
        text-align: left;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: #333;
    }

    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    .btn-submit {
        background-color: #e91e63;
        color: white;
        padding: 14px 36px;
        font-size: 1rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        width: 100%;
        margin-top: 15px;
    }

    .btn-submit:hover {
        background-color: #d81557;
    }

    .form-section {
        display: none;
    }

    .form-section.active {
        display: block;
    }

    @media (max-width: 992px) {
        .wrapper {
            flex-direction: column;
            align-items: center;
            gap: 40px;
        }

        .hero-section h1 {
            font-size: 2.4rem;
            text-align: center;
        }

        .hero-section p {
            text-align: center;
        }
    }
</style>
@endsection

@section('content')
<div class="floating-shapes">
    <span class="shape1"></span>
    <span class="shape2"></span>
    <span class="shape3"></span>
    <span class="shape4"></span>
    <span class="shape5"></span>
    <span class="shape6"></span>
    <span class="shape7"></span>
    <span class="shape8"></span>
    <span class="shape9"></span>
    <span class="shape10"></span>
    <span class="shape11"></span>
</div>

<div class="wrapper">
    <!-- Hero Section -->
    <div class="hero-section">
        <h1>
            <span id="typed-text-container">
                <span id="typed-text"></span>
            </span>
        </h1>
        <h2 class="subheading">Your supportive suite of AI-powered tools to help young learners build confidence in reading and writing.</h2>
    </div>

        <!-- Auth Forms -->
        <div class="auth-container">
            @if (session('status'))
                <div style="background-color: #e6f7f1; color: #1b5e20; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                    {{ session('status') }}
                </div>
            @endif

            <div class="form-toggle">
                <button id="loginTab" class="active" onclick="showForm('login')">Log In</button>
                <button id="registerTab" onclick="showForm('register')">Sign Up</button>
            </div>


        <!-- Login Form -->
        <div id="loginForm" class="form-section active">
            @if ($errors->any())
                <div style="background-color: #fdecea; color: #b00020; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ url('/login') }}">
                @csrf
                <div class="form-group">
                    <label for="login_email">Email</label>
                    <input type="email" id="login_email" name="email" value="{{ old('email') }}" required>
                </div>

                <div class="form-group">
                    <label for="login_password">Password</label>
                    <input type="password" id="login_password" name="password" required>
                    <a href="javascript:void(0)" onclick="showForm('forgot')" style="display: inline-block; margin-top: 5px; font-size: 0.9rem; color: #e91e63; text-decoration: underline;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-submit">Log In</button>
            </form>
        </div>

    <!-- Forgot Password Form -->
    <div id="forgotForm" class="form-section">
        <form method="POST" action="{{ url('/password/manual-reset') }}">
            @csrf
            <div class="form-group">
                <label for="forgot_email">Email</label>
                <input type="email" id="forgot_email" name="email" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_new_password">Confirm New Password</label>
                <input type="password" id="confirm_new_password" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn-submit">Reset Password</button>

            <div style="margin-top: 10px;">
                <a href="javascript:void(0)" onclick="showForm('login')" style="font-size: 0.9rem; color: #e91e63; text-decoration: underline;">Back to Login</a>
            </div>
        </form>
    </div>


        <!-- Register Form -->
        <div id="registerForm" class="form-section">
            <form method="POST" action="{{ url('/register') }}">
                @csrf
                <div class="form-group">
                    <label for="username">Name</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="grade_level">Grade Level</label>
                    <input type="text" id="grade_level" name="grade_level" required>
                </div>

                <div class="form-group">
                    <label for="register_password">Password</label>
                    <input type="password" id="register_password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required>
                </div>

                <button type="submit" class="btn-submit">Sign Up</button>
            </form>
        </div>
    </div>
</div>

<script>
    function showForm(form) {
        // Hide all forms
        document.getElementById('loginForm').classList.remove('active');
        document.getElementById('registerForm').classList.remove('active');
        document.getElementById('forgotForm').classList.remove('active');

        // Reset tab styles
        document.getElementById('loginTab').classList.remove('active');
        document.getElementById('registerTab').classList.remove('active');

        // Show selected form
        if (form === 'login') {
            document.getElementById('loginForm').classList.add('active');
            document.getElementById('loginTab').classList.add('active');
        } else if (form === 'register') {
            document.getElementById('registerForm').classList.add('active');
            document.getElementById('registerTab').classList.add('active');
        } else if (form === 'forgot') {
            document.getElementById('forgotForm').classList.add('active');
        }
    }


        // Typewriter animation
        const text = "Meet Your New AI Learning Buddy!";
        let index = 0;

        function type() {
            if (index < text.length) {
                document.getElementById("typed-text").textContent += text.charAt(index);
                index++;
                setTimeout(type, 80);
            } else {
                setTimeout(() => {
                    document.getElementById("typed-text").textContent = '';
                    index = 0;
                    type();
                }, 10000);
            }
        }

        window.onload = function () {
            type();

            @if (session('status'))
                showForm('login');
            @endif
        };

</script>
@endsection

