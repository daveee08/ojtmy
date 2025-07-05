@extends('layouts.header')
@extends('layouts.navbar')

@section('title', 'Home - CK Tools')

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
            max-width: 800px;
            width: 100%;
        }

        .hero h1 {
            font-size: 2rem;
            color: #e91e63;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1rem;
            color: #555;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .btn-start {
            background-color: #555;
            color: white;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s ease;
            width: 100%;
            max-width: 200px;
        }

        .btn-start:hover {
            background-color: #e91e63;
            color: #ffffff;
        }

        @media (min-width: 768px) {
            .container {
                margin-top: 170px;
                padding: 0 20px;
            }
            .hero {
                padding: 60px 40px;
            }
            .hero h1 {
                font-size: 2.5rem;
                margin-bottom: 20px;
            }
            .hero p {
                font-size: 1.1rem;
                margin-bottom: 35px;
            }
            .btn-start {
                padding: 10px 26px;
                font-size: 0.9rem;
                border-radius: 6px;
                width: auto;
                max-width: none;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="hero">
            <h1>Welcome to CK Tools</h1>
            <p>Your supportive suite of AI-powered tools to help young learners build confidence in reading and writing.</p>
            <a href="/tools" class="btn-start">Get Started</a>
        </div>
    </div>
@endsection

{{-- original --}}
