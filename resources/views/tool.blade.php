@extends('layouts.header')
@extends('layouts.navbar')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

@section('title', 'Home - CK AI Tools')

@section('styles')
    <style>
        .container {
            margin-top: 100px;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 20px;
        }

        .hero {
            background-color: #F5F5F5;
            border: 1px solid #F5F5F5;
            padding: 50px;
            border-radius: 12px;
            margin-bottom: 40px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            color: #e91e63;
            font-weight: 700;
        }

        .hero p {
            font-size: 1rem;
            color: #555;
            max-width: 600px;
            margin: 15px auto 0;
        }

        .search-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .search-wrapper input {
            width: 100%;
            max-width: 400px;
            padding: 10px 16px 10px 40px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 30px;
            outline: none;
            background: #fff url('data:image/svg+xml;utf8,<svg fill="gray" height="16" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C8.01 14 6 11.99 6 9.5S8.01 5 10.5 5 15 7.01 15 9.5 12.99 14 10.5 14z"/></svg>') no-repeat 12px center;
            background-size: 18px 18px;
            transition: 0.3s;
        }

        .search-wrapper input:focus {
            border-color: #e91e63;
            box-shadow: 0 0 0 0.1rem rgba(234, 114, 114, 0.1);
        }

        .tool-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .tool-card-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            height: 100%;
        }

        .tool-card {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: stretch;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            width: 100%;
            height: 120px;
        }

        .tool-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            border-color: #e91e63;
        }

        .tool-card h5 {
            font-size: 1.1rem;
            color: #333333;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 5px;
        }

        .tool-card h5 a {
            color: inherit;
            text-decoration: none;
            pointer-events: none;
            cursor: default;
        }

        .tool-card p {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .tool-card-content {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            flex-grow: 1;
        }

        .tool-card-icon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tool-card-icon img {
            width: 120%;
            height: 120%;
            object-fit: contain;
        }

        .tool-card-favorite {
            position: absolute;
            top: 15px;
            right: 15px;
            color: #ccc;
            font-size: 1.2rem;
            cursor: pointer;
            transition: color 0.02s ease;
        }

        .tool-card-link:hover .tool-card {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }

        @media (max-width: 992px) {
            .tool-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .tool-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

@endsection


@section('content')
    <div class="container-fluid py-4 px-2 px-md-4">
        <div class="text-center mb-4">
            <h1 class="display-5 fw-bold text-primary">Welcome to CK AI Tools</h1>
            <p class="lead text-secondary">
                A supportive suite of tools to help young learners grow their reading and writing skills with clarity and confidence.
            </p>
        </div>
        <div class="row g-4 justify-content-center">
            @foreach ($tools as $tool)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="mb-3">
                            <h5 class="card-title fw-bold text-primary mb-2" style="font-size:1.2rem;">{{ $tool['name'] }}</h5>
                            <p class="card-text text-secondary small">{{ $tool['description'] }}</p>
                        </div>
                        <a href="{{ $tool['url'] }}" class="btn btn-lg btn-primary w-100 mt-auto py-2 px-3 rounded-pill shadow-sm focus-ring" style="font-size:1.1rem;">Open Tool</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <style>
        .focus-ring:focus {
            outline: 3px solid #0d6efd;
            outline-offset: 2px;
        }
        @media (max-width: 576px) {
            .card-title { font-size: 1rem; }
            .card-text { font-size: 0.95rem; }
            .btn-lg { font-size: 1rem; padding: 0.75rem 1rem; }
        }
    </style>

    <script>
        document.getElementById('toolSearch').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const cards = document.querySelectorAll('.tool-card-link');
            cards.forEach(link => {
                const text = link.innerText.toLowerCase();
                link.style.display = text.includes(query) ? 'block' : 'none';
            });
        });
    </script>
@endsection
