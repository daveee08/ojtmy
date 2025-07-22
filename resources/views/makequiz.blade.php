<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CK Virtual Tutor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fdfdfd;
        }

        .quiz-toggle {
            position: fixed;
            bottom: 20px;
            right: 80px;
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
            position: relative;
        }

        #submit-quiz-btn:hover {
            background: #d81b60;
        }

        .ai-message {
            background: #f1f1f1;
            padding: 14px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 14px;
        }

        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-left: 6px;
            vertical-align: middle;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <button class="quiz-toggle" title="Create Quiz">
        <i class="fas fa-pen"></i>
    </button>

    <div class="chatbot-quiz-section">
        <div class="chatbot-header">
            Create a Quiz
            <button class="chatbot-close-quiz" title="Close Quiz">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chatbot-body" id="quiz-body">
            <div class="quiz-question-block">
                <label for="quiz-type" style="font-weight: bold;">Quiz Type / Format:</label>
                <select id="quiz-type">
                    <option value="multiple choice">Multiple Choice</option>
                    <option value="true or false">True or False</option>
                    <option value="short answer">Short Answer</option>
                    <option value="fill in the blank">Fill in the Blank</option>
                </select>
            </div>

            <div class="quiz-question-block">
                <label for="num-questions" style="font-weight: bold;">Number of Questions:</label>
                <input type="number" id="num-questions" min="1" max="50" value="5">
            </div>

            <div class="quiz-question-block">
                <label for="difficulty" style="font-weight: bold;">Difficulty Level:</label>
                <select id="difficulty">
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>

            <div class="quiz-question-block">
                <label for="grade-level" style="font-weight: bold;">Target Grade / Learner Level:</label>
                <input type="text" id="grade-level" placeholder="e.g. Grade 6, High School">
            </div>

            <div class="quiz-question-block">
                <label for="include-answers" style="font-weight: bold;">Include Answer Key:</label>
                <select id="include-answers">
                    <option value="true">Yes</option>
                    <option value="false">No</option>
                </select>
            </div>
        </div>

        <div class="chatbot-footer" id="quiz-footer">
            <button id="submit-quiz-btn" type="submit">
                <span id="submitText">Generate Quiz</span>
                <span id="loadingSpinner" class="spinner" style="display:none;"></span>
            </button>
        </div>
    </div>

    <script>
        const quizToggle = document.querySelector('.quiz-toggle');
        const quizSection = document.querySelector('.chatbot-quiz-section');
        const closeQuizButton = document.querySelector('.chatbot-close-quiz');
        const submitButton = document.getElementById('submit-quiz-btn');
        const submitText = document.getElementById('submitText');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const quizFooter = document.getElementById('quiz-footer');
        const quizBody = document.getElementById('quiz-body');

        quizToggle.addEventListener('click', () => {
            quizSection.classList.add('open');
            quizToggle.style.display = 'none';
            toggleButton.style.display = 'none';
        });

        closeQuizButton.addEventListener('click', () => {
            quizSection.classList.remove('open');
            quizToggle.style.display = 'block';
            toggleButton.style.display = 'block';
        });

        submitButton.addEventListener('click', async () => {
            // Show loading
            submitButton.disabled = true;
            submitText.textContent = 'Generating...';
            loadingSpinner.style.display = 'inline-block';

            const quizType = document.getElementById('quiz-type').value;
            const numQuestions = parseInt(document.getElementById('num-questions').value);
            const difficulty = document.getElementById('difficulty').value;
            const gradeLevel = document.getElementById('grade-level').value;
            const includeAnswers = document.getElementById('include-answers').value === 'true';

            const bookId = "{{ request('book_id') ?? 1 }}";
            const unitId = "{{ request('unit_id') ?? 1 }}";
            const chapterId = "{{ request('chapter_id') ?? 1 }}";

            try {
                const response = await fetch(`/generate-quiz`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        quiz_type: quizType,
                        number_of_questions: numQuestions,
                        difficulty_level: difficulty,
                        grade_level: gradeLevel,
                        answer_key: includeAnswers,
                        book_id: bookId,
                        unit_id: unitId,
                        chapter_id: chapterId
                    })
                });

                const result = await response.json();

                submitButton.disabled = false;
                submitText.textContent = 'Generate Quiz';
                loadingSpinner.style.display = 'none';

                if (!response.ok || !result.quiz) {
                    alert("❌ Failed to generate quiz.");
                    return;
                }

                document.querySelectorAll('.quiz-question-block').forEach(block => block.style.display =
                    'none');

                const message = document.createElement('div');
                message.className = 'ai-message';
                message.innerHTML = Array.isArray(result.quiz) ?
                    result.quiz.map(q =>
                        `<div>${q.question}${q.answer ? `<br><strong>Answer:</strong> ${q.answer}` : ''}</div>`)
                    .join('<hr>') :
                    result.quiz;
                quizBody.appendChild(message);

                quizFooter.innerHTML = `
                    <button id="restart-btn" style="flex:1; background:#2196F3; color:white; border:none; padding:10px 16px; border-radius:6px; cursor:pointer;">
                        <i class="fas fa-rotate-left"></i> Restart
                    </button>
                    <button id="download-btn" style="flex:1; background:#4CAF50; color:white; border:none; padding:10px 16px; border-radius:6px; cursor:pointer;">
                        <i class="fas fa-download"></i> Download PDF
                    </button>
                `;

                document.getElementById('restart-btn').addEventListener('click', () => {
                    document.querySelectorAll('.quiz-question-block').forEach(block => block.style
                        .display = 'block');
                    message.remove();
                    quizFooter.innerHTML = '';
                    quizFooter.appendChild(submitButton);
                });

                document.getElementById('download-btn').addEventListener('click', () => {
                    alert("📥 PDF download feature coming soon.");
                });

            } catch (err) {
                console.error('❌ JS Error generating quiz:', err);
                alert("❌ Error generating quiz.");
                submitButton.disabled = false;
                submitText.textContent = 'Generate Quiz';
                loadingSpinner.style.display = 'none';
            }
        });
    </script>
</body>

</html>
