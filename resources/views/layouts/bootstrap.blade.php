<!DOCTYPE html>
<html lang="en" data-bs-theme="light"> <!-- Add data-bs-theme -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Default Title')</title>

    {{-- Bootstrap CSS (Updated to 5.3.7) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Global Styles --}}
    <style>
        :root {
            --pink: #e91e63;
            --white: #ffffff;
            --dark: #191919;
            --light-grey: #f5f5f5;
        }

        [data-bs-theme="dark"] {
            --pink: #d61f5c; /* Lighter pink for dark mode */
            --white: #1e1e1e; /* Dark background */
            --dark: #e0e0e0; /* Light text for dark mode */
            --light-grey: #2c2c2c; /* Darker grey for backgrounds */
        }

        body {
            background: var(--white);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        body.sidebar-collapsed .container {
            margin-left: 70px;
        }

        body:not(.sidebar-collapsed) .container {
            margin-left: 240px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            font-size: 1rem;
            border: 1.5px solid var(--light-grey);
            transition: border-color 0.25s ease;
            box-shadow: none;
            padding: 0.6rem 1rem;
            color: var(--dark);
            background-color: var(--white);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--pink);
            box-shadow: 0 0 8px rgba(230, 57, 70, 0.3);
            outline: none;
        }

        .btn-primary {
            background-color: var(--pink);
            color: var(--white);
            border: none;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 0.65rem 2.5rem;
            border-radius: 10px;
            cursor: pointer;
            letter-spacing: 0.08em;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #d62839;
            outline: none;
        }

        .spinner-border.text-pink {
            color: var(--pink);
        }
    </style>

    @yield('styles')
</head>
<body>
    <div id="loading-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color: rgba(255,255,255,0.8); z-index:9999; align-items:center; justify-content:center; flex-direction: column;"
        data-bs-theme="light"> <!-- Ensure overlay respects theme -->
        <div class="spinner-border text-pink" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-center fw-bold" style="color: var(--pink);">Generating your response...</p>
    </div>

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous">
    </script>

    @yield('scripts')
</body>
</html>