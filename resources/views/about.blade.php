@extends('layouts.header')
@extends('layouts.navbar')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@section('title', 'About')

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
                CK Children's Publishing is a pioneering digital learning solutions provider dedicated to empowering the next generation through advanced Information and Communication Technology (ICT) education. 
                With over two decades of specialized expertise in ICT and digital marketing, we partner with schools across the Philippines to integrate innovative platforms and foster essential digital literacy.
            </p>
            <p>
                We offer a comprehensive suite of educational resources, including our popular "COMPUKIDS" ICT educational book series, cutting-edge robotics courseware, and robust school management systems. 
                Committed to the principles of modern learning, our mission is to provide exceptional, relevant solutions and unwavering support, ensuring that every student is equipped with the skills needed to thrive in a technology-driven future.
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
