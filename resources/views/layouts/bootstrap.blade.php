<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CK Virtual Tutor')</title>

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Global Styles --}}
    <style>
        body {
            background: white;
            font-family: 'Inter', sans-serif;
            color: #2c3e50;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            font-size: 1rem;
            border: 1.5px solid #d1d9e6;
            transition: border-color 0.25s ease;
            box-shadow: none;
            padding: 0.6rem 1rem;
            color: #34495e;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #e91e63;
            box-shadow: 0 0 8px rgba(230, 57, 70, 0.3);
            outline: none;
        }

        .btn-primary {
            background-color: #e91e63;
            color: white;
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
            color: #e91e63;
        }
    </style>

    @yield('styles')
</head>

<body class="@yield('body-class')">

    {{-- Loading Overlay --}}
    <div id="loading-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(255,255,255,0.8); z-index:9999; align-items:center; justify-content:center; flex-direction: column;">
        <div class="spinner-border text-pink" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-center fw-bold" style="color:#EC298B;">Generating your response...</p>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>

    @yield('scripts')
</body>

</html>
