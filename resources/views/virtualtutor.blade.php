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
    <div class="container">
        <div class="hero">
            <h1>Welcome to CK Virtual Tutor</h1>
            <p>AI-powered tutor will utilize a local knowledge base sourced from CK Grade 7 books in Science, English, and Math.</p>
        </div>

        <div class="search-wrapper">
            <input type="text" id="toolSearch" placeholder="Search tools...">
        </div>

        <div class="tool-grid">
            <div class="tool-grid">

                <a href="http://127.0.0.1:5001/summarize" target="_blank" class="tool-card-link">
                    <div class="tool-card">
                        <div class="tool-card-content">
                            <div class="tool-card-icon">
                                <img src="{{ asset('icons/english.png') }}" alt="English Icon">
                                {{-- Image src left blank for you to fill --}}
                            </div>
                            <div class="tool-card-text">
                                <h5>English</h5>
                                <p>Improve reading, writing, and communication.</p>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="http://192.168.50.144:8000/leveler" target="_blank" class="tool-card-link">
                    <div class="tool-card">
                        <div class="tool-card-content">
                            <div class="tool-card-icon">
                                <img src="{{ asset('icons/science.png') }}" alt="Science Icon">
                                {{-- Image src left blank for you to fill --}}
                            </div>
                            <div class="tool-card-text">
                                <h5>Science</h5>
                                <p>Study how the world works through nature, energy, and life.</p>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="https://tse4.mm.bing.net/th/id/OIP.eEW3EGenj4Djze7zv31PKAHaLG?rs=1&pid=ImgDetMain&o=7&rm=3"
                    target="_blank" class="tool-card-link">
                    <div class="tool-card">
                        <div class="tool-card-content">
                            <div class="tool-card-icon">
                                <img src="{{ asset('icons/mathematics.png') }}" alt="Mathematics Icon">
                                {{-- Image src left blank for you to fill --}}
                            </div>
                            <div class="tool-card-text">
                                <h5>Math</h5>
                                <p>Learn numbers, patterns, and problem-solving skills.</p>
                            </div>
                        </div>
                    </div>
                </a>

            </div>
        </div>
    </div>
    </div>

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
