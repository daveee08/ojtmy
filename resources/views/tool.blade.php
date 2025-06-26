@extends('layouts.header')
@extends('layouts.navbar')

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
            font-size: 2rem;
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
            border-color: #555;
            box-shadow: 0 0 0 0.1rem rgba(0, 0, 0, 0.25);
        }

        .tool-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .tool-card {
            background-color: #ffffff;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .tool-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }

        .tool-card h5 {
            font-size: 1rem;
            color: #000000;
            font-weight: normal;
            margin-bottom: 6px;
        }

        .tool-card h5 a {
            color: inherit;
            text-decoration: none;
            pointer-events: none;
            cursor: default;
        }

        .tool-card p {
            font-size: 0.9rem;
            color: #555;
            margin: 0;
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
            <h1>Welcome to CK AI Tools</h1>
            <p>A supportive suite of tools to help young learners grow their reading and writing skills with clarity and
                confidence.</p>
        </div>

        <div class="search-wrapper">
            <input type="text" id="toolSearch" placeholder="Search tools...">
        </div>
        <div class="tool-grid">
            <div class="tool-card">
                <h5><a href="http://192.168.50.144:8000/leveler" target="_blank">Text Leveler</a></h5>
                <p>Adjust text difficulty to match your reading level and comprehension needs.</p>
            </div>

            <div class="tool-card">
                <h5><a href="http://192.168.50.238:8000/" target="_blank">Text Summarizer</a></h5>
                <p>Simplify long text into concise, easy-to-understand summaries.</p>
            </div>

            <div class="tool-card">
                <h5><a href="http://192.168.50.127:8000/tutor" target="_blank">Conceptual Understanding</a></h5>
                <p>Get writing feedback on grammar, structure, and clarity to build stronger writing skills.</p>
            </div>

            <div class="tool-card">
                <h5><a href="http://192.168.50.123:8000/rewriter" target="_blank">Text Rewriter</a></h5>
                <p>Rephrase sentences to enhance expression and explore new ways of writing.</p>
            </div>

            <div class="tool-card">
                <h5><a href="http://192.168.50.18:5001/proofreader" target="_blank">Text Proofreader</a></h5>
                <p>Automatically catch and correct grammar, spelling, and punctuation errors.</p>
            </div>

            <div class="tool-card">
                <h5><a href="http://127.0.0.1:5001/generate-quiz" target="_blank">Quiz Me!</a></h5>
                <p>Quiz yourself on any topic or test type.</p>
            </div>

            <div class="tool-card">
                <h5><a href="http://192.168.50.123:5001/scaffolder" target="_blank">Text Scaffolder</a></h5>
                <p>Scaffold any text for better comprehension.</p>
            </div>

            <div class="tool-card">
                <h5><a href="http://192.168.50.144:5001/informational" target="_blank">Informational Text</a></h5>
                <p>Create original texts on any topic for your class.</p>
            </div>

            <div class="tool-card">
                <h5><a href="http://127.0.0.1:5001/step-tutor" target="_blank">Step by Step</a></h5>
                <p>Get guided instructions for any task or subject.</p>
            </div>

            <div class="tool-card">
                <h5><a href="http://127.0.0.1:5001/5questions" target="_blank">Five Questions</a></h5>
                <p>Use AI to challenge your thinking with 5 custom questions.</p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('toolSearch').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const cards = document.querySelectorAll('.tool-card');
            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(query) ? 'block' : 'none';
            });
        });
    </script>
@endsection
