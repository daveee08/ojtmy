@extends('layouts.header')
@extends('layouts.navbar')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@section('title', 'Home - CK AI Tools')

@section('styles')
    <style>
        :root {
            --pink: #e91e63;
            --white: #ffffff;
            --dark: #333333;
            --light-grey: #f5f5f5;
        }

        body {
            font-family: system-ui, sans-serif;
            background-color: var(--white);
            color: var(--dark);
        }

        .content {
            margin-left: 240px;
            padding: 40px;

        }

        .centered-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .about-card p {
            text-align: justify;
            font-family: 'Segoe UI', sans-serif;
            color: #666;
        }

        .hero {
            padding: 50px 30px;
            border-radius: 12px;
            margin-top: 60px;
            margin-bottom: 50px;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .hero h1 {
            font-family: 'Segoe UI', sans-serif;
            font-size: 3rem;
            color: #e91e63;
            font-weight: 700;
        }

        .hero p {
            font-family: 'Segoe UI', sans-serif;
            color: #666;
            max-width: 600px;
            margin-top: 15px;
            text-align: justify;
        }

        .footer {
            text-align: center;
            margin-top: 60px;
            padding-top: 30px;
            font-size: 0.9rem;
            color: #888;
            border-top: 1px solid #eee;
        }
    </style>
@endsection

@section('content')
    <div class="centered-content">
        <div class="hero">
            <h1 style="color: #e91e63;">About Us</h1>
        </div>

        <div class="about-card">
            <p>
                CK AI Tools is a growing collection of intelligent, web-based educational resources designed to support
                reading, writing, and comprehension skills for students of all ages.
                Each tool is crafted to make learning simpler, clearer, and more engaging.
            </p>
            <p>
                Whether it’s simplifying complex texts, summarizing key ideas, checking grammar, or helping young writers
                understand what they read,
                CK AI Tools brings the power of AI into the classroom and at home — in ways that are friendly, helpful, and
                accessible..
            </p>
            <p>
                We believe learning should be fun, inclusive, and supported by the best that technology can offer.
                That’s why CK AI Tools continues to evolve — to meet the needs of curious minds and the educators who guide
                them.
            </p>
        </div>

        <div class="footer">
            &copy; <span id="year"></span> CK Children's Publishing. All rights reserved.
        </div>
    </div>

    <script>
        document.getElementById("year").textContent = new Date().getFullYear();
    </script>
@endsection
