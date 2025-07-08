@extends('layouts.header')
@extends('layouts.navbar')

@section('title', 'Register - CK Tools')

@section('styles')
    <style>
        .container {
            margin-top: 100px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            flex-direction: column;
            padding: 0 1rem;
            width: 100%;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            box-sizing: border-box;
        }

        .hero {
            background-color: #F5F5F5;
            border: 1px solid #F5F5F5;
            padding: 1.5rem;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            color: #333;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            min-height: 48px;
        }

        .error {
            color: #e91e63;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            margin-bottom: 1rem;
            text-align: left;
        }

        .btn-register {
            background-color: #e91e63;
            color: white;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
        }

        .btn-register:hover {
            background-color: #d81557;
        }

        .login-link {
            margin-top: 1rem;
            color: #666;
            font-size: 0.9rem;
        }

        .login-link a {
            color: #e91e63;
            text-decoration: none;
        }

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
            .btn-register {
                padding: 14px 36px;
                font-size: 1rem;
                border-radius: 4px;
                width: auto;
            }
            .login-link {
                margin-top: 15px;
                font-size: inherit;
            }
        }
    </style>
@endsection

@section('content')
<div class="container">
    <div class="hero">
        <h1>Create Account</h1>
        <p>Join CK Tools today</p>

        @if ($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ url('/register') }}">
            @csrf
            <div class="form-group">
                <label for="username">Name</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            </div>

            <div class="form-group">
                <label for="name">Grade Level</label>
                <input type="text" id="grade_level" name="grade_level" value="{{ old('grade_level') }}" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn-register">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="{{ url('/login') }}">Login here</a>
        </div>
    </div>
</div>
@endsection
