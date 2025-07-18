<!-- resources/views/layouts/headerlogin.blade.php -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

    a {
        text-decoration: none;
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
    }
</style>

<nav class="navbar navbar-light fixed-top shadow-sm bg-white">
    <div class="container-fluid px-0">
        <a class="navbar-brand ms-4" href="{{ url('/') }}">
            <img src="https://ckgroup.ph/wp-content/uploads/2020/05/CK-Logo-Rectangle-300x95.png" alt="CK Logo" height="44">
        </a>
    </div>
</nav>