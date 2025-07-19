<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

    a {
        text-decoration: none;
    }

    .navbar-nav .nav-link {
        color: black !important;
        font-family: "Poppins", Sans-serif;
        font-size: 15px;
        font-weight: 500;
        transition: color 0.2s ease-in-out;
        margin: 0 10px;
    }

    .navbar-nav .nav-link:hover {
        color: #EC298B !important;
    }

    .navbar-brand img {
        margin: 4px 50px;
        height: 44px;
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
    }
</style>

<nav class="navbar navbar-light fixed-top shadow-sm bg-white">
    <div class="container-fluid px-0">
        @auth
            <a class="navbar-brand ms-4" href="{{ url('/tools') }}">
                <img src="https://ckgroup.ph/wp-content/uploads/2020/05/CK-Logo-Rectangle-300x95.png" alt="CK Logo" height="44">
            </a>
        @else
            <span class="navbar-brand ms-4" style="cursor: default; pointer-events: none;">
                <img src="https://ckgroup.ph/wp-content/uploads/2020/05/CK-Logo-Rectangle-300x95.png" alt="CK Logo" height="44">
            </span>
        @endauth
        <ul class="navbar-nav flex-row align-items-center ms-auto me-3">
            {{-- <li class="nav-item">
                <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}"></a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/tools') }}"></a>
            </li> --}}
            @auth
                <li class="nav-item">
                    <form method="POST" action="{{ url('/logout') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="nav-link btn btn-link"
                            style="display:inline; color:#e91e63; font-weight:600; padding:0; background:none; border:none;">Logout</button>
                    </form>
                </li>
            @endauth
        </ul>
    </div>
</nav>
