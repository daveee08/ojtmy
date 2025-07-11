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

        <div class="search-wrapper mb-4 d-flex justify-content-center">
            <input type="text" id="toolSearch" placeholder="Search tools..." class="form-control w-100 w-md-50 rounded-pill py-2 px-4 shadow-sm">
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://192.168.50.144:8000/leveler" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/text leveler.png') }}" alt="Text Leveler Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Text Leveler</h5>
                                <p class="card-text text-secondary small">Adjust text difficulty to match your reading level and comprehension needs.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/summarize" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/summarizer.png') }}" alt="Text Summarizer Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Text Summarizer</h5>
                                <p class="card-text text-secondary small">Simplify long text into concise, easy-to-understand summaries.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="{{ url('/conceptual') }}" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/conceptual.png') }}" alt="Conceptual Understanding Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Conceptual Understanding</h5>
                                <p class="card-text text-secondary small">Get writing feedback on grammar, structure, and clarity to build stronger writing skills.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="{{ url('/text-rewriter') }}" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/text rewritter.png') }}" alt="Text Rewriter Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Text Rewriter</h5>
                                <p class="card-text text-secondary small">Rephrase sentences to enhance expression and explore new ways of writing.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://192.168.50.18:5001/proofreader" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/proofreader.png') }}" alt="Text Proofreader Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Text Proofreader</h5>
                                <p class="card-text text-secondary small">Automatically catch and correct grammar, spelling, and punctuation errors.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/generate-quiz" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/quizme.png') }}" alt="Quiz Me! Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Quiz Me!</h5>
                                <p class="card-text text-secondary small">Asks questions to test knowledge and boost learning in a fun way.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://192.168.50.123:5001/scaffolder" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/text scaff.png') }}" alt="Text Scaffolder Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Text Scaffolder</h5>
                                <p class="card-text text-secondary small">Scaffold any text for better comprehension.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://192.168.50.144:5001/informational" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/infotext.png') }}" alt="Informational Text Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Informational Text</h5>
                                <p class="card-text text-secondary small">Create original texts on any topic for your class.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/step-tutor" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/stepbystep.png') }}" alt="Step by Step Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Step by Step</h5>
                                <p class="card-text text-secondary small">Get guided instructions for any task or subject.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/five.png') }}" alt="Five Questions Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Five Questions</h5>
                                <p class="card-text text-secondary small">Use AI to challenge your thinking with 5 custom questions.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="{{ url('/real-world-connections') }}" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/realworld.png') }}" alt="Real World Connections Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Real World Connections</h5>
                                <p class="card-text text-secondary small">Generate real world examples for what you're learning about!</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/studyhabits" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/studyhabits.png') }}" alt="Study Habits Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Study Habits</h5>
                                <p class="card-text text-secondary small">Get a plan and study tips to prepare for any test, assignment, or project.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="{{ url('/translator') }}" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/text translator.png') }}" alt="Text Translator Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Text Translator</h5>
                                <p class="card-text text-secondary small">Translate any text or uploaded document into any language.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="{{ url('/sentence-starters') }}" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/sentencestarter.png') }}" alt="Sentence Starters Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Sentence Starters</h5>
                                <p class="card-text text-secondary small">Get ideas to help you get started with writing on any topic.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://192.168.50.123:5001/assignmentscaffolder" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/scaff.png') }}" alt="Assignment Scaffolder Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Assignment Scaffolder</h5>
                                <p class="card-text text-secondary small">Helps break down assignments into easy, step-by-step parts for better planning and progress.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/coach-sports-practice" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/coachsports.png') }}" alt="Coach’s Sports Practice Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Coach’s Sports Practice</h5>
                                <p class="card-text text-secondary small">Leads fun drills to build athletic skills and teamwork.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/email-writer" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/email.png') }}" alt="Email Writer Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Email Writer</h5>
                                <p class="card-text text-secondary small">Helps draft clear, professional emails for any occasion or purpose.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://192.168.50.123:5001/explanations" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/multiple.png') }}" alt="Multiple Explanations Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Multiple Explanations Generator</h5>
                                <p class="card-text text-secondary small">Provides different ways to explain and supporting diverse learning styles.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5004/generate-joke" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/teacherjoke.png') }}" alt="Teacher Jokes Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Teacher Jokes</h5>
                                <p class="card-text text-secondary small">Generates classroom-friendly jokes to lighten the mood and bring smiles to learning.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:8001/generate-thankyou" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/ty.png') }}" alt="Thank You Note Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Thank You Note</h5>
                                <p class="card-text text-secondary small">Crafts short, heartfelt messages to show appreciation for any occasion.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5002/generate-tongue-twister" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/tonguetwister.png') }}" alt="Tongue Twisters Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Tongue Twisters</h5>
                                <p class="card-text text-secondary small">Creates fun and tricky phrases to challenge speech and boost pronunciation skills.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/quote-of-the-day" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/quote.png') }}" alt="Quote of the Day Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Quote of the Day</h5>
                                <p class="card-text text-secondary small">Generate an inspiring or thoughtful quote to spark reflection and motivation.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://192.168.50.123:5001/responder" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/responder.png') }}" alt="Email ResponderIcon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Email Responder</h5>
                                <p class="card-text text-secondary small">Generate a professional response based on a received email and your intended message.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/makeitrelevant" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/relevant.png') }}" alt="Make It Relevant Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Make It Relevant!</h5>
                                <p class="card-text text-secondary small">Generate ideas that make a topic relevant to your class based on their interests and background.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://192.168.50.144:5001/chatwithdocs" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/docs.png') }}" alt="Chat with Docs Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Chat with Docs</h5>
                                <p class="card-text text-secondary small">Upload a document and have an AI-powered chat with it.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/mathreview" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/math.png') }}" alt="Math Review Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Math Review</h5>
                                <p class="card-text text-secondary small">Generate review questions to improve your math skills and understanding.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/character-chatbot" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/chatbot.png') }}" alt="Chatbot Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Character Chatbot</h5>
                                <p class="card-text text-secondary small">Chat with any historic figure, author, or recognizable character from a story.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/idea-generator" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/idea.png') }}" alt="Idea Generator Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Idea Generator</h5>
                                <p class="card-text text-secondary small">Use AI as a thought partner to generate ideas on any topic.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/content-creator" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/content.png') }}" alt="Content Creator Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Content Creator</h5>
                                <p class="card-text text-secondary small">Generate academic content to help you learn.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex align-items-stretch">
                <a href="http://127.0.0.1:5001/social-stories" target="_blank" class="tool-card-link w-100">
                    <div class="card shadow-sm w-100 h-100 border-0 rounded-4 p-3 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="tool-card-icon me-3">
                                <img src="{{ asset('icons/stories.png') }}" alt="Social Stories Icon" class="img-fluid">
                            </div>
                            <div class="tool-card-text flex-grow-1">
                                <h5 class="card-title fw-bold text-primary mb-1">Social Stories</h5>
                                <p class="card-text text-secondary small">Generate a social story about a particular event to help a student understand what to expect in that situation.</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
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
