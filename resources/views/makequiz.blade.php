<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CK Virtual Tutor</title>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
                <label for="quiz-type"><strong>Quiz Type / Format:</strong></label>
                <select id="quiz-type">
                    <option value="multiple choice">Multiple Choice</option>
                    <option value="true or false">True or False</option>
                    <option value="short answer">Short Answer</option>
                    <option value="fill in the blank">Fill in the Blank</option>
                </select>
            </div>

            <div class="quiz-question-block">
                <label for="num-questions"><strong>Number of Questions:</strong></label>
                <input type="number" id="num-questions" min="1" max="50" value="5">
            </div>

            <div class="quiz-question-block">
                <label for="difficulty"><strong>Difficulty Level:</strong></label>
                <select id="difficulty">
                    <option value="easy">Easy</option>
                    <option value="medium">Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>

            <div class="quiz-question-block">
                <label for="grade-level"><strong>Target Grade / Learner Level:</strong></label>
                <input type="text" id="grade-level" placeholder="e.g. Grade 6, High School">
            </div>

            <div class="quiz-question-block">
                <label for="include-answers"><strong>Include Answer Key:</strong></label>
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
        const qs = (s) => document.querySelector(s);
        const qsa = (s) => document.querySelectorAll(s);
        const [quizSection, quizToggle, quizFooter, quizBody, submitBtn, submitText, spinner] = [
            '.chatbot-quiz-section', '.quiz-toggle', '#quiz-footer', '#quiz-body', '#submit-quiz-btn', '#submitText',
            '#loadingSpinner'
        ].map(qs);

        const bookId = "{{ request('book_id', 1) }}";
        const unitId = "{{ request('unit_id', 1) }}";
        const chapterId = "{{ request('chapter_id', 1) }}";

        quizToggle.onclick = () => {
            quizSection.classList.add('open');
            quizToggle.style.display = 'none';
            toggleButton.style.display = 'none';


        };

        qs('.chatbot-close-quiz').onclick = () => {
            quizSection.classList.remove('open');
            quizToggle.style.display = 'block';
            toggleButton.style.display = 'block';

        };

        const fetchQuizCheck = () =>
            fetch('http://localhost:5001/quiz-check', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    book_id: +bookId,
                    chapter_number: +chapterId
                })
            }).then(res => res.json());

        const displayQuiz = (quiz) => {
            // Hide previous question blocks
            qsa('.quiz-question-block').forEach(b => b.style.display = 'none');

            // Create a <pre> tag for raw text display with HTML support
            const msg = document.createElement('pre');
            msg.className = 'ai-message';
            msg.style.cssText = `
        white-space: pre-wrap;
        font-family: monospace;
        padding: 16px;
        border-radius: 6px;
        line-height: 1.5;
        font-size: 14px;
        margin-bottom: 20px;
    `;

            // Format: Bold any line starting with a digit followed by ". "
            const bolded = quiz
                .split('\n')
                .map(line => /^\d+\.\s/.test(line) ? `<strong>${line}</strong>` : line)
                .join('\n');

            msg.innerHTML = bolded;

            // Clear previous content and append the new quiz
            quizBody.innerHTML = '';
            quizBody.appendChild(msg);

            quizFooter.innerHTML = `
        <button id="restart-btn" style="flex:1; background:#2196F3; color:white; border:none; padding:10px 16px; border-radius:6px; cursor:pointer;">
            <i class="fas fa-rotate-left"></i> Restart
        </button>
        <button id="download-btn" style="flex:1; background:#4CAF50; color:white; border:none; padding:10px 16px; border-radius:6px; cursor:pointer;">
            <i class="fas fa-download"></i> Download PDF
        </button>`;

            qs('#restart-btn').onclick = async () => {
                try {
                    await fetch('http://localhost:5001/delete-quiz', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            book_id: +bookId,
                            chapter_number: +chapterId
                        })
                    });
                } catch (err) {
                    console.error("❌ Error deleting quiz:", err);
                    alert("❌ Error deleting quiz.");
                } finally {
                    quizBody.innerHTML = '';
                    location.reload();
                }
            };

            qs('#download-btn').onclick = () => {
                const quizContent = qs('.ai-message');
                if (!quizContent) return alert("❌ No quiz to download.");

                const opt = {
                    margin: 0.5,
                    filename: 'quiz.pdf',
                    image: {
                        type: 'jpeg',
                        quality: 0.98
                    },
                    html2canvas: {
                        scale: 2
                    },
                    jsPDF: {
                        unit: 'in',
                        format: 'letter',
                        orientation: 'portrait'
                    }
                };

                html2pdf().set(opt).from(quizContent).save();
            };
        };

        submitBtn.onclick = async () => {
            submitBtn.disabled = true;
            submitText.textContent = 'Generating...';
            spinner.style.display = 'inline-block';

            const data = {
                quiz_type: qs('#quiz-type').value,
                number_of_questions: +qs('#num-questions').value,
                difficulty_level: qs('#difficulty').value,
                grade_level: qs('#grade-level').value,
                answer_key: qs('#include-answers').value === 'true',
                book_id: bookId,
                unit_id: unitId,
                chapter_id: chapterId
            };

            try {
                const check = await fetchQuizCheck();
                if (check.quiz) return displayQuiz(check.quiz);

                const res = await fetch('/generate-quiz', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': qs('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                });

                const result = await res.json();
                if (!res.ok || !result.quiz) return alert("❌ Failed to generate quiz.");
                displayQuiz(result.quiz);

            } catch (err) {
                console.error('❌ Error:', err);
                alert("❌ Error generating quiz.");
            } finally {
                submitBtn.disabled = false;
                submitText.textContent = 'Generate Quiz';
                spinner.style.display = 'none';
            }
        };

        window.addEventListener('DOMContentLoaded', async () => {
            try {
                const check = await fetchQuizCheck();
                if (check.quiz?.message) displayQuiz(check.quiz.message);
            } catch (err) {
                console.error("❌ Error checking quiz:", err);
            }
        });
    </script>
</body>

</html>
