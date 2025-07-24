
@extends('layouts.header')
@extends('layouts.navbar')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

@section('title', 'CK Tools ')

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
            /* background-color: var(--light-grey);
            border: 1px solid var(--light-grey); */
            padding: 50px;
            border-radius: 12px;
            margin-bottom: 40px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            color: var(--pink);
            font-weight: 700;
        }

        /* .hero p {
            font-size: 1rem;
            color: var(--dark);
            max-width: 600px;
            margin: 15px auto 0;
        } */

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
            
            border: 1.5px solid var(--filter-border);
            border-radius: 30px;
            outline: none;
            background: var(--white) url('data:image/svg+xml;utf8,<svg fill="gray" height="16" viewBox="0 0 24 24" width="16" xmlns="http://www.w0.org/2000/svg"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C8.01 14 6 11.99 6 9.5S8.01 5 10.5 5 15 7.01 15 9.5 12.99 14 10.5 14z"/></svg>') no-repeat 12px center;
            background-size: 18px 18px;
            transition: 0.3s;
            color: var(--dark);
        }

        .search-wrapper input:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 0.1rem rgba(233, 30, 99, 0.1);
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
            background-color: var(--white);
            border: 1px solid var(--filter-border, #ccc);
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
            height: 130px;
            
        }

        .tool-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            border-color: var(--pink);
        }

        [data-bs-theme="dark"] .tool-card:hover {
        border-color: var(--pink); /* Ensure dark mode retains the pink border */
        }

        .tool-card h5 {
            font-size: 1.1rem;
            color: var(--dark);
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

        [data-bs-theme="dark"] .tool-card p {
        color: #a0a0a0; /* Better contrast in dark mode */
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
            transition: color 0.2s ease, background-color 0.2s ease;
            z-index: 10;
            padding: 5px;
            border-radius: 50%;
        }

        .tool-card-favorite:hover {
            background-color: #f4e4ef;
        }
        
        [data-bs-theme="dark"] .tool-card-favorite:hover {
        background-color: #4a1f34; /* Dark mode hover background */
         }

        .tool-card-favorite.favorited {
            color: #FFD700;
        }

        .tool-card-favorite:hover .tooltip {
            visibility: visible;
            opacity: 1;
        }

        .tooltip {
            visibility: hidden;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--dark);
            color: var(--tooltip-color);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            width: 200px;
            text-align: left;
            z-index: 20;
            opacity: 0;
            transition: opacity 0.2s ease;
            transform: translateY(5px);
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

        /* CSS for the favorites section */
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .favorites-section {
            margin-bottom: 30px;
        }

        .favorites-section h2 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #e91e63;
            color: #fff;
            padding: 12px 24px; /* Slightly larger padding for better appearance */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 10000; /* Increased z-index to ensure it appears above all elements */
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            font-size: 1rem;
            font-weight: 500;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0)
        }

        .tool-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            
        }

        .filter-btn {
            background: var(--filter-background);
            border: 1px solid #ccc; /* Matches navigation button border */
            padding: 6px 14px;
            border-radius: 20px;
            color: var(--dark);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn.active {
            background: #EC298B;
            border-color: #EC298B;
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

        <div id="notification" class="notification"></div>

        <div class="favorites-section" id="favoritesSection" style="display: none;">
            <h2>⭐ Your Favorite Tools</h2>
            <div class="favorites-grid" id="favoritesGrid">
            </div>
        </div>
<div class="tool-filters">
  <button class="filter-btn active" data-category="all">All</button>
  <button class="filter-btn" data-category="reading">Reading</button>
  <button class="filter-btn" data-category="writing">Writing</button>
  <button class="filter-btn" data-category="study">Study</button>
  <button class="filter-btn" data-category="creative">Creative</button>
</div>

        <div class="tool-grid" id="allToolsGrid">
            {{-- Text Leveler --}}
            <a href="http://192.168.50.144:8000/leveler" target="_blank" class="tool-card-link" data-tool-id="text-leveler">
                <div class="tool-card" data-category="reading">
                    <span class="tool-card-favorite" data-tool-id="text-leveler"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/text leveler.png') }}" alt="Text Leveler Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Text Leveler</h5>
                            <p>Adjust text difficulty to match your reading level and comprehension needs.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Text Summarizer --}}
            <a href="http://127.0.0.1:8000/summarize" target="_blank" class="tool-card-link" data-tool-id="text-summarizer">
                <div class="tool-card" data-category="reading writing">
                    <span class="tool-card-favorite" data-tool-id="text-summarizer"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/summarizer.png') }}" alt="Text Summarizer Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Text Summarizer</h5>
                            <p>Simplify long text into concise, easy-to-understand summaries.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Conceptual Understanding --}}
            <a href="http://127.0.0.1:8000/tutor"
                target="_blank" class="tool-card-link" data-tool-id="conceptual-understanding">
                <div class="tool-card" data-category="reading study">
                    <span class="tool-card-favorite" data-tool-id="conceptual-understanding"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/conceptual.png') }}" alt="Conceptual Understanding Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Conceptual Understanding</h5>
                            <p>Get writing feedback on grammar, structure, and clarity to build stronger writing skills.
                            </p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Text Rewriter --}}
            <a href="https://tse4.mm.bing.net/th/id/OIP.eEW3EGenj4Djze7zv31PKAHaLG?rs=1&pid=ImgDetMain&o=7&rm=3"
                target="_blank" class="tool-card-link" data-tool-id="text-rewriter">
                <div class="tool-card" data-category="writing">
                    <span class="tool-card-favorite" data-tool-id="text-rewriter"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/text rewritter.png') }}" alt="Text Rewriter Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Text Rewriter</h5>
                            <p>Rephrase sentences to enhance expression and explore new ways of writing.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Text Proofreader --}}
            <a href="http://127.0.0.1:8000/proofreader" target="_blank" class="tool-card-link" data-tool-id="text-proofreader">
                <div class="tool-card" data-category="writing">
                    <span class="tool-card-favorite" data-tool-id="text-proofreader"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/proofreader.png') }}" alt="Text Proofreader Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Text Proofreader</h5>
                            <p>Automatically catch and correct grammar, spelling, and punctuation errors.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Quiz Me! --}}
            <a href="http://127.0.0.1:5001/generate-quiz" target="_blank" class="tool-card-link" data-tool-id="quiz-me">
                <div class="tool-card" data-category="study">
                    <span class="tool-card-favorite" data-tool-id="quiz-me"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/quizme.png') }}" alt="Quiz Me! Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Quiz Me!</h5>
                            <p>Asks questions to test knowledge and boost learning in a fun way.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Text Scaffolder --}}
            <a href="http://192.168.50.123:5001/scaffolder" target="_blank" class="tool-card-link" data-tool-id="text-scaffolder">
                <div class="tool-card" data-category="reading writing">
                    <span class="tool-card-favorite" data-tool-id="text-scaffolder"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/text scaff.png') }}" alt="Text Scaffolder Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Text Scaffolder</h5>
                            <p>Scaffold any text for better comprehension.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Informational Text --}}
            <a href="http://192.168.50.144:5001/informational" target="_blank" class="tool-card-link" data-tool-id="informational-text">
                <div class="tool-card" data-category="writing creative">
                    <span class="tool-card-favorite" data-tool-id="informational-text"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/infotext.png') }}" alt="Informational Text Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Informational Text</h5>
                            <p>Create original texts on any topic for your class.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Step by Step --}}
            <a href="http://localhost:8000/step-tutor" target="_blank" class="tool-card-link" data-tool-id="step-by-step">
                <div class="tool-card" data-category="study">
                    <span class="tool-card-favorite" data-tool-id="step-by-step"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/stepbystep.png') }}" alt="Step by Step Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Step by Step</h5>
                            <p>Get guided instructions for any task or subject.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Five Questions --}}
            <a href="http://127.0.0.1:8000/5questions" target="_blank" class="tool-card-link" data-tool-id="five-questions">
                <div class="tool-card" data-category="study">
                    <span class="tool-card-favorite" data-tool-id="five-questions"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/five.png') }}" alt="Five Questions Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Five Questions</h5>
                            <p>Use AI to challenge your thinking with 5 custom questions.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Real World Connections --}}
            <a href="http://127.0.0.1:8000/realworld"
                target="_blank" class="tool-card-link" data-tool-id="real-world-connections">
                <div class="tool-card" data-category="reading study">
                    <span class="tool-card-favorite" data-tool-id="real-world-connections"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/realworld.png') }}" alt="Real World Connections Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Real World Connections</h5>
                            <p>Generate real world examples for what you're learning about!</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Study Habits --}}
            <a href="http://127.0.0.1:8000/studyhabits" target="_blank" class="tool-card-link" data-tool-id="study-habits">
                <div class="tool-card" data-category="study">
                    <span class="tool-card-favorite" data-tool-id="study-habits"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/studyhabits.png') }}" alt="Study Habits Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Study Habits</h5>
                            <p>Get a plan and study tips to prepare for any test, assignment, or project.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Text Translator --}}
            <a href="http://127.0.0.1:8000/translator"
                target="_blank" class="tool-card-link" data-tool-id="text-translator">
                <div class="tool-card" data-category="reading">
                    <span class="tool-card-favorite" data-tool-id="text-translator"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/text translator.png') }}" alt="Text Translator Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Text Translator</h5>
                            <p>Translate any text or uploaded document into any language.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Sentence Starters --}}
            <a href="http://127.0.0.1:8000/sentencestarter"
                target="_blank" class="tool-card-link" data-tool-id="sentence-starters">
                <div class="tool-card" data-category="writing">
                    <span class="tool-card-favorite" data-tool-id="sentence-starters"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/sentencestarter.png') }}" alt="Sentence Starters Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Sentence Starters</h5>
                            <p>Get ideas to help you get started with writing on any topic.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Assignment Scaffolder --}}
            <a href="http://192.168.50.123:5001/assignmentscaffolder" target="_blank" class="tool-card-link" data-tool-id="assignment-scaffolder">
                <div class="tool-card" data-category="study writing">
                    <span class="tool-card-favorite" data-tool-id="assignment-scaffolder"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/scaff.png') }}" alt="Assignment Scaffolder Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Assignment Scaffolder</h5>
                            <p>Helps break down assignments into easy, step-by-step parts for better planning and
                                progress.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Coach’s Sports Practice --}}
            <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link" data-tool-id="coachs-sports-practice">
                <div class="tool-card" data-category="study">
                    <span class="tool-card-favorite" data-tool-id="coachs-sports-practice"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/coachsports.png') }}" alt="Coach’s Sports Practice Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Coach’s Sports Practice</h5>
                            <p>Leads fun drills to build athletic skills and teamwork.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Email Writer --}}
            <a href="http://127.0.0.1:8000/email-writer" target="_blank" class="tool-card-link" data-tool-id="email-writer">
                <div class="tool-card" data-category="writing">
                    <span class="tool-card-favorite" data-tool-id="email-writer"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/email.png') }}" alt="Email Writer Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Email Writer</h5>
                            <p>Helps draft clear, professional emails for any occasion or purpose.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Multiple Explanations Generator --}}
            <a href="http://192.168.50.123:5001/explanations" target="_blank" class="tool-card-link" data-tool-id="multiple-explanations-generator">
                <div class="tool-card" data-category="study">
                    <span class="tool-card-favorite" data-tool-id="multiple-explanations-generator"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/multiple.png') }}" alt="Multiple Explanations Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Multiple Explanations Generator</h5>
                            <p>Provides different ways to explain and supporting diverse learning styles.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Teacher Jokes --}}
            <a href="http://127.0.0.1:5004/generate-joke" target="_blank" class="tool-card-link" data-tool-id="teacher-jokes">
                <div class="tool-card" data-category="creative">
                    <span class="tool-card-favorite" data-tool-id="teacher-jokes"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/teacherjoke.png') }}" alt="Teacher Jokes Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Teacher Jokes</h5>
                            <p>Generates classroom-friendly jokes to lighten the mood and bring smiles to learning.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Thank You Note --}}
            <a href="http://127.0.0.1:8000/thankyou-note" target="_blank" class="tool-card-link" data-tool-id="thank-you-note">
                <div class="tool-card" data-category="writing creative">
                    <span class="tool-card-favorite" data-tool-id="thank-you-note"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/ty.png') }}" alt="Thank You Note Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Thank You Note</h5>
                            <p>Crafts short, heartfelt messages to show appreciation for any occasion.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Tongue Twisters --}}
            <a href="http://127.0.0.1:5002/generate-tongue-twister" target="_blank" class="tool-card-link" data-tool-id="tongue-twisters">
                <div class="tool-card" data-category="reading creative">
                    <span class="tool-card-favorite" data-tool-id="tongue-twisters"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/tonguetwister.png') }}" alt="Tongue Twisters Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Tongue Twisters</h5>
                            <p>Creates fun and tricky phrases to challenge speech and boost pronunciation skills.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Quote of the Day --}}
            <a href="http://127.0.0.1:5001/5questions" target="_blank" class="tool-card-link" data-tool-id="quote-of-the-day">
                <div class="tool-card" data-category="creative">
                    <span class="tool-card-favorite" data-tool-id="quote-of-the-day"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/quote.png') }}" alt="Quote of the Day Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Quote of the Day</h5>
                            <p>Generate an inspiring or thoughtful quote to spark reflection and motivation.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Email Responder --}}
            <a href="http://192.168.50.123:5001/responder" target="_blank" class="tool-card-link" data-tool-id="email-responder">
                <div class="tool-card" data-category="writing">
                    <span class="tool-card-favorite" data-tool-id="email-responder"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/responder.png') }}" alt="Email ResponderIcon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Email Responder</h5>
                            <p>Generate a professional response based on a received email and your intended message.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Make It Relevant! --}}
            <a href="http://127.0.0.1:5001/makeitrelevant" target="_blank" class="tool-card-link" data-tool-id="make-it-relevant">
                <div class="tool-card" data-category="writing study">
                    <span class="tool-card-favorite" data-tool-id="make-it-relevant"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/relevant.png') }}" alt="Make It Relevant Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Make It Relevant!</h5>
                            <p>Generate ideas that make a topic relevant to your class based on their interests and
                                background.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Chat with Docs --}}
            <a href="http://192.168.50.144:5001/chatwithdocs" target="_blank" class="tool-card-link" data-tool-id="chat-with-docs">
                <div class="tool-card" data-category="reading study">
                    <span class="tool-card-favorite" data-tool-id="chat-with-docs"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/docs.png') }}" alt="Chat with Docs Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Chat with Docs</h5>
                            <p>Upload a document and have an AI-powered chat with it.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Math Review --}}
            <a href="http://127.0.0.1:5001/mathreview" target="_blank" class="tool-card-link" data-tool-id="math-review">
                <div class="tool-card" data-category="study">
                    <span class="tool-card-favorite" data-tool-id="math-review"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/math.png') }}" alt="Math Review Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Math Review</h5>
                            <p>Generate review questions to improve your math skills and understanding.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Character Chatbot --}}
            <a href="http://127.0.0.1:8000/characterchat" target="_blank" class="tool-card-link" data-tool-id="character-chatbot">
                <div class="tool-card" data-category="reading creative">
                    <span class="tool-card-favorite" data-tool-id="character-chatbot"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/chatbot.png') }}" alt="Chatbot Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Character Chatbot</h5>
                            <p>Chat with any historic figure, author, or recognizable character from a story.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Idea Generator --}}
            <a href="http://127.0.0.1:8000/idea-generator" target="_blank" class="tool-card-link" data-tool-id="idea-generator">
                <div class="tool-card" data-category="creative">
                    <span class="tool-card-favorite" data-tool-id="idea-generator"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/idea.png') }}" alt="Idea Generator Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Idea Generator</h5>
                            <p>Use AI as a thought partner to generate ideas on any topic.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Content Creator --}}
            <a href="http://127.0.0.1:8000/contentcreator" target="_blank" class="tool-card-link" data-tool-id="content-creator">
               <div class="tool-card" data-category="writing creative">
                    <span class="tool-card-favorite" data-tool-id="content-creator"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/content.png') }}" alt="Content Creator Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Content Creator</h5>
                            <p>Generate academic content to help you learn.</p>
                        </div>
                    </div>
                </div>
            </a>

            {{-- Social Stories --}}
            <a href="http://127.0.0.1:8000/socialstory" target="_blank" class="tool-card-link" data-tool-id="social-stories">
                <div class="tool-card" data-category="reading creative">
                    <span class="tool-card-favorite" data-tool-id="social-stories"><i class="fas fa-star"></i></span>
                    <div class="tool-card-content">
                        <div class="tool-card-icon">
                            <img src="{{ asset('icons/stories.png') }}" alt="Social Stories Icon">
                        </div>
                        <div class="tool-card-text">
                            <h5>Social Stories</h5>
                            <p>Generate a social story about a particular event to help a student understand what to
                                expect in that situation.</p>
                        </div>
                    </div>
                </div>
            </a>

        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const toolSearch = document.getElementById('toolSearch');
    const allToolsGrid = document.getElementById('allToolsGrid');
    const favoritesGrid = document.getElementById('favoritesGrid');
    const favoritesSection = document.getElementById('favoritesSection');
    const toolLinks = document.querySelectorAll('.tool-card-link');
    const favoriteButtons = document.querySelectorAll('.tool-card-favorite');
    const notification = document.getElementById('notification');
    const filterButtons = document.querySelectorAll('.filter-btn');

    let favoritedTools = JSON.parse(localStorage.getItem('favoritedTools')) || [];

    // Show notification
    function showNotification(message) {
        notification.textContent = message;
        notification.classList.add('show');
        setTimeout(() => {
            notification.classList.remove('show');
        }, 2000);
    }

    // Render favorites
    function renderFavorites() {
        favoritesGrid.innerHTML = '';
        if (favoritedTools.length > 0) {
            favoritesSection.style.display = 'block';
            favoritedTools.forEach(toolId => {
                const originalCard = document.querySelector(`.tool-card-link[data-tool-id="${toolId}"]`);
                if (originalCard) {
                    const clonedCard = originalCard.cloneNode(true);
                    const favoriteButton = clonedCard.querySelector('.tool-card-favorite');
                    favoriteButton.classList.add('favorited');
                    favoriteButton.addEventListener('click', handleFavoriteClick);
                    favoritesGrid.appendChild(clonedCard);
                }
            });
        } else {
            favoritesSection.style.display = 'none';
        }
    }

    // Update all stars
    function updateAllStarIcons() {
        favoriteButtons.forEach(button => {
            const toolId = button.dataset.toolId;
            button.classList.toggle('favorited', favoritedTools.includes(toolId));
        });
        const favoriteGridButtons = favoritesGrid.querySelectorAll('.tool-card-favorite');
        favoriteGridButtons.forEach(button => {
            const toolId = button.dataset.toolId;
            button.classList.toggle('favorited', favoritedTools.includes(toolId));
        });
    }

    // Handle favorite toggle
    function handleFavoriteClick(event) {
        event.preventDefault();
        event.stopPropagation();
        const button = event.currentTarget;
        const toolId = button.dataset.toolId;
        const isFavorited = favoritedTools.includes(toolId);
        const toolCard = button.closest('.tool-card');
        const toolName = toolCard.querySelector('h5').textContent;

        if (isFavorited) {
            favoritedTools = favoritedTools.filter(id => id !== toolId);
            showNotification(`${toolName} removed from favorites`);
        } else {
            favoritedTools.unshift(toolId);
            showNotification(`${toolName} added to favorites`);
        }

        localStorage.setItem('favoritedTools', JSON.stringify(favoritedTools));
        renderFavorites();
        updateAllStarIcons();
    }

    // Initialize
    renderFavorites();
    updateAllStarIcons();
    favoriteButtons.forEach(button => {
        button.addEventListener('click', handleFavoriteClick);
    });

    // Search tools
    toolSearch.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        toolLinks.forEach(link => {
            const text = link.innerText.toLowerCase();
            link.style.display = text.includes(query) ? 'block' : 'none';
        });
    });

    // Prevent default behavior for favorite buttons
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    });

    // Filter buttons
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const selected = btn.getAttribute('data-category');

            toolLinks.forEach(link => {
                const card = link.querySelector('.tool-card');
                const categories = card.getAttribute('data-category').split(' ');
                if (selected === 'all' || categories.includes(selected)) {
                    link.style.display = 'block';
                } else {
                    link.style.display = 'none';
                }
            });
        });
    });
});
    </script>

@endsection