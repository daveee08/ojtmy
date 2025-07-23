@extends('layouts.app') {{-- This indicates it's a Laravel Blade file extending a base layout --}}

@section('content')
<style>
    /* General body styling */
    body {
        background: linear-gradient(to right, #ffe6ec, #ffffff);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #2c2c2c;
        padding-top: 80px; /* To prevent content from being hidden behind fixed navbar */
    }

    /* Navbar styling */
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

    /* Main container styling */
    .container {
        background: #ffffff;
        max-width: 900px;
        padding: 3rem 2rem;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        margin-top: 2rem;
    }

    /* Hero section styling (if used) */
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

    /* Section title styling */
    .section-title {
        font-weight: 700;
        font-size: 1.5rem;
        color: #e91e63;
        margin-top: 2.5rem;
        margin-bottom: 1.25rem;
    }

    /* Tool item styling (form container) */
    .tool-item h5 {
        font-weight: 700;
        color: #2c2c2c;
    }

    .tool-item p {
        color: #555;
    }

    /* Footer styling */
    .footer {
        margin-top: 60px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        text-align: center;
        font-size: 0.9rem;
        color: #777;
    }

    /* Button styling */
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

    /* General info button styling (used for Load Example) */
    .btn-info {
        background-color: #6c757d; /* Bootstrap default secondary/info color */
        color: #fff;
        font-weight: 600;
        padding: 0.5rem 1rem; /* Slightly smaller padding for compact buttons */
        border-radius: 8px;
        border: none;
        transition: 0.3s ease;
    }

    .btn-info:hover {
        background-color: #5a6268;
    }

    /* Text colors */
    .text-pink {
        color: #e91e63 !important;
    }

    .text-highlight {
        color: #e91e63 !important; /* Specific color for fullscreen spinner */
    }

    /* List group styling */
    ul.list-group li {
        border: none;
        padding-left: 0;
        background: transparent;
    }

    /* Clear Inputs button specific styling */
    #clearInputsBtn {
        transition: color 0.2s;
        /* Ensure consistent flex display for content */
        display: flex;
        align-items: center;
        gap: 5px; /* Spacing between icon and text */
        background: transparent; /* Override Bootstrap .btn styling */
        border: none;
        color: #e91e63;
        font-weight: 600;
        box-shadow: none;
        padding: 0.5rem 1rem; /* Match other buttons */
        border-radius: 8px; /* Match other buttons */
    }
    #clearInputsBtn:hover, #clearInputsBtn:focus {
        color: #d81b60 !important;
        background: rgba(233, 30, 99, 0.05); /* Slight background on hover */
    }
    #clearInputsBtn:hover svg, #clearInputsBtn:focus svg {
        fill: #d81b60 !important;
    }
    #clearInputsBtn:active {
        color: #ad1457 !important;
        background: rgba(233, 30, 99, 0.1);
    }

    /* Fullscreen loading overlay */
    #loadingOverlay {
        display: flex; /* Ensure it uses flexbox for centering */
        /* d-none class will initially hide it */
    }

    /* Loading spinner for buttons */
    .loading-spinner {
        display: none; /* Hidden by default */
        border: 4px solid rgba(0, 0, 0, 0.1);
        border-left-color: #e91e63; /* Spinner color */
        border-radius: 50%;
        width: 24px;
        height: 24px;
        animation: spin 1s linear infinite;
        margin-left: 10px; /* Space from text */
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .button-content {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Alert Styling (from TeacherJokes) */
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
    }
    .alert-success { /* Added success alert style for response */
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
    }

    /* Ensure specific styles for form-control-tj from reference are respected if they differ from general form-control */
    .form-control-tj {
        border-color: #ddd; /* Example from TeacherJokes */
        padding: 10px 15px; /* Example from TeacherJokes */
        border-radius: 8px; /* Example from TeacherJokes */
        width: 100%;
        box-sizing: border-box;
    }
</style>

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

{{-- Fullscreen loading overlay (remains active for API calls) --}}
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 9999;">
    <div class="text-center">
        <div class="spinner-border text-highlight mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <div class="fw-semibold text-highlight">Generating Response..</div>
    </div>
</div>

<div class="container">
    <div class="section-title text-center">CK Quiz Me!</div>
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="p-4 rounded shadow-sm bg-white tool-item">
                {{-- The form will now initiate the quiz, and further interaction will be via AJAX --}}
                <form id="quizForm" method="POST" action="{{ route('quizme.generate') }}">
                    @csrf {{-- Laravel CSRF token for form submission --}}

                    {{-- **IMPORTANT: HIDDEN INPUT FOR 'input_type' - always 'topic' for initial generation** --}}
                    <input type="hidden" name="input_type" value="topic">

                    {{-- Display validation errors and general errors --}}
                    @if ($errors->any() || (isset($errorMessage) && $errorMessage))
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                                @if (isset($errorMessage) && $errorMessage)
                                    <li>{{ $errorMessage }}</li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="topic" class="form-label">Topic / Quiz Instructions:</label>
                        {{-- Encourage user to specify quiz length and type here --}}
                        <textarea class="form-control" id="topic" name="topic" rows="3" placeholder="e.g., Natural sciences, or history of WWII. You can also specify here if you want a certain number of questions if you leave the 'Number of Questions' field blank, like 'history of WWII for about 7 questions'." required>{{ old('topic', $topic ?? '') }}</textarea>
                        <div class="form-text">Provide the main topic. You can also include desired length or quiz types here (e.g., "5 multiple choice questions on fractions").</div>
                    </div>

                    <div class="mb-3">
                        <label for="grade_level" class="form-label">Grade Level:</label>
                        <select class="form-select" id="grade_level" name="grade_level" required>
                            <option value="" disabled {{ old('grade_level', $grade_level ?? '') == '' ? 'selected' : '' }}>Select a Grade Level</option>
                            <option value="Pre-K" {{ (old('grade_level', $grade_level ?? '') == 'Pre-K') ? 'selected' : '' }}>Pre-K</option>
                            <option value="Kindergarten" {{ (old('grade_level', $grade_level ?? '') == 'Kindergarten') ? 'selected' : '' }}>Kindergarten</option>
                            <option value="1st Grade" {{ (old('grade_level', $grade_level ?? '') == '1st Grade') ? 'selected' : '' }}>1st Grade</option>
                            <option value="2nd Grade" {{ (old('grade_level', $grade_level ?? '') == '2nd Grade') ? 'selected' : '' }}>2nd Grade</option>
                            <option value="3rd Grade" {{ (old('grade_level', $grade_level ?? '') == '3rd Grade') ? 'selected' : '' }}>3rd Grade</option>
                            <option value="4th Grade" {{ (old('grade_level', $grade_level ?? '') == '4th Grade') ? 'selected' : '' }}>4th Grade</option>
                            <option value="5th Grade" {{ (old('grade_level', $grade_level ?? '') == '5th Grade') ? 'selected' : '' }}>5th Grade</option>
                            <option value="6th Grade" {{ (old('grade_level', $grade_level ?? '') == '6th Grade') ? 'selected' : '' }}>6th Grade</option>
                            <option value="7th Grade" {{ (old('grade_level', $grade_level ?? '') == '7th Grade') ? 'selected' : '' }}>7th Grade</option>
                            <option value="8th Grade" {{ (old('grade_level', $grade_level ?? '') == '8th Grade') ? 'selected' : '' }}>8th Grade</option>
                            <option value="9th Grade" {{ (old('grade_level', $grade_level ?? '') == '9th Grade') ? 'selected' : '' }}>9th Grade</option>
                            <option value="10th Grade" {{ (old('grade_level', $grade_level ?? '') == '10th Grade') ? 'selected' : '' }}>10th Grade</option>
                            <option value="11th Grade" {{ (old('grade_level', $grade_level ?? '') == '11th Grade') ? 'selected' : '' }}>11th Grade</option>
                            <option value="12th Grade" {{ (old('grade_level', $grade_level ?? '') == '12th Grade') ? 'selected' : '' }}>12th Grade</option>
                            <option value="University" {{ (old('grade_level', $grade_level ?? '') == 'University') ? 'selected' : '' }}>University</option>
                            <option value="College" {{ (old('grade_level', $grade_level ?? '') == 'College') ? 'selected' : '' }}>College</option>
                            <option value="Professional" {{ (old('grade_level', $grade_level ?? '') == 'Professional') ? 'selected' : '' }}>Professional</option>
                        </select>
                    </div>

                    {{-- Re-adding the optional Number of Questions field --}}
                    <div class="mb-3">
                        <label for="num_questions" class="form-label">Number of Questions (Optional):</label>
                        <input type="number" class="form-control" id="num_questions" name="num_questions" min="1" max="50" placeholder="e.g., 10" value="{{ old('num_questions', $num_questions ?? '') }}">
                        <div class="form-text">Specify the exact number of questions, or leave blank and let the AI decide based on your topic instructions. Max 50.</div>
                    </div>


                    {{-- Quiz Type Selection (checkboxes for multiple types) --}}
                    <div class="mb-3">
                        <label class="form-label">Desired Quiz Types:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="quiz_types[]" value="multiple_choice" id="quizTypeMC" checked>
                            <label class="form-check-label" for="quizTypeMC">Multiple Choice</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="quiz_types[]" value="fill_in_the_blanks" id="quizTypeFIB">
                            <label class="form-check-label" for="quizTypeFIB">Fill-in-the-Blanks</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="quiz_types[]" value="identification" id="quizTypeID">
                            <label class="form-check-label" for="quizTypeID">Identification</label>
                        </div>
                        <div class="form-text">Select one or more quiz formats. The AI will try to combine them as requested.</div>
                    </div>


                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-pink" id="generate-btn">
                            <span class="button-content">
                                Start Quiz Generation
                                <span class="loading-spinner" id="loading-spinner"></span>
                            </span>
                        </button>
                        <button type="button" class="btn btn-info" id="loadExemplarBtn">Load Example</button>
                        <button type="button" id="clearInputsBtn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-arrow-clockwise me-1" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 1 1 .908-.418A4 4 0 1 0 8 4V1.5a.5.5 0 0 1 1 0v3A.5.5 0 0 1 8.5 5h-3a.5.5 0 0 1 0-1H8z" />
                            </svg>
                            <span>Clear Inputs</span>
                        </button>
                    </div>
                </form>

                {{-- The section below will be populated by JavaScript after the quiz is generated --}}
                <div id="quiz-output-area" class="mt-4" style="display: none;">
                    {{-- This is where the initial generated quiz questions/response will appear --}}
                    <h5>Generated Quiz Questions:</h5>
                    <div id="generated-questions-display"></div>
                    <button id="start-interactive-quiz-btn" class="btn btn-primary mt-3">Begin Interactive Quiz</button>
                </div>

                {{-- Interactive Quiz Area (hidden until interactive quiz starts) --}}
                <div id="interactive-quiz-area" style="display:none; margin-top: 20px;">
                    <h4>Interactive Quiz</h4>
                    <h5 id="question-counter"></h5> {{-- Added counter here --}}
                    <p id="current-question-text"></p>
                    <div id="current-question-options-list" class="mb-3"></div>
                    <input type="text" id="user-current-answer" class="form-control mb-2" placeholder="Your answer">
                    <p id="current-feedback" class="mt-2"></p> {{-- Feedback moved above buttons for clarity --}}
                    <button id="submit-current-answer" class="btn btn-success">Submit Answer</button>
                    <button id="next-question-btn" class="btn btn-info ms-2" style="display: none;">Next Question</button>
                </div>
                <div id="quiz-final-summary" style="display:none; margin-top: 20px;"></div>

            </div>
        </div>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} CK Children's Publishing. All rights reserved.
    </div>
</div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous" defer></script>

    <script defer>
        console.log("CK Quiz Me! script has started execution.");

        let generatedQuizData = null; // Stores the entire quiz data (all questions, answers, etc.)
        let currentQuestionIndex = 0;
        let quizSessionId = null; // To track the current quiz session with the backend

        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

        function toggleFullscreenLoading(show) {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                if (show) {
                    loadingOverlay.classList.remove('d-none');
                } else {
                    loadingOverlay.classList.add('d-none');
                }
            }
        }

        // Function to display an individual question for the interactive part
        function displayCurrentQuestion() {
            if (!generatedQuizData || generatedQuizData.length === 0 || currentQuestionIndex >= generatedQuizData.length) {
                showQuizSummary(); // All questions answered or no questions
                return;
            }

            const question = generatedQuizData[currentQuestionIndex];
            const questionCounterElem = document.getElementById('question-counter'); // Get the question counter element
            const questionTextElem = document.getElementById('current-question-text');
            const optionsListElem = document.getElementById('current-question-options-list');
            const userAnswerElem = document.getElementById('user-current-answer');
            const feedbackElem = document.getElementById('current-feedback');
            const submitAnswerBtn = document.getElementById('submit-current-answer');
            const nextQuestionBtn = document.getElementById('next-question-btn');

            if (questionCounterElem) {
                questionCounterElem.textContent = `Question ${currentQuestionIndex + 1} of ${generatedQuizData.length}`;
            }

            if (questionTextElem) questionTextElem.textContent = question.question;

            if (optionsListElem) {
                optionsListElem.innerHTML = ''; // Clear previous options
                // Check if it's multiple_choice AND options array exists and has elements
                if (question.type === 'multiple_choice' && question.options && question.options.length > 0) {
                    optionsListElem.style.display = 'block'; // Show options list
                    userAnswerElem.style.display = 'none'; // Hide text input
                    currentQuestionIndex
                    question.options.forEach(option => {
                        const div = document.createElement('div');
                        div.className = 'form-check';
                        // Generate a more robust ID for the radio button
                        const optionId = `option-${currentQuestionIndex}-${option.replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_]/g, '').toLowerCase()}`;
                        div.innerHTML = `
                            <input class="form-check-input" type="radio" name="current_option" id="${optionId}" value="${option}">
                            <label class="form-check-label" for="${optionId}">${option}</label>
                        `;
                        optionsListElem.appendChild(div);
                    });
                } else {
                    // For fill-in-the-blanks and identification, hide options list and show text input
                    optionsListElem.style.display = 'none';
                    userAnswerElem.style.display = 'block';
                    // Clear any existing radio selections if they persist from a previous MCQ
                    const existingRadios = document.querySelectorAll('input[name="current_option"]:checked');
                    existingRadios.forEach(radio => radio.checked = false);
                }
            }
            if (userAnswerElem) userAnswerElem.value = ''; // Clear user's text input
            if (feedbackElem) {
                feedbackElem.innerHTML = ''; // Clear feedback
                feedbackElem.className = 'mt-2'; // Reset classes
            }

            if (submitAnswerBtn) {
                submitAnswerBtn.style.display = 'block'; // Always show submit for current question
                submitAnswerBtn.removeAttribute('disabled');
            }
            if (nextQuestionBtn) nextQuestionBtn.style.display = 'none'; // Hide next question until answered
            if (userAnswerElem && userAnswerElem.style.display !== 'none') userAnswerElem.focus(); // Focus on the answer input if visible
        }

        function showQuizSummary() {
            const interactiveQuizArea = document.getElementById('interactive-quiz-area');
            const quizFinalSummary = document.getElementById('quiz-final-summary');
            const formContainer = document.querySelector('.tool-item'); // Assuming .tool-item is the main container

            if (interactiveQuizArea) interactiveQuizArea.style.display = 'none';
            if (quizFinalSummary) {
                let score = 0;
                generatedQuizData.forEach(q => {
                    if (q.is_correct) {
                        score++;
                    }
                });

                let summaryHtml = `
                    <h5 class="text-center section-title">Quiz Complete!</h5>
                    <p class="text-center fs-4">You scored <span class="text-pink fw-bold">${score}</span> out of <span class="text-pink fw-bold">${generatedQuizData.length}</span> questions.</p>
                    <hr>
                    <h5 class="mb-3">Review your answers:</h5>
                    <ul class="list-group list-group-flush">
                `;

                generatedQuizData.forEach((q, i) => {
                    const feedbackClass = q.is_correct ? 'text-success' : 'text-danger';
                    const feedbackText = q.is_correct ? 'Correct!' : 'Incorrect.';
                    const userAnswer = q.user_answer !== undefined && q.user_answer !== null ? q.user_answer : 'No answer provided';

                    summaryHtml += `
                        <li class="list-group-item">
                            <p class="mb-1"><strong>Q${i+1}:</strong> ${q.question}</p>
                    `;
                    if (q.type === 'multiple_choice' && q.options) {
                        summaryHtml += `<p class="mb-1 ms-3"><strong>Options:</strong> ${q.options.join(', ')}</p>`;
                    }
                    summaryHtml += `
                            <p class="mb-1 ms-3"><strong>Your Answer:</strong> ${userAnswer}</p>
                            <p class="mb-1 ms-3"><strong>Correct Answer:</strong> <span class="text-success">${q.answer}</span></p>
                            <p class="mb-0 ms-3"><strong>Feedback:</strong> <span class="${feedbackClass}">${feedbackText}</span></p>
                        </li>
                    `;
                });
                summaryHtml += '</ul>';
                quizFinalSummary.innerHTML = summaryHtml;
                quizFinalSummary.style.display = 'block';

                // You might want to add a "Start New Quiz" button here
                const startNewQuizBtn = document.createElement('button');
                startNewQuizBtn.className = 'btn btn-pink mt-4 w-100';
                startNewQuizBtn.textContent = 'Start New Quiz';
                startNewQuizBtn.addEventListener('click', () => {
                    location.reload(); // Reloads the page to start fresh
                });
                quizFinalSummary.appendChild(startNewQuizBtn);

                // Scroll to summary
                quizFinalSummary.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }


        document.addEventListener('DOMContentLoaded', () => {
            const quizForm = document.getElementById('quizForm');
            const generateBtn = document.getElementById('generate-btn');
            const loadingSpinner = document.getElementById('loading-spinner');
            const topicInput = document.getElementById('topic');
            const gradeLevelSelect = document.getElementById('grade_level');
            const numQuestionsInput = document.getElementById('num_questions');
            const loadExemplarBtn = document.getElementById('loadExemplarBtn');
            const clearInputsBtn = document.getElementById('clearInputsBtn');
            const quizOutputArea = document.getElementById('quiz-output-area');
            const generatedQuestionsDisplay = document.getElementById('generated-questions-display');
            const startInteractiveQuizBtn = document.getElementById('start-interactive-quiz-btn');
            const interactiveQuizArea = document.getElementById('interactive-quiz-area');
            const submitCurrentAnswerBtn = document.getElementById('submit-current-answer');
            const nextQuestionBtn = document.getElementById('next-question-btn');
            const quizFinalSummary = document.getElementById('quiz-final-summary');
            const userAnswerElem = document.getElementById('user-current-answer');
            const optionsListElem = document.getElementById('current-question-options-list');
            const feedbackElem = document.getElementById('current-feedback');

            // Event Listener for the main quiz generation form submission
            if (quizForm) {
                quizForm.addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent default form submission

                    toggleFullscreenLoading(true); // Show fullscreen loading
                    if (generateBtn) {
                        loadingSpinner.style.display = 'inline-block';
                        generateBtn.setAttribute('disabled', 'disabled');
                        generateBtn.style.opacity = '0.7';
                    }

                    // Construct the data object directly to ensure quiz_types is an array
                    const data = {
                        input_type: document.querySelector('input[name="input_type"]').value,
                        topic: topicInput.value,
                        grade_level: gradeLevelSelect.value,
                        quiz_types: Array.from(document.querySelectorAll('input[name="quiz_types[]"]:checked'))
                                                .map(checkbox => checkbox.value)
                    };

                    const numQuestions = numQuestionsInput ? numQuestionsInput.value : '';
                    if (numQuestions !== null && numQuestions !== '' && !isNaN(parseInt(numQuestions))) {
                        data.num_questions = parseInt(numQuestions);
                    } else {
                        // If num_questions is empty or not a valid number, ensure it's not sent or sent as null
                        // The Laravel validation already handles nullable, so not including it is fine.
                    }

                    fetch(quizForm.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        },
                        body: JSON.stringify(data) // Send the 'data' object as JSON
                    })
                    .then(response => {
                        if (!response.ok) {
                            // Check for HTTP errors (e.g., 400, 500)
                            return response.json().then(errorData => {
                                // Log the error response from the server
                                console.error('Server error response:', errorData);
                                let message = errorData.message || 'An unknown error occurred.';
                                if (errorData.errors) { // Laravel validation errors
                                    for (const key in errorData.errors) {
                                        message += `\n- ${errorData.errors[key].join(', ')}`;
                                    }
                                }
                                throw new Error(message);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Hide the input form and show the output area
                        quizForm.style.display = 'none';
                        quizOutputArea.style.display = 'block';
                        interactiveQuizArea.style.display = 'none'; // Ensure interactive area is hidden initially
                        quizFinalSummary.style.display = 'none'; // Ensure summary is hidden

                        quizSessionId = data.session_id; // Store the session ID from the response
                        generatedQuizData = data.questions; // Store the generated questions

                        // Display the generated questions as raw text first (for review)
                        let rawQuestionsHtml = '<h5>Review your questions:</h5>';
                        if (generatedQuizData && generatedQuizData.length > 0) {
                            generatedQuizData.forEach((q, index) => {
                                rawQuestionsHtml += `<p><strong>Q${index + 1}:</strong> ${q.question}</p>`;
                                if (q.type === 'multiple_choice' && q.options) {
                                    rawQuestionsHtml += `<p class="ms-3">Options: ${q.options.join(', ')}</p>`;
                                }
                                // Optionally display correct answers for review if desired (for dev/debugging)
                                // rawQuestionsHtml += `<p class="ms-3">Correct Answer: ${q.answer}</p>`;
                            });
                        } else {
                            rawQuestionsHtml = '<p>No questions were generated. Please try again with different parameters.</p>';
                            startInteractiveQuizBtn.style.display = 'none'; // Hide button if no questions
                        }
                        generatedQuestionsDisplay.innerHTML = rawQuestionsHtml;

                        // Show the "Begin Interactive Quiz" button (unless no questions were generated)
                        if (generatedQuizData && generatedQuizData.length > 0) {
                           startInteractiveQuizBtn.style.display = 'block';
                        }


                        // Clear any previous error messages (if you have an alert div for them)
                        // const errorMessageDisplay = document.getElementById('error-message-display');
                        // if (errorMessageDisplay) errorMessageDisplay.innerHTML = '';
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        let displayMessage = `Error: ${error.message}`;
                        // Display error message to the user, e.g., in an alert div
                        const errorAlertDiv = document.createElement('div');
                        errorAlertDiv.className = 'alert alert-danger mt-3';
                        errorAlertDiv.textContent = displayMessage;
                        quizForm.insertAdjacentElement('beforebegin', errorAlertDiv); // Insert before the form
                        // Remove after a few seconds
                        setTimeout(() => { errorAlertDiv.remove(); }, 7000);
                    })
                    .finally(() => {
                        toggleFullscreenLoading(false); // Hide fullscreen loading
                        if (generateBtn) {
                            loadingSpinner.style.display = 'none';
                            generateBtn.removeAttribute('disabled');
                            generateBtn.style.opacity = '1';
                        }
                    });
                });
            }

            // Event Listener for "Load Example" button
            if (loadExemplarBtn) {
                loadExemplarBtn.addEventListener('click', () => {
                    topicInput.value = "Natural sciences: Biology, chemistry, physics, astronomy, and Earth science.";
                    gradeLevelSelect.value = "University";
                    numQuestionsInput.value = "5";
                    // Check the multiple choice checkbox and uncheck others if present
                    document.getElementById('quizTypeMC').checked = true;
                    document.getElementById('quizTypeFIB').checked = false;
                    document.getElementById('quizTypeID').checked = false;
                });
            }

            // Event Listener for "Clear Inputs" button
            if (clearInputsBtn) {
                clearInputsBtn.addEventListener('click', () => {
                    topicInput.value = '';
                    gradeLevelSelect.value = '';
                    numQuestionsInput.value = '';
                    // Reset quiz type checkboxes (e.g., only multiple choice checked)
                    document.getElementById('quizTypeMC').checked = true;
                    document.getElementById('quizTypeFIB').checked = false;
                    document.getElementById('quizTypeID').checked = false;
                });
            }

            // Event Listener for "Begin Interactive Quiz" button
            if (startInteractiveQuizBtn) {
                startInteractiveQuizBtn.addEventListener('click', () => {
                    quizOutputArea.style.display = 'none'; // Hide the review area
                    interactiveQuizArea.style.display = 'block'; // Show the interactive quiz area
                    currentQuestionIndex = 0; // Reset question index
                    displayCurrentQuestion(); // Display the first question
                });
            }

            // Event Listener for "Submit Answer" button (for interactive quiz)
            if (submitCurrentAnswerBtn) {
                submitCurrentAnswerBtn.addEventListener('click', () => {
                    const currentQuestion = generatedQuizData[currentQuestionIndex];
                    let userAnswer;

                    if (currentQuestion.type === 'multiple_choice') {
                        const selectedOption = document.querySelector('input[name="current_option"]:checked');
                        userAnswer = selectedOption ? selectedOption.value : '';
                    } else {
                        userAnswer = userAnswerElem ? userAnswerElem.value : '';
                    }

                    // Disable inputs/buttons to prevent multiple submissions
                    if (currentQuestion.type === 'multiple_choice') {
                        document.querySelectorAll('input[name="current_option"]').forEach(radio => radio.disabled = true);
                    }
                    if (userAnswerElem) userAnswerElem.disabled = true;
                    submitCurrentAnswerBtn.disabled = true;

                    // Send the answer to the backend for checking and storage
                    fetch("{{ route('quizme.submitAnswer') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        },
                        body: JSON.stringify({
                            session_id: quizSessionId,
                            question_index: currentQuestionIndex,
                            user_answer: userAnswer,
                            question_type: currentQuestion.type,
                            correct_answer: currentQuestion.answer // Send correct answer for backend checking
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(errorData => {
                                console.error('Error submitting answer:', errorData);
                                throw new Error(errorData.message || 'Failed to submit answer.');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Update feedback based on backend response
                        if (feedbackElem) {
                            feedbackElem.textContent = data.feedback;
                            feedbackElem.className = 'mt-2 ' + (data.is_correct ? 'text-success' : 'text-danger');
                        }

                        // Store correctness in local generatedQuizData for summary
                        generatedQuizData[currentQuestionIndex].user_answer = userAnswer;
                        generatedQuizData[currentQuestionIndex].is_correct = data.is_correct;

                        submitCurrentAnswerBtn.style.display = 'none'; // Hide submit button
                        if (currentQuestionIndex < generatedQuizData.length - 1) {
                            nextQuestionBtn.style.display = 'block'; // Show next button
                        } else {
                            // Last question, show button to finish/see summary
                            const finishButton = document.createElement('button');
                            finishButton.className = 'btn btn-primary mt-3';
                            finishButton.textContent = 'Finish Quiz & See Summary';
                            finishButton.addEventListener('click', showQuizSummary);
                            interactiveQuizArea.appendChild(finishButton);
                        }
                    })
                    .catch(error => {
                        console.error('Submit answer fetch error:', error);
                        if (feedbackElem) {
                            feedbackElem.textContent = `Error: ${error.message}`;
                            feedbackElem.className = 'mt-2 text-danger';
                        }
                        // Re-enable inputs/buttons if an error occurs to allow user to retry
                        if (currentQuestion.type === 'multiple_choice') {
                            document.querySelectorAll('input[name="current_option"]').forEach(radio => radio.disabled = false);
                        }
                        if (userAnswerElem) userAnswerElem.disabled = false;
                        submitCurrentAnswerBtn.disabled = false;
                    });
                });
            }

            // Event Listener for "Next Question" button
            if (nextQuestionBtn) {
                nextQuestionBtn.addEventListener('click', () => {
                    currentQuestionIndex++;
                    displayCurrentQuestion(); // Load the next question
                    // Re-enable inputs for the new question
                    if (generatedQuizData[currentQuestionIndex].type === 'multiple_choice') {
                        document.querySelectorAll('input[name="current_option"]').forEach(radio => radio.disabled = false);
                    }
                    if (userAnswerElem) userAnswerElem.disabled = false;
                });
            }
        });
    </script>
@endpush