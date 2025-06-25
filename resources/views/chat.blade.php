<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous" />
    <style>
        body {
            background: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-container {
            display: flex;
            flex-direction: column;
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            height: 80vh;
            border: 1.5px solid #ccc;
            /* 👈 Add this line */
        }

        .chat-header {
            padding: 1rem;
            text-align: center;
            background-color: #191919;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 1px;
        }

        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            padding: 1rem 1.25rem;
            border-radius: 16px;
            max-width: 75%;
            font-size: 1rem;
            line-height: 1.5;
        }

        .user-message {
            background-color: #ecf0f1;
            align-self: flex-end;
            text-align: right;
        }

        .ai-message {
            background-color: #f9e3e6;
            align-self: flex-start;
        }

        .chat-footer {
            padding: 1rem;
            border-top: 1px solid #eee;
            background-color: #F5F5F5;
            display: flex;
            gap: 1rem;
        }

        .chat-footer textarea {
            resize: none;
            flex: 1;
            border-radius: 8px;
            border: 1.5px solid #ccc;
            padding: 0.75rem 1rem;
            font-size: 1rem;
        }

        .chat-footer button {
            background-color: #191919;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .chat-footer button:disabled {
            opacity: 0.6;
        }

        #loading-spinner {
            display: none;
            align-self: center;
        }

        .spinner-border.text-pink {
            color: #EC298B;
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <div class="chat-header">
            Chat
        </div>

        <div class="chat-body" id="chatBody">
            <!-- Chat messages will be appended here -->
        </div>

        <div class="chat-footer">
            <textarea id="userInput" rows="1" placeholder="Type your message..."></textarea>
            <button id="sendBtn">Send</button>
        </div>
    </div>

    <!-- Optional Spinner -->
    <div id="loading-spinner" class="mt-3 text-center">
        <div class="spinner-border text-pink" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script>
        const chatBody = document.getElementById('chatBody');
        const userInput = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');
        const spinner = document.getElementById('loading-spinner');

        function appendMessage(message, type) {
            const msgDiv = document.createElement('div');
            msgDiv.classList.add('message', type === 'user' ? 'user-message' : 'ai-message');
            msgDiv.textContent = message;
            chatBody.appendChild(msgDiv);
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message) return;

            appendMessage(message, 'user');
            userInput.value = '';
            sendBtn.disabled = true;
            spinner.style.display = 'block';

            try {
                const response = await fetch('/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        message
                    })
                });

                const data = await response.json();
                appendMessage(data.reply || '[No response]', 'ai');
            } catch (err) {
                appendMessage('[Error contacting server]', 'ai');
            }

            sendBtn.disabled = false;
            spinner.style.display = 'none';
        }

        sendBtn.addEventListener('click', sendMessage);

        userInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
</body>

</html>
