<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'CK AI Tools')</title>
    @yield('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root {
            --pink: #EC298B;
            --white: #black;
            --dark: #191919;
            --light-grey: #f5f5f5;
            --light-dark: #2a2a2a;
        }

        body {
            font-family: 'Poppins', system-ui, sans-serif;
            background-color: var(--white);
            color: var(--dark);
            margin: 0;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 240px;
            background-color: white;
            padding: 40px 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            margin-top: 100px;
        }

        .sidebar h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--pink);
            margin-bottom: 50px;
            text-align: center;
        }

        .sidebar a {
            display: block;
            color: var(--white);
            text-decoration: none;
            margin: 12px 0;
            font-size: 1rem;
            padding: 12px 18px;
            border-radius: 10px;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .sidebar a:hover {
            background-color: #F5F5F5;
            color: #EC298B;
        }

        .content {
            margin-left: 240px;
            padding: 50px 30px;
            background-color: var(--light-grey);
            min-height: 100vh;
        }

        /*
        .footer {
            text-align: center;
            margin-top: 60px;
            padding-top: 30px;
            font-size: 0.9rem;
            color: #666;
            border-top: 1px solid #ddd; */
        }

        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 20px;
                text-align: center;
                box-shadow: none;
            }

            .content {
                margin-left: 0;
                padding: 30px 15px;
            }

            .sidebar a {
                display: inline-block;
                margin: 8px 10px;
                padding: 10px 14px;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <div class="sidebar">
        <a href="#tool-leveler">Text Leveler</a>
        <a href="#tool-summary">Summarizer</a>
        <a href="#tool-understanding">Understanding</a>
        <a href="#tool-rewriter">Rewriter</a>
        <a href="#tool-proofreader">Proofreader</a>
        <a href="#tool-quizme">Quiz Me</a>
        @auth
            <form method="POST" action="{{ url('/logout') }}" style="margin-top: 30px;">
                @csrf
                <button type="submit" class="btn btn-link" style="color: #e91e63; text-decoration: none; font-weight: 600;">Logout</button>
            </form>
        @else
            <a href="{{ url('/login') }}" style="color: #e91e63; font-weight: 600;">Login</a>
            <a href="{{ url('/register') }}" style="color: #e91e63; font-weight: 600;">Register</a>
        @endauth
    </div>

    <div class="content">
        @yield('content')

        {{-- <div class="footer">
            &copy; <span id="year"></span> CK Children’s Publishing. All rights reserved.
        </div> --}}
    </div>

    <script>
        document.getElementById("year").textContent = new Date().getFullYear();
    </script>

</body>

</html>
