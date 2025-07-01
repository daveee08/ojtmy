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
            height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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
            
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical; 
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
        .tool-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .tool-card-link:hover .tool-card {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
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
    <a href="http://192.168.50.144:8000/leveler" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Text Leveler</h5>
            <p>Adjust text difficulty to match your reading level and comprehension needs.</p>
        </div>
    </a>

    <a href="http://192.168.50.238:8000/" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Text Summarizer</h5>
            <p>Simplify long text into concise, easy-to-understand summaries.</p>
        </div>
    </a>

    <a href="https://tse4.mm.bing.net/th/id/OIP.eEW3EGenj4Djze7zv31PKAHaLG?rs=1&pid=ImgDetMain&o=7&rm=3" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Conceptual Understanding</h5>
            <p>Get writing feedback on grammar, structure, and clarity to build stronger writing skills.</p>
        </div>
    </a>

    <a href="https://tse4.mm.bing.net/th/id/OIP.eEW3EGenj4Djze7zv31PKAHaLG?rs=1&pid=ImgDetMain&o=7&rm=3" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Text Rewriter</h5>
            <p>Rephrase sentences to enhance expression and explore new ways of writing.</p>
        </div>
    </a>

    <a href="http://192.168.50.18:5001/proofreader" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Text Proofreader</h5>
            <p>Automatically catch and correct grammar, spelling, and punctuation errors.</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/generate-quiz" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Quiz Me!</h5>
            <p>Asks questions to test knowledge and boost learning in a fun way.</p>
        </div>
    </a>

    <a href="http://192.168.50.123:5001/scaffolder" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Text Scaffolder</h5>
            <p>Scaffold any text for better comprehension.</p>
        </div>
    </a>

    <a href="http://192.168.50.144:5001/informational" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Informational Text</h5>
            <p>Create original texts on any topic for your class.</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/step-tutor" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Step by Step</h5>
            <p>Get guided instructions for any task or subject.</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Five Questions</h5>
            <p>Use AI to challenge your thinking with 5 custom questions.</p>
        </div>
    </a>

    <a href="https://tse4.mm.bing.net/th/id/OIP.eEW3EGenj4Djze7zv31PKAHaLG?rs=1&pid=ImgDetMain&o=7&rm=3" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Real World Connections</h5>
            <p>Generate real world examples for what you're learning about!</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Study Habits</h5>
            <p>Get a plan and study tips to prepare for any test, assignment, or project.</p>
        </div>
    </a>

    <a href="https://tse4.mm.bing.net/th/id/OIP.eEW3EGenj4Djze7zv31PKAHaLG?rs=1&pid=ImgDetMain&o=7&rm=3" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Text Translator</h5>
            <p>Translate any text or uploaded document into any language.</p>
        </div>
    </a>

    <a href="https://tse4.mm.bing.net/th/id/OIP.eEW3EGenj4Djze7zv31PKAHaLG?rs=1&pid=ImgDetMain&o=7&rm=3" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Sentence Starters</h5>
            <p>Get ideas to help you get started with writing on any topic.</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Assignment Scaffolder</h5>
            <p>Helps break down assignments into easy, step-by-step parts for better planning and progress.</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Coach’s Sports Practice</h5>
            <p>Leads fun drills to build athletic skills and teamwork.</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Email Writer</h5>
            <p>Helps draft clear, professional emails for any occasion or purpose.</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Multiple Explanations Generator</h5>
            <p>Provides different ways to explain and supporting diverse learning styles.</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Teacher Jokes</h5>
            <p>Generates classroom-friendly jokes to lighten the mood and bring smiles to learning.</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Thank You Note</h5>
            <p>Crafts short, heartfelt messages to show appreciation for any occasion.</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Tongue Twisters</h5>
            <p>Creates fun and tricky phrases to challenge speech and boost pronunciation skills.</p>
        </div>
    </a>

    <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link">
        <div class="tool-card">
            <h5>Quote of the Day</h5>
            <p>Generate an inspiring or thoughtful quote to spark reflection and motivation.</p>
        </div>
    </a>

    
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
