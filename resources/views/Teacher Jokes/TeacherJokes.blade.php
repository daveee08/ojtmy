<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CK Quiz Me!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous" />
    <style>
        body {
            background: linear-gradient(to right, #ffe6ec, #ffffff);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c2c2c;
            padding-top: 80px;
        }

        .navbar-custom {
            background-color: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .navbar-brand {
            font-weight: 700;
            color: #e91e63 !important;
        }

        .nav-link {
            color: #2c2c2c !important;
            font-weight: 500;
            margin-right: 1rem;
        }

        .nav-link:hover {
            color: #e91e63 !important;
        }

        .container {
            background: #ffffff;
            max-width: 900px;
            padding: 3rem 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            margin-top: 2rem;
        }

        .hero {
            text-align: center;
            margin-bottom: 2rem;
        }

        .hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #e91e63;
        }

        .hero p {
            font-size: 1rem;
            color: #555;
            margin-top: 1rem;
        }

        .section-title {
            font-weight: 700;
            font-size: 1.5rem;
            color: #e91e63;
            margin-top: 2.5rem;
            margin-bottom: 1.25rem;
        }

        .tool-item h5 {
            font-weight: 700;
            color: #2c2c2c;
        }

        .tool-item p {
            color: #555;
        }

        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 0.9rem;
            color: #777;
        }

        .btn-pink {
            background-color: #e91e63;
            color: #fff;
            font-weight: 600;
            padding: 0.5rem 2rem;
            border-radius: 8px;
            border: none;
            transition: 0.3s ease;
        }

        .btn-pink:hover {
            background-color: #d81b60;
        }

        .text-pink {
            color: #e91e63 !important;
        }

        .text-highlight {
            color: #e91e63 !important; /* Specific color for fullscreen spinner */
        }

        ul.list-group li {
            border: none;
            padding-left: 0;
            background: transparent;
        }

        #clearInputsBtn {
            transition: color 0.2s;
        }

        #clearInputsBtn:hover,
        #clearInputsBtn:focus {
            color: #d81b60 !important;
        }

        #clearInputsBtn:hover i,
        #clearInputsBtn:focus i {
            color: #d81b60 !important;
        }

        #clearInputsBtn:active {
            color: #ad1457 !important;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top navbar-custom">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#">CK Quiz Me!</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Support</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Fullscreen loading overlay -->
    <div id="loadingOverlay"
        class="position-fixed top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center bg-white bg-opacity-75"
        style="z-index: 9999;">
        <div class="text-center">
            <div class="spinner-border text-highlight mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
            <div class="fw-semibold text-highlight" aria-live="polite">Generating Response..</div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="section-title text-center">CK Quiz Me!</div>
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="p-4 rounded shadow-sm bg-white tool-item">
                    <form id="twisterForm" method="POST" action="/tonguetwister">
                        @csrf
                        <div class="mb-3">
                            <label for="topic" class="form-label">Topic:</label>
                            <input type="text" class="form-control" id="topic" name="topic" value="{{ old('topic') }}"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="grade_level" class="form-label">Grade Level:</label>
                            <select class="form-select" id="grade_level" name="grade_level" required>
                                <option value="Pre-K">Pre-K</option>
                                <option value="Kindergarten">Kindergarten</option>
                                <option value="1st Grade">1st Grade</option>
                                <option value="2nd Grade">2nd Grade</option>
                                <option value="3rd Grade">3rd Grade</option>
                                <option value="4th Grade">4th Grade</option>
                                <option value="5th Grade">5th Grade</option>
                                <option value="6th Grade">6th Grade</option>
                                <option value="7th Grade">7th Grade</option>
                                <option value="8th Grade">8th Grade</option>
                                <option value="9th Grade">9th Grade</option>
                                <option value="10th Grade">10th Grade</option>
                                <option value="11th Grade">11th Grade</option>
                                <option value="12th Grade">12th Grade</option>
                                <option value="University">University</option>
                                <option value="College">College</option>
                                <option value="Professional">Professional</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-pink">Generate Tongue Twister</button>
                        <button type="button" class="btn btn-info ms-2" id="loadExemplarBtn">Load Example</button>
                        <button type="button"
                            class="d-flex align-items-center ms-2"
                            id="clearInputsBtn"
                            style="background: transparent; border: none; color: #e91e63; font-weight: 600; box-shadow: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#e91e63"
                                class="bi bi-arrow-clockwise me-1" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 1 1 .908-.418A4 4 0 1 0 8 4V1.5a.5.5 0 0 1 1 0v3A.5.5 0 0 1 8.5 5h-3a.5.5 0 0 1 0-1H8z" />
                            </svg>
                            <span style="color: #e91e63;">Clear Inputs</span>
                        </button>
                    </form>

                    <!-- Response alert (example placeholder) -->
                    @if ($response)
                    <div class="alert alert-success mt-4">{{ $response }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quiz interactive area -->
        <div id="quiz-area" class="mt-4" style="display:none;">
            <div class="card p-3 shadow-sm">
                <div id="question-text" class="h5 mb-3"></div>
                <div id="options-list" class="mb-3"></div>
                <input type="text" id="user-answer" class="form-control mb-3" placeholder="Type your answer here..." />
                <button id="submit-answer" class="btn btn-pink mb-3">Submit Answer</button>
                <div id="feedback" class="fw-semibold" aria-live="polite"></div>
            </div>
        </div>

        <div id="quiz-summary" class="mt-4" style="display:none;"></div>
        <button id="reveal-answers-btn" class="btn btn-info mt-3" style="display:none;">Reveal Answers</button>

        <div class="footer">
            &copy; {{ date('Y') }} CK Children's Publishing. All rights reserved.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous" defer></script>

    <script defer>
        document.addEventListener('DOMContentLoaded', () => {
            // Variables
            let sessionId = null;
            let questions = []; // Fill this with your quiz questions after generation

            // Elements
            const quizArea = document.getElementById('quiz-area');
            const questionText = document.getElementById('question-text');
            const optionsList = document.getElementById('options-list');
            const userAnswerInput = document.getElementById('user-answer');
            const submitAnswerBtn = document.getElementById('submit-answer');
            const feedbackEl = document.getElementById('feedback');
            const quizSummary = document.getElementById('quiz-summary');
            const revealAnswersBtn = document.getElementById('reveal-answers-btn');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const twisterForm = document.getElementById('twisterForm');

            function startInteractiveQuiz(quizQuestions, session_id) {
                questions = quizQuestions;
                sessionId = session_id;
                fetch('/quizme/start', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_id: sessionId, questions: questions })
                })
                    .then(res => res.json())
                    .then(data => {
                        quizArea.style.display = 'block';
                        revealAnswersBtn.style.display = 'inline-block';
                        showQuestion(data.question, data.options, data.index);
                    });
            }

            function showQuestion(question, options, index) {
                questionText.innerText = `Question ${index + 1}: ${question}`;
                let opts = '';
                for (let i = 0; i < options.length; i++) {
                    opts += `<div>${options[i]}</div>`;
                }
                optionsList.innerHTML = opts;
                userAnswerInput.value = '';
                feedbackEl.innerText = '';
                userAnswerInput.focus();
            }

            submitAnswerBtn.onclick = function () {
                const answer = userAnswerInput.value.trim();
                if (!answer) return;
                fetch('/quizme/answer', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_id: sessionId, answer: answer })
                })
                    .then(res => res.json())
                    .then(data => {
                        feedbackEl.innerText = data.feedback;
                        if (!data.done) {
                            setTimeout(() => {
                                showQuestion(data.next_question, data.options, data.index);
                            }, 1500);
                        } else {
                            quizArea.style.display = 'none';
                            quizSummary.style.display = 'block';
                            quizSummary.innerText = "Quiz complete! See your results in the history.";
                        }
                    });
            };

            revealAnswersBtn.onclick = function () {
                fetch('/quizme/reveal-answers', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session_id: sessionId })
                })
                    .then(res => res.json())
                    .then(data => {
                        let answersHtml = '<h5>All Answers:</h5><ul>';
                        data.answers.forEach((q, i) => {
                            answersHtml += `<li>Q${i + 1}: ${q.question}<br><b>Answer:</b> ${q.answer}</li>`;
                        });
                        answersHtml += '</ul>';
                        quizSummary.innerHTML = answersHtml;
                    });
            };

            document.getElementById('loadExemplarBtn').addEventListener('click', function () {
                document.getElementById('topic').value = 'Silly Snakes';
                document.getElementById('grade_level').value = '1st Grade';
            });

            document.getElementById('clearInputsBtn').addEventListener('click', function () {
                document.getElementById('topic').value = '';
                document.getElementById('grade_level').value = 'Pre-K';
            });

            // Show loading overlay on form submit
            twisterForm.addEventListener('submit', () => {
                loadingOverlay.classList.remove('d-none');
            });

            // Optional: Hide loading overlay after some delay or on page reload as needed.
        });
    </script>

</body>

</html>
