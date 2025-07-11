@extends('layouts.bootstrap')
@extends('layouts.historysidenav')
@extends('layouts.header')

@section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css" />
    <style>
        :root {
            --sidebar-width: 240px;
            --header-height: 0px;
            --chat-bg: #f9fafb;
            --user-bubble: #d1f3ff;
            --ai-bubble: #f7f7f8;
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
            padding: 1.5rem 12rem;
            background-color: var(--chat-bg);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            scroll-behavior: smooth;
        }

        .message {
            max-width: 75%;
            padding: 1rem;
            border-radius: 12px;
            font-size: 1rem;
            line-height: 1.6;
            word-wrap: break-word;
            position: relative;
            white-space: pre-wrap;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .user-message {
            background-color: var(--user-bubble);
            align-self: flex-end;
            color: #1a202c;
            border-bottom-right-radius: 4px;
        }

        .ai-message {
            background-color: transparent;
            color: #2d2d2d;
            padding: 0;
            margin: 0;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            line-height: 1.6;
            max-width: 100%;
            text-align: left;
            box-shadow: none;
        }

        .ai-message * {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.4 !important;
        }

        .ai-message p,
        .ai-message li,
        .ai-message blockquote,
        .ai-message h1,
        .ai-message h2,
        .ai-message h3,
        .ai-message h4,
        .ai-message h5,
        .ai-message h6 {
            margin: 0 !important;
            padding: 0 !important;
        }

        .ai-message h1,
        .ai-message h2,
        .ai-message h3,
        .ai-message h4,
        .ai-message h5,
        .ai-message h6 {
            font-weight: bold;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .ai-message h1 {
            font-size: 1.6rem;
        }

        .ai-message h2 {
            font-size: 1.4rem;
        }

        .ai-message h3 {
            font-size: 1.25rem;
        }

        .ai-message h4 {
            font-size: 1.1rem;
        }

        .ai-message h5 {
            font-size: 1rem;
        }

        .ai-message h6 {
            font-size: 0.95rem;
        }

        .ai-message ul,
        .ai-message ol {
            margin-left: 1.5rem;
            padding-left: 0.5rem;
            margin: 0 !important;
            padding-left: 1rem !important;
            /* keep indentation if needed */
        }

        .ai-message li {
            margin-bottom: 0.4rem;
        }

        .ai-message strong {
            font-weight: 700;
        }

        .ai-message em {
            font-style: italic;
        }

        .ai-message del {
            text-decoration: line-through;
        }

        .ai-message blockquote {
            border-left: 4px solid #ccc;
            padding-left: 1rem;
            color: #555;
            margin: 1rem 0;
            font-style: italic;
        }

        .ai-message hr {
            border: none;
            border-top: 1px solid #ccc;
            margin: 1rem 0;
        }

        .ai-message table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .ai-message table,
        .ai-message th,
        .ai-message td {
            border: 1px solid #ccc;
        }

        .ai-message th,
        .ai-message td {
            padding: 0.5rem;
            text-align: left;
        }

        .ai-message code {
            background: #f0f0f0;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }

        .ai-message pre {
            background: #1e1e1e;
            color: #ffffff;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.95rem;
            margin: 1rem 0;
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
            height: 50px;
        }

        .chat-footer button {
            background-color: #e91e63;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }

        .chat-footer button:disabled {
            background-color: #a0aec0;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            :root {
                --sidebar-width: 0;
            }

            .chat-container {
                left: 0;
            }

            .message {
                max-width: 90%;
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
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>
        const chatBody = document.getElementById('chatBody');
        const userInput = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const sessionID = "{{ $session_id ?? '1' }}";
        const userID = "{{ $user_id ?? 1 }}";

        let lastSender = null;

        function appendMessage(message, type) {
            if (lastSender && lastSender !== type) {
                const divider = document.createElement('hr');
                divider.style.border = 'none';
                divider.style.borderTop = '1px solid lightgray';
                divider.style.margin = '1rem auto';
                divider.style.width = '90%'; // or adjust to 70%, 80%, etc.
                chatBody.appendChild(divider);
            }

            const msgDiv = document.createElement('div');
            msgDiv.classList.add('message', type === 'user' ? 'user-message' : 'ai-message');

            if (type === 'ai') {
                msgDiv.innerHTML = marked.parse(message);
                msgDiv.querySelectorAll('pre code').forEach(block => hljs.highlightElement(block));
            } else {
                msgDiv.textContent = message;
            }

            chatBody.appendChild(msgDiv);
            chatBody.scrollTop = chatBody.scrollHeight;
            lastSender = type;
        }

        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message) return;

            appendMessage(message, 'user');
            userInput.value = '';
            sendBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('user_id', userID);
                formData.append('message_id', sessionID);
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
            }
        }

        sendBtn.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Load chat history
        (async function loadChat() {
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
            }
        })();
    </script>
@endsection
