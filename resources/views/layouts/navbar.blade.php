<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'CK AI Tools')</title>
    @yield('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --pink: #EC298B;
            --white: #ffffff;
            --dark: #191919;
            --light-grey: #f5f5f5;
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
            background-color: var(--white);
            padding: 40px 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            transition: width 0.3s ease;
        }

        .sidebar.collapsed {
            width: 70px;
            padding: 40px 10px;
        }

        .sidebar h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--pink);
            margin-bottom: 50px;
            text-align: center;
            transition: opacity 0.2s ease;
        }

        .sidebar.collapsed h2 {
            opacity: 0;
        }

        .sidebar a {
            display: block;
            color: var(--dark);
            text-decoration: none;
            margin: 12px 0;
            font-size: 1rem;
            padding: 12px 18px;
            border-radius: 10px;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .sidebar a:hover {
            background-color: var(--light-grey);
            color: var(--pink);
        }

        .sidebar.collapsed a {
            text-align: center;
            padding: 10px 5px;
            font-size: 0.85rem;
            overflow: hidden;
        }

        .sidebar a i{
            margin-right: 10px;
            font-size: 1.2rem;
            transition: margin 0.3s ease, font-size 0.3s ease
        }

        .sidebar.collapsed a i {
            margin-right: 0;
            font-size: 1rem;
        }   

        .link-text {
            display: inline;
            transition: opacity 0.3s ease, width 0.3s ease;
            opacity: 1;
            width: auto;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .link-text {
            opacity: 0;
            width: 0;
        }

        .sidebar:not(.collapsed) a[data-bs-toggle="tooltip"] .link-text {
            pointer-events: none;
        }

        .content {
            margin-left: 240px;
            padding: 50px 30px;
            background-color: var(--light-grey);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .content.expanded {
            margin-left: 70px;
        }

        #toggleSidebar {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            border: none;
            background: var(--white);
            color: rgb(90, 89, 89);
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 1rem;
        }
    </style>
</head>

<body>

    <!-- Toggle Sidebar Button -->
    <button id="toggleSidebar">☰</button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <h2>CK AI Tools</h2>

        <a href="{{url('/tools')}}" data-bs-toggle="tooltip" title="Tools">
            <i class="bi bi-tools"></i>
            <span class="link-text">Tools</span>
        </a>
        <a href="{{url('/')}}" data-bs-toggle="tooltip" title="Home">
            <i class="bi bi-house-door"></i>
            <span class="link-text">Home</span>
        </a>
        <a href="#tool-About" data-bs-toggle="tooltip" title="About">
            <i class="bi bi-people"></i>
            <span class="link-text">About</span>
        </a>
        <a href="#tool-Contact" data-bs-toggle="tooltip" title="Contact">
            <i class="bi bi-envelope"></i>
            <span class="link-text">Contact</span>
        
        @auth
            <form method="POST" action="{{ url('/logout') }}" style="margin-top: 30px;">
                @csrf
                <button type="submit" class="btn btn-link" style="color: #e91e63; text-decoration: none; font-weight: 600;">
                    <span class="link-text">Logout</span>
                </button>
            </form>
        @else
            <a href="{{ url('/login') }}" style="color: #e91e63; font-weight: 600;">
                <span class="link-text">Login</span>
            </a>
            <a href="{{ url('/register') }}" style="color: #e91e63; font-weight: 600;">
                <span class="link-text">Register</span>
            </a>
        @endauth
    </div>

    <!-- Content -->
    <div class="content" id="mainContent">
        @yield('content')
    </div>

    <!-- Scripts -->
    <script>
        const toggleBtn = document.getElementById("toggleSidebar");
        const sidebar = document.getElementById("sidebar");
        const content = document.getElementById("mainContent");

        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            content.classList.toggle("expanded");
        });

        const tooltipTriggerlist = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerlist.forEach(el =>{
            new bootstrap.Tooltip(el, {
            });
        })
    </script>

</body>

</html>
