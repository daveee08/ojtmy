@extends('layouts.bootstrap')
@extends('layouts.historysidenav')
@extends('layouts.header')

@section('title', 'Chat')

@section('styles')
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .chat-container {
            display: flex;
            flex-direction: column;
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            height: 80vh;
            border: 1px solid #e5e7eb;
            margin-top: 20px;
        }

        .chat-header {
            padding: 1rem;
            text-align: center;
            background-color: #1a202c;
            color: white;
            font-weight: 700;
            font-size: 1.75rem;
            border-bottom: 1px solid #2d3748;
        }

        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background-color: #ffffff;
        }

        .message {
            padding: 0.75rem 1.25rem;
            border-radius: 16px;
            max-width: 75%;
            font-size: 1rem;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .user-message {
            background-color: #e0f2f7;
            align-self: flex-end;
            text-align: right;
            color: #2c5282;
        }

        .ai-message {
            background-color: #ffe0f0;
            align-self: flex-start;
            color: #7b341e;
        }

        .chat-footer {
            padding: 1rem;
            border-top: 1px solid #e2e8f0;
            background-color: #f7fafc;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .chat-footer textarea {
            resize: none;
            flex: 1;
            border-radius: 8px;
            border: 1.5px solid #cbd5e0;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .chat-footer textarea:focus {
            border-color: #63b3ed;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.5);
        }

        .chat-footer button {
            background-color: #1a202c;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }

        .chat-footer button:hover {
            background-color: #2d3748;
            transform: translateY(-1px);
        }

        .chat-footer button:disabled {
            background-color: #a0aec0;
            cursor: not-allowed;
        }

        #loading-spinner {
            display: none;
            margin-top: 20px;
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
        }

        @media (max-width: 768px) {
            .chat-container {
                height: 90vh;
                margin-top: 10px;
                border-radius: 8px;
            }

            .chat-body {
                padding: 1rem;
                gap: 0.75rem;
            }

            .message {
                max-width: 90%;
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .chat-footer {
                flex-direction: column;
                gap: 0.75rem;
                padding: 0.75rem;
            }

            .chat-footer button {
                width: 100%;
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="chat-container">
        <div class="chat-header">Chat</div>
        <div class="chat-body" id="chatBody"></div>
        <div class="chat-footer">
            <textarea id="userInput" rows="1" placeholder="Type your message..."></textarea>
            <button id="sendBtn">Send</button>
        </div>
    </div>

    <div id="loading-spinner" class="mt-3 text-center">
        <div class="spinner-border text-pink-500" role="status"></div>
    </div>

    <script>
        const chatBody = document.getElementById('chatBody');
        const userInput = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');
        const spinner = document.getElementById('loading-spinner');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const sessionID = "{{ $session_id ?? '1' }}"; // Default fallback if not passed

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
                const formData = new FormData();
                formData.append('user_id', "{{ $user_id ?? 1 }}");
                formData.append('message_id', "{{ $session_id }}");
                formData.append('input', message);

                const response = await fetch('/chat', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const data = await response.json();
                appendMessage(data.response, 'ai');
            } catch (err) {
                appendMessage(`[Error: ${err.message}]`, 'ai');
            } finally {
                sendBtn.disabled = false;
                spinner.style.display = 'none';
            }
        }

        sendBtn.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        (async function loadChat() {
            spinner.style.display = 'block';
            try {
                const res = await fetch(`/chat/api/history/${sessionID}`);
                const data = await res.json();

                if (data.conversation?.length) {
                    data.conversation.forEach(msg => {
                        appendMessage(msg.topic, msg.sender === 'human' ? 'user' : 'ai');
                    });
                } else {
                    appendMessage('[No previous messages. Start chatting!]', 'ai');
                }
            } catch (err) {
                appendMessage(`[Could not load history: ${err.message}]`, 'ai');
            } finally {
                spinner.style.display = 'none';
            }
        })();
    </script>
@endsection
