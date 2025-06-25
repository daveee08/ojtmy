<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

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
        margin: 15px;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light fixed-top shadow-sm bg-white">
    <div class="container-fluid px-0">
        <a class="navbar-brand ms-4" href="{{ url('/') }}">
            <img src="https://ckgroup.ph/wp-content/uploads/2020/05/CK-Logo-Rectangle-300x95.png" alt="CK Logo"
                height="62">
        </a>
        <button class="navbar-toggler me-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end me-3" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('home') ? 'active' : '' }}" href="{{ url('/') }}">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/tools') }}">Tools</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('about') ? 'active' : '' }}"
                        href="{{ url('/about') }}">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('products') ? 'active' : '' }}"
                        href="{{ url('/products') }}">Products</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
