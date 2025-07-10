@extends('layouts.header')
@extends('layouts.navbar')


@section('title', 'Home - CK Tools')

@section('styles')
    <style>
        body {
            background: linear-gradient(to bottom, #ffffff 0%, #fef4f7 100%);
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        .hero {
            margin-top: 170px;
            text-align: center;
            padding: 0 20px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeInUp 0.6s ease-out;
        }

        .hero h1 {
            font-size: 3rem;
            color: #e91e63;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.25rem;
            color: #555;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .btn-start {
            background-color: #e91e63;
            color: #fff;
            padding: 12px 34px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.5s ease;
        }

        .btn-start:hover {
            background-color: #c2185b;
            transform: scale(1.05);
            color: white;
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 576px) {
            .hero h1 {
                font-size: 2.2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .btn-start {
                padding: 10px 24px;
                font-size: 0.95rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="hero">
        <h1>Welcome to CK AI Tools</h1>
        <p>Your supportive suite of AI-powered tools to help young learners build confidence in reading and writing.</p>
        <a href="/tools" class="btn-start">Get Started</a>
    </div>
@endsection
