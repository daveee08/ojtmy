<!-- @extends('layouts.header') -->
@extends('layouts.navbar')

@section('title', 'Login - CK Tools')

@section('styles')
    <style>
        .container {
            margin-top: 100px; /* Adjusted for mobile view */
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            flex-direction: column;
            padding: 0 1rem; /* Mobile-first padding */
            width: 100%;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            box-sizing: border-box;
        }

        .hero {
            background-color: #F5F5F5;
            border: 1px solid #F5F5F5;
            padding: 1.5rem; /* Mobile-first padding */
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 1rem; /* Adjusted margin */
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.25rem; /* Adjusted margin */
            color: #333;
            font-size: 0.9rem; /* Adjusted font size */
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem; /* Increased padding for touch friendliness */
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem; /* Consistent font size */
            min-height: 48px; /* Ensure touch friendliness */
        }

        .error {
            color: #e91e63;
            font-size: 0.8rem; /* Adjusted font size */
            margin-top: 0.5rem;
            margin-bottom: 1rem; /* Add margin to separate from form */
            text-align: left;
        }

        .btn-login {
            background-color: #e91e63;
            color: white;
            padding: 0.75rem 1.5rem; /* Increased padding for touch friendliness */
            font-size: 1rem; /* Consistent font size */
            border: none;
            border-radius: 8px; /* Slightly larger border-radius for touch */
            cursor: pointer;
            width: 100%;
        }

        .btn-login:hover {
            background-color: #d81557;
        }

        .register-link {
            margin-top: 1rem; /* Adjusted margin */
            color: #666;
            font-size: 0.9rem; /* Adjusted font size */
        }

        .register-link a {
            color: #e91e63;
            text-decoration: none;
        }

        /* Media queries for larger screens */
        @media (min-width: 768px) {
            .container {
                margin-top: 170px;
                padding: 0 20px;
            }
            .hero {
                padding: 60px 40px;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group label {
                margin-bottom: 5px;
                font-size: inherit;
            }
            .form-group input {
                padding: 10px;
                font-size: 16px;
                min-height: auto;
            }
            .error {
                font-size: 0.9rem;
                margin-top: 5px;
                margin-bottom: 0;
            }
            .btn-login {
                padding: 14px 36px;
                font-size: 1rem;
                border-radius: 4px;
                width: auto;
            }
            .register-link {
                margin-top: 15px;
                font-size: inherit;
            }
        }
    </style>
@endsection

@section('content')
<div class="container">
    <div class="hero">
        <h1>Welcome Back</h1>
        <p>Please login to continue using CK Tools</p>

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="{{ url('/register') }}">Register here</a>
        </div>
    </div>
</div>
@endsection
