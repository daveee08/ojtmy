@extends('layouts.bootstrap')
@extends('layouts.historysidenav')
@extends('layouts.header')

@section('styles')
    <style>
        :root {
            --sidebar-width: 240px;
            --header-height: 0px;
            --chat-bg: #f9fafb;
            --user-bubble: #d1f3ff;
            --ai-bubble: #ffe4ec;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--chat-bg);
            margin: 0;
            padding: 0;
        }

        .chat-container {
            position: fixed;
            top: var(--header-height);
            left: var(--sidebar-width);
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            background: white;
            border-left: 1px solid #e5e7eb;
            height: 100vh;
            transition: left 0.3s;
        }

        body.sidebar-collapsed .chat-container {
            left: 70px;
        }

        .chat-header {
            padding: 1rem 1.5rem;
            background-color: #1a202c;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #2d3748;
        }

        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            background-color: var(--chat-bg);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            scroll-behavior: smooth;
        }

        .message {
            max-width: 75%;
            padding: 0.8rem 1.25rem;
            border-radius: 16px;
            line-height: 1.6;
            font-size: 1rem;
            word-wrap: break-word;
            position: relative;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .user-message {
            background-color: var(--user-bubble);
            align-self: flex-end;
            color: #1a202c;
            border-bottom-right-radius: 4px;
        }

        .ai-message {
            background-color: var(--ai-bubble);
            align-self: flex-start;
            color: #4a1c1c;
            border-bottom-left-radius: 4px;
        }

        .chat-footer {
            padding: 1rem;
            background-color: #F0F0F0;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-top: 1px solid #d9e2ec;
        }

        .chat-footer textarea {
            resize: none;
            flex: 1;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid #cbd5e0;
            transition: border-color 0.2s;
            height: 50px;
        }

        .chat-footer textarea:focus {
            border-color: ##F5F5F5;
        }

        .chat-footer button {
            background-color: #e91e63;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.2s, transform 0.1s;
        }

        .chat-footer button:hover {
            background-color: #2c5282;
            transform: translateY(-1px);
        }

        .chat-footer button:disabled {
            background-color: #a0aec0;
            cursor: not-allowed;
        }

        .spinner {
            display: none;
            align-items: center;
            justify-content: center;
            height: 40px;
            color: #2b6cb0;
            font-size: 0.9rem;
        }

        .spinner.show {
            display: flex;
        }

        @media (max-width: 768px) {
            :root {
                --sidebar-width: 0;
            }

            .chat-container {
                left: 0;
            }

            .chat-body {
                padding: 1rem;
                gap: 0.75rem;
            }

            .message {
                max-width: 90%;
                font-size: 0.95rem;
            }

            .chat-footer {
                flex-direction: column;
                padding: 0.75rem;
            }

            .chat-footer button {
                width: 100%;
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
@endsection

@section('scripts')
    <script>
        const chatBody = document.getElementById('chatBody');
        const userInput = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const sessionID = "{{ $session_id ?? '1' }}";

        function appendMessage(message, type) {
            const msgDiv = document.createElement('div');
            msgDiv.classList.add('message', type === 'user' ? 'user-message' : 'ai-message');
            msgDiv.textContent = message;
            chatBody.appendChild(msgDiv);
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        function toggleSpinner(show) {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) overlay.style.display = show ? 'flex' : 'none';
        }

        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message) return;

            appendMessage(message, 'user');
            userInput.value = '';
            sendBtn.disabled = true;
            toggleSpinner(true);

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

                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

                const data = await response.json();
                appendMessage(data.response, 'ai');
            } catch (err) {
                appendMessage(`[Error: ${err.message}]`, 'ai');
            } finally {
                sendBtn.disabled = false;
                toggleSpinner(false);
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
            toggleSpinner(true);
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
                toggleSpinner(false);
            }
        })();
    </script>
@endsection
