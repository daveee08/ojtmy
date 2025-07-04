<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AI Tutor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#e91e63"/>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Mobile-first padding for main content */
        main.py-4 {
            padding: 1rem !important; /* Adjusted for mobile */
        }

        /* Revert padding for larger screens */
        @media (min-width: 768px) {
            main.py-4 {
                padding: 1.5rem !important; /* Revert to a more standard desktop padding */
            }
        }
    </style>
</head>
<body>
    <main class="py-4">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
