@extends('layouts.header')
@extends('layouts.navbar')

@section('title', 'Home - CK Tools')

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
        }

        .hero h1 {
            font-size: 2.5rem;
            color: #e91e63;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 35px;
            line-height: 1.6;
        }

        .btn-start,
        .btn-login {
            background-color: #e91e63;
            color: white;
            padding: 14px 36px;
            font-size: 1rem;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }

        .btn-start:hover,
        .btn-login:hover {
            background-color: #555;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }

        .note {
            margin-top: 10px;
            font-size: 0.9rem;
            text-align: center;
        }

        .note a {
            color: #e91e63;
            text-decoration: none;
        }

        .note a:hover {
            text-decoration: underline;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="hero">
            <h1>Welcome to CK Tools</h1>
            <p>Your supportive suite of AI-powered tools to help young learners build confidence in reading and writing.</p>
            <button class="btn-login" onclick="document.getElementById('loginModal').style.display='block'">Log In</button>
        </div>
    </div>

    <!-- Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('loginModal').style.display='none'">&times;</span>
            <h2 style="margin-bottom: 20px; color: #e91e63;">Log In</h2>
            <form action="/login" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>

                <button type="submit" class="btn-login" style="width: 100%;">Log In</button>

                <div class="note">
                    Don't have an account? <a href="/register">Register here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('loginModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
@endsection
