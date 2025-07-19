<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CK Virtual Tutor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fdfdfd;
        }

        .chatbot-container {
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            z-index: 1000;
        }

        .chatbot-toggle,
        .quiz-toggle {
            position: fixed;
            bottom: 20px;
            background: #E91E63;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 22px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1001;
        }

        .chatbot-toggle {
            right: 20px;
        }

        .quiz-toggle {
            right: 80px;
            background: #E91E63;
        }

        .chatbot-sidebar,
        .chatbot-quiz-section {
            position: fixed;
            top: 75px;
            right: -420px;
            width: 400px;
            height: calc(100vh - 70px);
            background: white;
            border-left: 1px solid #ccc;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            transition: right 0.3s ease;
            z-index: 1000;
        }

        .chatbot-sidebar.open,
        .chatbot-quiz-section.open {
            right: 0;
        }

        .chatbot-header {
            background: #E91E63;
            color: white;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            position: relative;
        }

        .chatbot-close,
        .chatbot-close-quiz {
            position: absolute;
            top: 10px;
            right: 15px;
            background: transparent;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        .chatbot-body {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
        }

        .chatbot-footer {
            padding: 15px;
            border-top: 1px solid #ccc;
            display: flex;
            gap: 10px;
        }

        .chatbot-footer input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .chatbot-footer button {
            background: #E91E63;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }

        .quiz-question-block {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
        }

        .quiz-question-block input,
        .quiz-question-block select {
            width: 100%;
            margin-top: 6px;
            padding: 8px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        #submit-quiz-btn {
            flex: 1;
            background: #E91E63;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        #submit-quiz-btn:hover {
            background: #d81b60;
        }
    </style>
</head>

<body>
    <div class="chatbot-container">
        <!-- Toggle Buttons -->
        <button class="chatbot-toggle" title="Chat with CK Virtual Tutor">
            <i class="fas fa-robot"></i>
        </button>
        <button class="quiz-toggle" title="Create Quiz">
            <i class="fas fa-pen"></i>
        </button>

        <!-- Chatbot Sidebar -->
        <div class="chatbot-sidebar">
            <div class="chatbot-header">
                Ask CK Virtual Tutors
                <button class="chatbot-close" title="Close Chat">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="chatbot-body">
                <p>Welcome! How can I assist you today?</p>
            </div>
            <div class="chatbot-footer">
                <input type="text" placeholder="Type your question...">
                <button>Send</button>
            </div>
        </div>

        <!-- Quiz Sidebar -->
        <div class="chatbot-quiz-section">
            <div class="chatbot-header">
                Create a Quiz
                <button class="chatbot-close-quiz" title="Close Quiz">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="chatbot-body" id="quiz-body">
                <!-- Quiz Type -->
                <div class="quiz-question-block">
                    <label for="quiz-type" style="font-weight: bold;">Quiz Type / Format:</label>
                    <select id="quiz-type">
                        <option value="multiple choice">Multiple Choice</option>
                        <option value="true or false">True or False</option>
                        <option value="short answer">Short Answer</option>
                        <option value="fill in the blank">Fill in the Blank</option>
                    </select>
                </div>

                <!-- Number of Questions -->
                <div class="quiz-question-block">
                    <label for="num-questions" style="font-weight: bold;">Number of Questions:</label>
                    <input type="number" id="num-questions" min="1" max="50" value="5">
                </div>

                <!-- Difficulty Level -->
                <div class="quiz-question-block">
                    <label for="difficulty" style="font-weight: bold;">Difficulty Level:</label>
                    <select id="difficulty">
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>

                <!-- Grade Level -->
                <div class="quiz-question-block">
                    <label for="grade-level" style="font-weight: bold;">Target Grade / Learner Level:</label>
                    <input type="text" id="grade-level" placeholder="e.g. Grade 6, High School">
                </div>

                <!-- Include Answer Key -->
                <div class="quiz-question-block">
                    <label for="include-answers" style="font-weight: bold;">Include Answer Key:</label>
                    <select id="include-answers">
                        <option value="true">Yes</option>
                        <option value="false">No</option>
                    </select>
                </div>
            </div>
            <div class="chatbot-footer">
                <button id="submit-quiz-btn"><i class="fas fa-paper-plane"></i> Submit</button>
            </div>
        </div>
    </div>

    <script>
        const toggleButton = document.querySelector('.chatbot-toggle');
        const quizToggle = document.querySelector('.quiz-toggle');
        const sidebar = document.querySelector('.chatbot-sidebar');
        const quizSection = document.querySelector('.chatbot-quiz-section');
        const closeButton = document.querySelector('.chatbot-close');
        const closeQuizButton = document.querySelector('.chatbot-close-quiz');

        toggleButton.addEventListener('click', () => {
            sidebar.classList.add('open');
            toggleButton.style.display = 'none';
            quizToggle.style.display = 'none';
        });

        closeButton.addEventListener('click', () => {
            sidebar.classList.remove('open');
            toggleButton.style.display = 'block';
            quizToggle.style.display = 'block';
        });

        quizToggle.addEventListener('click', () => {
            quizSection.classList.add('open');
            toggleButton.style.display = 'none';
            quizToggle.style.display = 'none';
        });

        closeQuizButton.addEventListener('click', () => {
            quizSection.classList.remove('open');
            toggleButton.style.display = 'block';
            quizToggle.style.display = 'block';
        });

        document.getElementById('submit-quiz-btn').addEventListener('click', () => {
            const quizType = document.getElementById('quiz-type').value;
            const numQuestions = document.getElementById('num-questions').value;
            const difficulty = document.getElementById('difficulty').value;
            const gradeLevel = document.getElementById('grade-level').value;
            const includeAnswers = document.getElementById('include-answers').value;

            const prompt = `
Generate ${numQuestions} ${quizType} quiz questions for ${gradeLevel} students.
Difficulty: ${difficulty}.
Include Answer Key: ${includeAnswers}.
            `.trim();

            console.log("📘 Prompt to send to backend:\n", prompt);
            // TODO: Send this prompt to your FastAPI or chatbot backend.
        });
    </script>
</body>
</html>
