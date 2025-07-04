<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat</title>
    <!-- Using Tailwind CSS for modern styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            /* Light gray background */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem; /* Mobile-first padding */
        }

        .chat-container {
            display: flex;
            flex-direction: column;
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 8px; /* Slightly smaller border-radius for mobile */
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); /* Lighter shadow for mobile */
            /* More pronounced shadow */
            height: 90vh; /* Make it take more height on mobile */
            border: 1px solid #e5e7eb;
            /* Lighter border */
            margin-top: 1rem; /* Adjusted margin for mobile */
        }

        .chat-header {
            padding: 1rem;
            text-align: center;
            background-color: #1a202c;
            /* Darker header */
            color: white;
            font-weight: 700;
            font-size: 1.5rem; /* Adjusted font size for mobile */
            /* Larger font size */
            border-bottom: 1px solid #2d3748;
        }

        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem; /* Adjusted padding for mobile */
            display: flex;
            flex-direction: column;
            gap: 0.75rem; /* Adjusted gap for mobile */
            background-color: #ffffff;
        }

        .message {
            padding: 0.6rem 1rem; /* Adjusted padding for mobile */
            border-radius: 16px;
            max-width: 90%; /* Increase max-width for better mobile readability */
            font-size: 0.9rem; /* Adjusted font size for mobile */
            line-height: 1.5;
            word-wrap: break-word;
            /* Ensure long words break */
        }

        .user-message {
            background-color: #e0f2f7;
            /* Light blue */
            align-self: flex-end;
            text-align: right;
            color: #2c5282;
            /* Darker text */
        }

        .ai-message {
            background-color: #ffe0f0;
            /* Light pink */
            align-self: flex-start;
            color: #7b341e;
            /* Darker text */
        }

        .chat-footer {
            padding: 0.75rem; /* Adjusted padding for mobile */
            border-top: 1px solid #e2e8f0;
            background-color: #f7fafc;
            /* Lighter footer */
            display: flex;
            flex-direction: column; /* Stack elements on mobile */
            gap: 0.75rem; /* Adjusted gap for mobile */
            align-items: center;
        }

        .chat-footer textarea {
            resize: none;
            flex: 1;
            border-radius: 8px;
            border: 1.5px solid #cbd5e0;
            padding: 0.6rem 1rem; /* Adjusted padding for mobile */
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
            min-height: 48px; /* Ensure touch friendliness */
        }

        .chat-footer textarea:focus {
            border-color: #63b3ed;
            /* Blue on focus */
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.5);
        }

        .chat-footer button {
            background-color: #1a202c;
            /* Dark button */
            color: white;
            border: none;
            padding: 0.75rem 1.5rem; /* Maintain good button size */
            border-radius: 8px; /* Consistent with container */
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            width: 100%; /* Full width for mobile */
        }

        .chat-footer button:hover {
            background-color: #2d3748;
            /* Darker on hover */
            transform: translateY(-1px);
        }

        .chat-footer button:disabled {
            background-color: #a0aec0;
            cursor: not-allowed;
        }

        #loading-spinner {
            display: none;
            margin-top: 1rem; /* Adjusted margin */
        }

        .spinner-border {
            border: 0.25em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border .75s linear infinite;
            width: 2rem;
            height: 2rem;
        }

        @keyframes spinner-border {
            to {
                transform: rotate(360deg);
            }
        }

        .text-pink-500 {
            color: #ec4899;
            /* Tailwind pink-500 equivalent */
        }

        /* Responsive adjustments */
        @media (min-width: 768px) {
            body {
                padding: 20px;
            }
            .chat-container {
                height: 80vh;
                margin-top: 20px;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            }
            .chat-header {
                font-size: 1.75rem;
            }
            .chat-body {
                padding: 1.5rem;
                gap: 1rem;
            }
            .message {
                max-width: 75%;
                padding: 0.75rem 1.25rem;
                font-size: 1rem;
            }
            .chat-footer {
                flex-direction: row; /* Revert to row for larger screens */
                gap: 1rem;
                padding: 1rem;
            }
            .chat-footer textarea {
                padding: 0.75rem 1rem;
            }
            .chat-footer button {
                width: auto; /* Revert to auto width for larger screens */
                padding: 0.75rem 1.5rem;
                border-radius: 10px;
            }
            #loading-spinner {
                margin-top: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="text-center w-full max-w-md">
        <input type="text" id="sessionInput"
            class="form-control w-full mx-auto p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out"
            placeholder="Enter session ID..." />
        <button
            class="bg-gray-800 text-white font-semibold py-2 px-4 rounded-lg mt-2 shadow hover:bg-gray-700 transition duration-150 ease-in-out"
            onclick="loadSession()">Load Session</button>
    </div>

    <div class="chat-container">
        <div class="chat-header">Chat</div>
        <div class="chat-body" id="chatBody"></div>
        <div class="chat-footer">
            <textarea id="userInput" rows="1" placeholder="Type your message..."></textarea>
            <button id="sendBtn">Send</button>
        </div>
    </div>

    <div id="loading-spinner" class="mt-3 text-center">
        <div class="spinner-border text-pink-500" role="status">
        </div>
    </div>

    <script>
        const chatBody = document.getElementById('chatBody');
        const userInput = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');
        const sessionInput = document.getElementById('sessionInput');
        const spinner = document.getElementById('loading-spinner');

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let messageID = '';

        function appendMessage(message, type) {
            const msgDiv = document.createElement('div');
            msgDiv.classList.add('message', type === 'user' ? 'user-message' : 'ai-message');
            msgDiv.textContent = message;
            chatBody.appendChild(msgDiv);
            chatBody.scrollTop = chatBody.scrollHeight; // Auto-scroll to bottom
        }

        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message || !messageID) {
                // Instead of alert, could display a temporary message in UI
                console.warn("Message or session ID is empty.");
                return;
            }

            appendMessage(message, 'user');
            userInput.value = '';
            sendBtn.disabled = true;
            spinner.style.display = 'block';

            try {
                const formData = new FormData();
                formData.append('topic', message);
                formData.append('message_id', messageID);

                const response = await fetch('/chat', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
                }

                const data = await response.json();
                appendMessage(data.response, 'ai');
            } catch (err) {
                console.error('Error sending message:', err);
                appendMessage(`[Error: ${err.message || 'Could not contact server or process response.'}]`, 'ai');
            } finally {
                sendBtn.disabled = false;
                spinner.style.display = 'none';
            }
        }

        async function loadSession() {
            messageID = sessionInput.value.trim();
            if (!messageID) {
                // Replaced alert with a console warning and a temporary UI message
                console.warn("Please enter a session ID.");
                chatBody.innerHTML = '<div class="message ai-message">Please enter a session ID to load history.</div>';
                return;
            }

            chatBody.innerHTML = ''; // Clear existing messages
            spinner.style.display = 'block';

            try {
                const response = await fetch(`/chat/history/${messageID}`);

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
                }

                const data = await response.json();
                if (data.history && data.history.length > 0) {
                    data.history.forEach(msg => {
                        appendMessage(msg.content, msg.type === 'human' ? 'user' : 'ai');
                    });
                } else {
                    appendMessage('[No chat history found for this session. Start a new conversation!]', 'ai');
                }
            } catch (err) {
                console.error('Error loading chat history:', err);
                appendMessage(`[Failed to load chat history: ${err.message || 'Server error.'}]`, 'ai');
            } finally {
                spinner.style.display = 'none';
            }
        }

        // Event Listeners
        sendBtn.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) { // Send on Enter, allow Shift+Enter for new line
                e.preventDefault();
                sendMessage();
            }
        });

        // Optionally, load a default session or prompt for one on page load
        // window.onload = () => {
        //     // You could set a default session ID here for testing
        //     // sessionInput.value = 'default_session_id';
        //     // loadSession();
        // };
    </script>
</body>

</html>
