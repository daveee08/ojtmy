@extends('layouts.header')
@extends('layouts.navbar')

@section('title', 'Welcome')

@section('styles')
<style>
    body {
        background: linear-gradient(to bottom right, #fff5fb, #fde8f0);
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
    }

    .landing-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 80vh;
        text-align: center;
        flex-direction: column;
    }

    h1 {
        font-size: 3rem;
        color: #e91e63;
        margin-bottom: 20px;
    }

    p {
        font-size: 1.2rem;
        color: #555;
        max-width: 600px;
        margin-bottom: 30px;
    }

    .btn-primary {
        background-color: #e91e63;
        color: white;
        padding: 12px 30px;
        font-size: 1rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
    }

    .btn-primary:hover {
        background-color: #d81557;
    }

    .confetti {
        font-size: 2rem;
        margin-bottom: 20px;
        animation: pop 1s ease-in-out infinite;
    }

    @keyframes pop {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }
</style>
@endsection

@section('content')
<div class="landing-wrapper">
    <div class="confetti">🎉</div>
    <h1>Welcome to CK AI Tools!</h1>
    <p>You're all set! Explore tools designed to help young learners thrive in reading, writing, and more.</p>
    <a href="{{ url('/dashboard') }}" class="btn-primary">Go to Dashboard</a>
</div>
@endsection
