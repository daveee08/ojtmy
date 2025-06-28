<!-- @extends('layouts.header') -->
@extends('layouts.navbar')

@section('title', 'Login - CK Tools')

@section('styles')
    <style>
        .container {
            margin-top: 170px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            flex-direction: column;
            padding: 0 20px;
            width: 100%;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            box-sizing: border-box;
        }

        .hero {
            background-color: #F5F5F5;
            border: 1px solid #F5F5F5;
            padding: 60px 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
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

        .error {
            color: #e91e63;
            font-size: 0.9rem;
            margin-top: 5px;
            text-align: left;
        }

        .btn-login {
            background-color: #e91e63;
            color: white;
            padding: 14px 36px;
            font-size: 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .btn-login:hover {
            background-color: #d81557;
        }

        .register-link {
            margin-top: 15px;
            color: #666;
        }

        .register-link a {
            color: #e91e63;
            text-decoration: none;
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
