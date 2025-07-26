<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'CK Tools')</title>

    {{-- Common Styles --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    @yield('styles')
</head>
<body>
    {{-- Conditional Header --}}
    @auth
        @include('layouts.header')        {{-- This is the full navbar for logged-in users --}}
    @else
        @include('layouts.headerlogin')   {{-- This is for guests --}}
    @endauth

    <div class="main-content pt-5">
        @yield('content')
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
