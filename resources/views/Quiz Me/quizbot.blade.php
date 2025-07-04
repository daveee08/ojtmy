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
            padding-top: 80px; /* Space for fixed navbar */
        }

        .navbar-custom {
            background-color: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .navbar-brand {
            font-weight: 700;
            color: #e91e63 !important;
            font-size: 1.25rem; /* Base font size for mobile brand */
        }

        .nav-link {
            color: #2c2c2c !important;
            font-weight: 500;
            margin-right: 0.5rem; /* Slightly reduced margin for mobile */
            padding: 0.5rem 1rem; /* Better touch target for nav links */
        }

        .nav-link:hover {
            color: #e91e63 !important;
        }

        .container {
            background: #ffffff;
            max-width: 900px;
            padding: 1.5rem 1rem; /* Reduced padding for smaller screens, base for mobile-first */
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            margin-top: 1.5rem; /* Slightly reduced margin top for mobile */
        }

        .hero {
            text-align: center;
            margin-bottom: 1.5rem; /* Adjusted margin */
        }

        .hero h1 {
            font-size: 2rem; /* Base font size for mobile headings */
            font-weight: 800;
            color: #e91e63;
        }

        .hero p {
            font-size: 0.9rem;
            color: #555;
            margin-top: 0.5rem;
        }

        .section-title {
            font-weight: 700;
            font-size: 1.25rem; /* Base font size for mobile section titles */
            color: #e91e63;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .tool-item h5 {
            font-weight: 700;
            color: #2c2c2c;
        }

        .tool-item p {
            color: #555;
        }

        .footer {
            margin-top: 40px; /* Reduced margin top */
            padding-top: 15px; /* Reduced padding */
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 0.8rem;
            color: #777;
        }

        .btn-pink {
            background-color: #e91e63;
            color: #fff;
            font-weight: 600;
            padding: 0.75rem 1.5rem; /* Increased padding for better touch target */
            border-radius: 8px;
            border: none;
            transition: 0.3s ease;
            width: 100%; /* Make buttons full width on mobile */
            margin-bottom: 0.5rem; /* Add space between stacked buttons */
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

        /* Media query for larger screens (e.g., tablets and desktops) */
        @media (min-width: 768px) {
            .container {
                padding: 3rem 2rem; /* Restore original padding for larger screens */
                margin-top: 2rem; /* Restore original margin top */
            }
            .hero h1 {
                font-size: 2.5rem; /* Restore original font size for larger screens */
            }
            .section-title {
                font-size: 1.5rem; /* Restore original font size for larger screens */
            }
            .btn-pink {
                width: auto; /* Buttons return to auto width on larger screens */
                margin-bottom: 0; /* Remove bottom margin when not stacked */
            }
            /* Adjust spacing for buttons if they are next to each other */
            .btn-pink + .btn-info, .btn-info + .btn-secondary, .btn-pink + .btn-secondary {
                margin-left: 0.5rem;
                margin-bottom: 0;
            }
            .nav-link {
                margin-right: 1rem; /* Restore original margin for nav links */
                padding: 0.5rem 1rem; /* Keep existing padding */
            }
        }

        /* Specific styles for download buttons within quiz-summary */
        #quiz-summary .download-form .btn, #resources-output .download-form .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }

        @media (min-width: 768px) {
            #quiz-summary .download-form, #resources-output .download-form {
                display: inline; /* Make forms inline for larger screens */
            }
            #quiz-summary .download-form .btn, #resources-output .download-form .btn {
                width: auto; /* Restore auto width for buttons */
                margin-bottom: 0;
            }
            #quiz-summary .download-form + .download-form, #resources-output .download-form + .download-form {
                margin-left: 10px;
            }
        }

        /* Chat history specific styles */
        #chat-history {
            font-size: 0.9rem;
        }
        #chat-history p {
            margin-bottom: 0.25rem; /* Reduce spacing between chat messages */
        }
        #chatInput {
            min-height: 48px; /* Ensure chat input is tall enough for touch */
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

    {{-- Fullscreen loading overlay --}}
    <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 9999;">
        <div class="text-center">
            <div class="spinner-border text-highlight mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
            <div class="fw-semibold text-highlight">Generating Response..</div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="section-title text-center">CK Quiz Me!</div>
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="p-4 rounded shadow-sm bg-white tool-item">
                    <form id="quizForm">
                        @csrf
                        <div class="mb-3">
                            <label for="topic" class="form-label">Topic or Exam You Want to Be Quizzed On:</label>
                            <input type="text" class="form-control" id="topic" name="topic" required>
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
                                <option value="1st Year College">1st Year College</option>
                                <option value="2nd Year College">2nd Year College</option>
                                <option value="3rd Year College">3rd Year College</option>
                                <option value="4th Year College">4th Year College</option>
                                <option value="Adult">Adult</option>
                                <option value="Professional Staff">Professional Staff</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="num_questions" class="form-label">Number of Questions:</label>
                            <input type="number" class="form-control" id="num_questions" name="num_questions" value="10" required>
                        </div>
                        <button type="submit" class="btn btn-pink" id="generateBtn">Generate Quiz</button>
                        <button type="button" class="btn btn-info ms-2" id="loadExemplarBtn">Load Example</button>
                        <button type="button" class="btn btn-secondary ms-2" id="clearInputsBtn">Clear Inputs</button>
                        <div id="loadingSpinner" class="spinner-border text-pink mt-3" role="status" style="display:none;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span id="loadingText" class="text-pink ms-2" style="display:none;">Generating quiz and resources...</span>
                    </form>

                    <div id="interactive-quiz" style="display:none;">
                        <h5 class="section-title" id="currentQuestionNumber"></h5>
                        <p id="questionText" class="fw-bold"></p>
                        <div id="questionOptions" class="mb-3"></div>
                        <div class="mb-3">
                            <label for="userAnswer" class="form-label">Your Answer:</label>
                            <input type="text" class="form-control" id="userAnswer" placeholder="Type your answer here...">
                        </div>
                        <button type="button" class="btn btn-pink" id="submitAnswerBtn">Submit Answer</button>
                        <button type="button" class="btn btn-success ms-2" id="nextQuestionBtn" style="display:none;">Next Question</button>
                        <div id="feedback" class="mt-3"></div>
                    </div>

                    <div id="quiz-summary" style="display:none;">
                        <h5 class="section-title">Quiz Summary</h5>
                        <p id="summaryText"></p>
                        <pre style="white-space: pre-wrap; word-wrap: break-word; display:none;" id="quizContent"></pre>
                        <div id="download-buttons-summary" class="mt-3">
                            <form action="{{ route('quizme.download') }}" method="POST" style="display:inline;" class="download-form">
                                @csrf
                                <input type="hidden" name="content" id="quizTextContent" value="">
                                <input type="hidden" name="filename" value="quiz">
                                <input type="hidden" name="format" value="txt">
                                <input type="hidden" name="topic_name" id="quizTopicName" value="">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Save Quiz as Text</button>
                            </form>
                            <form action="{{ route('quizme.download') }}" method="POST" style="display:inline;" class="download-form">
                                @csrf
                                <input type="hidden" name="content" id="quizPdfContent" value="">
                                <input type="hidden" name="filename" value="quiz">
                                <input type="hidden" name="format" value="pdf">
                                <input type="hidden" name="topic_name" id="quizPdfTopicName" value="">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Save Quiz as PDF</button>
                            </form>
                        </div>
                    </div>

                    <div id="resources-output" class="mt-4" style="display:none;">
                        <h5 class="section-title">Generated Resources</h5>
                        <pre style="white-space: pre-wrap; word-wrap: break-word;" id="resourcesContent"></pre>
                        <div class="mt-3">
                            <form action="{{ route('quizme.download') }}" method="POST" style="display:inline;" class="download-form">
                                @csrf
                                <input type="hidden" name="content" id="resourcesTextContent" value="">
                                <input type="hidden" name="filename" value="resources">
                                <input type="hidden" name="format" value="txt">
                                <input type="hidden" name="topic_name" id="resourcesTopicName" value="">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Save Resources as Text</button>
                            </form>
                            <form action="{{ route('quizme.download') }}" method="POST" style="display:inline;" class="download-form">
                                @csrf
                                <input type="hidden" name="content" id="resourcesPdfContent" value="">
                                <input type="hidden" name="filename" value="resources">
                                <input type="hidden" name="format" value="pdf">
                                <input type="hidden" name="topic_name" id="resourcesPdfTopicName" value="">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Save Resources as PDF</button>
                            </form>
                        </div>
                    </div>

                    <div id="conversational-ai" class="mt-4" style="display:none;">
                        <h5 class="section-title">CK Quiz me Follow up AI</h5>
                        <div id="chat-history" class="border p-3 mb-3" style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa; border-radius: 8px;"></div>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="chatInput" placeholder="Ask a follow-up question or for more details...">
                            <button class="btn btn-primary" type="button" id="sendChatBtn">Send</button>
                        </div>
                        <div id="chatLoadingSpinner" class="spinner-border text-pink mt-3" role="status" style="display:none;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span id="chatLoadingText" class="text-pink ms-2" style="display:none;">Generating response...</span>
                    </div>

                    <div id="errorMessage" class="alert alert-danger mt-4" style="display:none;"></div>
                </div>
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} CK Children's Publishing. All rights reserved.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous" defer>
    </script>

    <script defer>
        console.log("Quizbot script has started execution. From Alert");
        alert("Quizbot script has started execution!");
        let currentQuiz = [];
        let currentQuestionIndex = 0;
        let currentTopic = '';
        let currentGradeLevel = '';

        document.getElementById('quizForm').addEventListener('submit', async function(event) {
            console.log('Generate Quiz button clicked, form submitted.');
            event.preventDefault();

            const generateBtn = document.getElementById('generateBtn');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const loadingText = document.getElementById('loadingText');
            const interactiveQuizDiv = document.getElementById('interactive-quiz');
            const quizSummaryDiv = document.getElementById('quiz-summary');
            const resourcesOutputDiv = document.getElementById('resources-output');
            const errorMessageDiv = document.getElementById('errorMessage');
            const quizContentPre = document.getElementById('quizContent'); // This will be used for final summary text download
            const resourcesContentPre = document.getElementById('resourcesContent');
            const loadingOverlay = document.getElementById('loadingOverlay'); // Get the fullscreen overlay
            const conversationalAiDiv = document.getElementById('conversational-ai'); // Define it here

            // Reset UI
            errorMessageDiv.style.display = 'none';
            interactiveQuizDiv.style.display = 'none';
            quizSummaryDiv.style.display = 'none';
            resourcesOutputDiv.style.display = 'none';
            quizContentPre.textContent = '';
            resourcesContentPre.textContent = '';
            document.getElementById('feedback').innerHTML = '';
            conversationalAiDiv.style.display = 'none'; // Ensure chat is hidden on new quiz generation
            document.getElementById('chat-history').innerHTML = ''; // Clear chat history

            // Show fullscreen loading overlay
            loadingOverlay.classList.remove('d-none');
            loadingOverlay.classList.add('d-flex');

            currentTopic = document.getElementById('topic').value;
            currentGradeLevel = document.getElementById('grade_level').value;
            const num_questions = document.getElementById('num_questions').value;
            const csrfToken = document.querySelector('input[name="_token"]').value;

            console.log('Sending quiz request with:');
            console.log('Topic:', currentTopic);
            console.log('Grade Level:', currentGradeLevel);
            console.log('Number of Questions:', num_questions);
            console.log('CSRF Token:', csrfToken);

            try {
                const response = await fetch('/quizme', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ topic: currentTopic, grade_level: currentGradeLevel, num_questions })
                });

                const data = await response.json();
                console.log('Backend response data:', data);

                if (response.ok) {
                    // Check if data.quiz is an array and has content
                    if (Array.isArray(data.quiz) && data.quiz.length > 0) {
                        currentQuiz = data.quiz;

                        // Construct full quiz text immediately after successful generation
                        let fullQuizText = '';
                        currentQuiz.forEach((q, index) => {
                            fullQuizText += `Question ${index + 1}: ${q.question_text.replace(/\*\*/g, '')}\n`;
                            for (const optionKey in q.options) {
                                fullQuizText += `${optionKey}) ${q.options[optionKey].replace(/\*\*/g, '')}\n`;
                            }
                            fullQuizText += `Correct Answer: ${q.correct_answer.replace(/\*\*/g, '')}\n\n`;
                        });

                        // Populate download forms with the newly generated quiz content
                        const quizTextContent = document.getElementById('quizTextContent');
                        if (quizTextContent) quizTextContent.value = fullQuizText;
                        const quizPdfContent = document.getElementById('quizPdfContent');
                        if (quizPdfContent) quizPdfContent.value = fullQuizText;
                        const quizTopicName = document.getElementById('quizTopicName');
                        if (quizTopicName) quizTopicName.value = currentTopic;
                        const quizPdfTopicName = document.getElementById('quizPdfTopicName');
                        if (quizPdfTopicName) quizPdfTopicName.value = currentTopic;

                        // Clean ** from resources before setting for display and download
                        const cleanedResources = data.resources.replace(/\*\*/g, '');
                        const resourcesTextContent = document.getElementById('resourcesTextContent');
                        if (resourcesTextContent) resourcesTextContent.value = cleanedResources;
                        const resourcesPdfContent = document.getElementById('resourcesPdfContent');
                        if (resourcesPdfContent) resourcesPdfContent.value = cleanedResources;
                        const resourcesContent = document.getElementById('resourcesContent');
                        if (resourcesContent) resourcesContent.textContent = cleanedResources;
                        resourcesOutputDiv.style.display = 'block';

                        // Set the topic name for download forms
                        const resourcesTopicName = document.getElementById('resourcesTopicName');
                        if (resourcesTopicName) resourcesTopicName.value = currentTopic;
                        const resourcesPdfTopicName = document.getElementById('resourcesPdfTopicName');
                        if (resourcesPdfTopicName) resourcesPdfTopicName.value = currentTopic;

                        currentQuestionIndex = 0; // Start with the first question
                        displayQuestion();
                        interactiveQuizDiv.style.display = 'block';
                    } else {
                        errorMessageDiv.textContent = 'Backend returned an empty or invalid quiz structure.';
                        errorMessageDiv.style.display = 'block';
                        console.error('Backend quiz data is empty or invalid:', data.quiz);
                    }
                } else {
                    errorMessageDiv.textContent = data.error || 'An unknown error occurred.';
                    errorMessageDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Fetch error:', error);
                errorMessageDiv.textContent = 'Network error or service unavailable. Please check console for details.';
                errorMessageDiv.style.display = 'block';
                // Additionally log the error details more thoroughly
                if (error instanceof TypeError) {
                    console.error('TypeError (likely network error or CORS issue): ', error.message);
                } else if (error instanceof SyntaxError) {
                    console.error('SyntaxError (likely JSON parsing error): ', error.message);
                } else {
                    console.error('General fetch error:', error);
                }
            } finally {
                // Hide fullscreen loading overlay and re-enable button in all cases
                loadingOverlay.classList.add('d-none');
                loadingOverlay.classList.remove('d-flex');
                generateBtn.disabled = false;

                // Hide old loading indicators if they were somehow still active
                loadingSpinner.style.display = 'none';
                loadingText.style.display = 'none';
            }
        });

        document.getElementById('submitAnswerBtn').addEventListener('click', async function() {
            const userAnswer = document.getElementById('userAnswer').value.trim();
            const feedbackDiv = document.getElementById('feedback');
            // Prevent numeric answers
            if (!/^[A-Da-d]$/.test(userAnswer)) {
                feedbackDiv.innerHTML = '<span class="text-danger">Please enter only A, B, C, or D as your answer.</span>';
                return;
            }
            const currentQuestion = currentQuiz[currentQuestionIndex];
            const submitAnswerBtn = document.getElementById('submitAnswerBtn');
            const nextQuestionBtn = document.getElementById('nextQuestionBtn');
            const csrfToken = document.querySelector('input[name="_token"]').value;
            const loadingOverlay = document.getElementById('loadingOverlay'); // Get the fullscreen overlay

            // Client-side check for missing correct_answer to prevent backend validation error
            if (!currentQuestion.correct_answer || currentQuestion.correct_answer === "") {
                feedbackDiv.innerHTML = '<span class="text-danger">Error: Correct answer data is missing for this question. Cannot evaluate. Please try generating a new quiz.</span>';
                submitAnswerBtn.disabled = false;
                nextQuestionBtn.style.display = 'inline-block'; // Allow user to move forward
                document.getElementById('conversational-ai').style.display = 'block'; // Show chat even on error
                return;
            }

            submitAnswerBtn.disabled = true; // Disable submit button
            feedbackDiv.innerHTML = 'Evaluating...';

            try {
                const response = await fetch('/quizme/evaluate-answer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        user_answer: userAnswer,
                        question_text: currentQuestion.question_text,
                        options: currentQuestion.options,
                        correct_answer: currentQuestion.correct_answer,
                        topic: currentTopic,
                        grade_level: currentGradeLevel
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    const cleanedFeedback = data.feedback.replace(/\*\*/g, '');
                    feedbackDiv.innerHTML = cleanedFeedback;
                    nextQuestionBtn.style.display = 'inline-block'; // Show next question button

                    // Show conversational AI section after receiving feedback for a question
                    document.getElementById('conversational-ai').style.display = 'block';
                    document.getElementById('chat-history').scrollTop = document.getElementById('chat-history').scrollHeight; // Scroll chat to bottom
                } else {
                    feedbackDiv.innerHTML = 'Error evaluating answer: ' + (data.error || 'Unknown error');
                }
            } catch (error) {
                console.error('Evaluation fetch error:', error);
                feedbackDiv.innerHTML = 'Network error during evaluation.';
            } finally {
                submitAnswerBtn.disabled = false; // Ensure button is re-enabled on success or error
            }
        });

        document.getElementById('nextQuestionBtn').addEventListener('click', function() {
            currentQuestionIndex++;
            document.getElementById('userAnswer').value = ''; // Clear previous answer
            document.getElementById('feedback').innerHTML = ''; // Clear previous feedback
            document.getElementById('submitAnswerBtn').disabled = false; // Re-enable submit
            document.getElementById('nextQuestionBtn').style.display = 'none'; // Hide next button
            displayQuestion();

            // Ensure conversational AI stays open when moving to next question
            document.getElementById('conversational-ai').style.display = 'block';
            document.getElementById('chat-history').scrollTop = document.getElementById('chat-history').scrollHeight; // Scroll chat to bottom
        });

        document.getElementById('sendChatBtn').addEventListener('click', async function() {
            const userQuery = document.getElementById('chatInput').value.trim();
            if (!userQuery) return;

            const chatHistoryDiv = document.getElementById('chat-history');
            const chatInput = document.getElementById('chatInput');
            const sendChatBtn = document.getElementById('sendChatBtn');
            const chatLoadingSpinner = document.getElementById('chatLoadingSpinner');
            const chatLoadingText = document.getElementById('chatLoadingText');
            const csrfToken = document.querySelector('input[name="_token"]').value;
            const loadingOverlay = document.getElementById('loadingOverlay'); // Get the fullscreen overlay

            // Add user message to chat history
            chatHistoryDiv.innerHTML += `<p class="mb-1"><strong>You:</strong> ${userQuery}</p>`;
            chatInput.value = ''; // Clear input
            chatHistoryDiv.scrollTop = chatHistoryDiv.scrollHeight; // Scroll to bottom

            // Show fullscreen loading overlay for chat
            loadingOverlay.classList.remove('d-none');
            loadingOverlay.classList.add('d-flex');

            sendChatBtn.disabled = true; // Disable send button
            chatLoadingSpinner.style.display = 'none'; // Hide specific chat spinner
            chatLoadingText.style.display = 'none'; // Hide specific chat loading text

            try {
                const response = await fetch('/quizme/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        user_query: userQuery,
                        topic: currentTopic, // Pass current topic for context
                        grade_level: currentGradeLevel // Pass current grade level for context
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    const aiResponse = data.response.replace(/\*\*/g, ''); // Clean up any bold markdown
                    chatHistoryDiv.innerHTML += `<p class="mb-1"><strong>CK Quiz Me Follow up AI:</strong> ${aiResponse}</p>`;
                } else {
                    chatHistoryDiv.innerHTML += `<p class="text-danger mb-1"><strong>CK Quiz Me Follow up AI Error:</strong> ${data.error || 'Failed to get response.'}</p>`;
                }
            } catch (error) {
                console.error('Chat fetch error:', error);
                chatHistoryDiv.innerHTML += `<p class="text-danger mb-1"><strong>CK Quiz Me Follow up AI Error:</strong> Network error or service unavailable.</p>`;
            } finally {
                // Hide fullscreen loading overlay
                loadingOverlay.classList.add('d-none');
                loadingOverlay.classList.remove('d-flex');

                sendChatBtn.disabled = false;
                // Re-enable specific chat spinner/text if needed (though now handled by fullscreen)
                // chatLoadingSpinner.style.display = 'none'; 
                // chatLoadingText.style.display = 'none';
                chatHistoryDiv.scrollTop = chatHistoryDiv.scrollHeight; // Scroll to bottom again
            }
        });

        function displayQuestion() {
            const questionTextElem = document.getElementById('questionText');
            const questionOptionsElem = document.getElementById('questionOptions');
            const currentQuestionNumberElem = document.getElementById('currentQuestionNumber');
            const quizSummaryDiv = document.getElementById('quiz-summary');
            const interactiveQuizDiv = document.getElementById('interactive-quiz');
            const nextQuestionBtn = document.getElementById('nextQuestionBtn');
            const submitAnswerBtn = document.getElementById('submitAnswerBtn');
            const conversationalAiDiv = document.getElementById('conversational-ai');

            if (currentQuestionIndex < currentQuiz.length) {
                const question = currentQuiz[currentQuestionIndex];
                currentQuestionNumberElem.textContent = `Question ${currentQuestionIndex + 1} of ${currentQuiz.length}`;
                
                // Remove '**' from question text before displaying
                questionTextElem.textContent = question.question_text.replace(/\*\*/g, '');

                questionOptionsElem.innerHTML = '';
                for (const optionKey in question.options) {
                    const optionValue = question.options[optionKey];
                    const p = document.createElement('p');
                    p.textContent = `${optionKey}) ${optionValue.replace(/\*\*/g, '')}`;
                    questionOptionsElem.appendChild(p);
                }

                // Ensure quiz section is visible and summary is hidden
                interactiveQuizDiv.style.display = 'block';
                quizSummaryDiv.style.display = 'none';
                conversationalAiDiv.style.display = 'block'; // Ensure chat is always visible while quizzing

                // Control visibility of Next Question button
                if (currentQuestionIndex === currentQuiz.length - 1) {
                    nextQuestionBtn.style.display = 'none'; // Hide if it's the last question
                } else {
                    nextQuestionBtn.style.display = 'none'; // Initially hidden, will show after submit
                }
                submitAnswerBtn.style.display = 'inline-block'; // Ensure submit button is visible

            } else {
                // Quiz finished
                interactiveQuizDiv.style.display = 'none';
                quizSummaryDiv.style.display = 'block';
                conversationalAiDiv.style.display = 'block'; // Chat remains visible after quiz completion
                document.getElementById('chat-history').scrollTop = document.getElementById('chat-history').scrollHeight; // Scroll chat to bottom

                let fullQuizText = '';
                currentQuiz.forEach((q, index) => {
                    fullQuizText += `Question ${index + 1}: ${q.question_text.replace(/\*\*/g, '')}\n`;
                    for (const optionKey in q.options) {
                        fullQuizText += `${optionKey}) ${q.options[optionKey].replace(/\*\*/g, '')}\n`;
                    }
                    fullQuizText += `Correct Answer: ${q.correct_answer.replace(/\*\*/g, '')}\n\n`;
                });
                document.getElementById('summaryText').textContent = "You have completed the quiz!";

                // Populate download forms for the entire quiz
                const quizTextContent = document.getElementById('quizTextContent');
                if (quizTextContent) quizTextContent.value = fullQuizText.replace(/\*\*/g, '');
                const quizPdfContent = document.getElementById('quizPdfContent');
                if (quizPdfContent) quizPdfContent.value = fullQuizText.replace(/\*\*/g, '');

                // Set the topic name for download forms for the entire quiz
                const quizTopicName = document.getElementById('quizTopicName');
                if (quizTopicName) quizTopicName.value = currentTopic;
                const quizPdfTopicName = document.getElementById('quizPdfTopicName');
                if (quizPdfTopicName) quizPdfTopicName.value = currentTopic;

                // Move download buttons to the summary section
                const downloadButtonsSummaryDiv = document.getElementById('download-buttons-summary');
                downloadButtonsSummaryDiv.innerHTML = `
                    <form action="{{ route('quizme.download') }}" method="POST" style="display:inline;" class="download-form">
                        @csrf
                        <input type="hidden" name="content" value="${fullQuizText.replace(/\*\*/g, '')}">
                        <input type="hidden" name="filename" value="quiz">
                        <input type="hidden" name="format" value="txt">
                        <input type="hidden" name="topic_name" value="${currentTopic}">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Save Quiz as Text</button>
                    </form>
                    <form action="{{ route('quizme.download') }}" method="POST" style="display:inline;" class="download-form">
                        @csrf
                        <input type="hidden" name="content" value="${fullQuizText.replace(/\*\*/g, '')}">
                        <input type="hidden" name="filename" value="quiz">
                        <input type="hidden" name="format" value="pdf">
                        <input type="hidden" name="topic_name" value="${currentTopic}">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Save Quiz as PDF</button>
                    </form>
                `;
            }
        }

        document.getElementById('loadExemplarBtn').addEventListener('click', function() {
            document.getElementById('topic').value = 'Ancient Egypt';
            document.getElementById('grade_level').value = '5th Grade';
            document.getElementById('num_questions').value = '10';
        });

        document.getElementById('clearInputsBtn').addEventListener('click', function() {
            document.getElementById('topic').value = '';
            document.getElementById('grade_level').value = 'Pre-K';
            document.getElementById('num_questions').value = '10';
            document.getElementById('interactive-quiz').style.display = 'none';
            document.getElementById('quiz-summary').style.display = 'none';
            document.getElementById('resources-output').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('feedback').innerHTML = '';
            document.getElementById('conversational-ai').style.display = 'none';
            document.getElementById('chat-history').innerHTML = '';
            currentQuiz = [];
            currentQuestionIndex = 0;
            currentTopic = '';
            currentGradeLevel = '';
            document.getElementById('loadingOverlay').classList.add('d-none');
            document.getElementById('loadingOverlay').classList.remove('d-flex');
        });
    </script>

</body>

</html>
