<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'CK AI Tools')</title>
    @yield('styles')

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

    <style>
        :root {
            --pink: #e91e63;
            --white: #ffffff;
            --dark: #191919;
            --light-grey: #f5f5f5;
        }

        a {
            text-decoration: none;
        }

        body {
            font-family: 'Poppins', system-ui, sans-serif;
            background-color: var(--white);
            color: var(--dark);
            margin: 0;
        }

        .sidebar {
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
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
            display: flex;
            /* Change from block to flex */
            align-items: center;
            justify-content: flex-start;
            color: var(--dark);
            text-decoration: none;
            margin: 12px 0;
            font-size: 1rem;
            padding: 12px 18px;
            border-radius: 10px;
            transition: background 0.3s ease, color 0.3s ease;
            white-space: nowrap;
            /* Prevent text from wrapping */
            overflow: hidden;
        }

        .sidebar.collapsed a {
            justify-content: center;
            padding-left: 12px;
            padding-right: 12px;
        }

        .sidebar a i {
            margin-right: 12px;
            font-size: 1.2rem;
            min-width: 24px;
            width: 24px;
            text-align: center;
            transition: margin 0.3s ease, font-size 0.3s ease;
        }

        .sidebar.collapsed a i {
            margin-right: 0;
        }

        .link-text {
            display: inline-block;
            opacity: 1;
            width: auto;
            max-width: 200px;
            transition: opacity 0.3s ease, max-width 0.3s ease;
            overflow: hidden;
        }

        .sidebar.collapsed .link-text {
            opacity: 0;
            max-width: 0;
        }

        .sidebar:not(.collapsed) a[data-bs-toggle="tooltip"] .link-text {
            pointer-events: none;
        }
        
        .content {
            flex: 1;
            padding: 50px 30px;
            background-color: var(--light-grey);
            transition: all 0.3s ease;
            overflow-x: hidden;
        }

        .layout {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .sidebar a.active-link {
            background-color: rgba(221, 175, 198, 0.15);

        }

        .sidebar a.active-link i {
            color: inherit !important;
        }

        .sidebar a:hover {
            background-color: rgba(221, 175, 198, 0.15);
        }

        #toggleSidebar {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            border: none;
            background: var(--white);
            color: #5a5959;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 1rem;
        }

        #toggleSidebar:hover {
            color: var(--pink);
        }
    </style>
</head>

<body>

    <!-- Toggle Sidebar Button -->
    <button id="toggleSidebar">☰</button>

    <!-- Sidebar -->
    <div class="layout">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <h2></h2>

            <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active-link' : '' }}" data-bs-toggle="tooltip"
                title="Home">
                <i class="bi bi-house-door"></i>
                <span class="link-text">Home</span>
            </a>
            <a href="{{ url('/tools') }}" class="{{ request()->is('tools*') ? 'active-link' : '' }}"
                data-bs-toggle="tooltip" title="Tools">
                <i class="bi bi-tools"></i>
                <span class="link-text">Tools</span>
            </a>
            <a href="{{ url('/about') }}" class="{{ request()->is('about') ? 'active-link' : '' }}"
                data-bs-toggle="tooltip" title="About">
                <i class="bi bi-people"></i>
                <span class="link-text">About</span>
            </a>

            @auth
                <form method="POST" action="{{ url('/logout') }}" style="margin-top: 30px;">
                    @csrf
                    <button type="submit" class="btn btn-link"
                        style="color: #e91e63; text-decoration: none; font-weight: 600;">
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
            tooltipTriggerlist.forEach(el => {
                new bootstrap.Tooltip(el, {});
            })
        </script>

</body>

</html>
