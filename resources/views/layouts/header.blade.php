<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

    /* :root {
            --pink: #e91e63;
            --white: #ffffff;
            --dark: #191919;
            --light-grey: #f5f5f5;
        } */

        [data-bs-theme="dark"] {
            --pink: #d61f5c; /* Lighter pink for dark mode */
            --white: #1e1e1e; /* Dark background */
            --dark: #e0e0e0; /* Light text for dark mode */
            --light-grey: #1e1e1e; /* Darker grey for backgrounds */
            --filter-border:#5f5f5f;
            --filter-background:#1e1e1e;
            --tooltip-color:#ff0a4b;
        }


    a {
        text-decoration: none;
    }

    .navbar {
        background-color: var(--white);
    }

    .navbar-nav .nav-link {
        color: var(--dark) !important;
        font-family: "Poppins", Sans-serif;
        font-size: 15px;
        font-weight: 500;
        transition: color 0.2s ease-in-out;
        margin: 0 10px;
    }

    .navbar-nav .nav-link:hover {
        color: var(--pink) !important;
    }

    .navbar-brand img {
        margin: 4px 50px;
        height: 44px;
    }

    .theme-toggle {
        color: var(--dark);
        font-size: 1.2rem;
        padding: 0 10px;
        cursor: pointer;
        transition: color 0.2s ease-in-out;
    }

    .theme-toggle:hover {
        color: var(--pink);
    }

    @media (max-width: 600px) {
        .navbar-brand img {
            margin: 4px 10px;
            height: 36px;
        }

        .navbar-nav .nav-link {
            margin: 0 4px;
            font-size: 13px;
        }

        .theme-toggle {
            font-size: 1rem;
            padding: 0 5px;
        }
    }
</style>

<nav class="navbar fixed-top shadow-sm" data-bs-theme="light">
    <div class="container-fluid px-0">
        @auth
            <a class="navbar-brand ms-4" href="{{ url('/tools') }}">
                <img id="logoImg" src="https://ckgroup.ph/wp-content/uploads/2020/05/CK-Logo-Rectangle-300x95.png" alt="CK Logo" height="44">
            </a>
        @else
            <span class="navbar-brand ms-4" style="cursor: default; pointer-events: none;">
                <img id="logoImg" src="https://ckgroup.ph/wp-content/uploads/2020/05/CK-Logo-Rectangle-300x95.png" alt="CK Logo" height="44">
            </span>
        @endauth
        <ul class="navbar-nav flex-row align-items-center ms-auto me-3">
            @auth
                <li class="nav-item">
                    <a id="themeToggle" class="theme-toggle" data-bs-toggle="tooltip" title="">
                        <i class="bi bi-sun-fill"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <form method="POST" action="{{ url('/logout') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="nav-link btn btn-link"
                            style="display:inline; color: var(--pink); font-weight:600; padding:0; background:none; border:none;">Logout</button>
                    </form>
                </li>
            @endauth
        </ul>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeToggle = document.getElementById('themeToggle');
        const htmlElement = document.documentElement;
        const themeIcon = themeToggle.querySelector('i');
        const logoImg = document.getElementById('logoImg');

        // Define logo paths
        const darkLogo = 'https://ckgroup.ph/wp-content/uploads/2020/05/CK-Logo-Rectangle-300x95.png';
        const lightLogo = '/icons/CK light image.png';

        // Load theme from localStorage or system preference and set initial logo
        const savedTheme = localStorage.getItem('theme') || 
            (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        htmlElement.setAttribute('data-bs-theme', savedTheme);
        themeIcon.className = savedTheme === 'dark' ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
        if (logoImg) {
            logoImg.src = savedTheme === 'dark' ? lightLogo : darkLogo;
        }

        // Toggle theme, icon, and logo
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlElement.setAttribute('data-bs-theme', newTheme);
            themeIcon.className = newTheme === 'dark' ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
            if (logoImg) {
                logoImg.src = newTheme === 'dark' ? lightLogo : darkLogo;
            }
            localStorage.setItem('theme', newTheme);
        });

        // Initialize Bootstrap tooltip for theme toggle
        new bootstrap.Tooltip(themeToggle);
    });
</script>