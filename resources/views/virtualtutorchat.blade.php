@extends('layouts.bootstrap')
@include('chatbot')

<!-- @extends('layouts.chatnavbar') -->
@extends('layouts.header')

@section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css" />
    <style>
        :root {
            --sidebar-width: 240px;
            --chat-bg: #f9fafb;
            --user-bubble: #d1f3ff;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--chat-bg);
            margin: 0;
            padding: 0;
            overflow-y: hidden;
        }

        .chat-container {
            position: fixed;
            top: 0;
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
            text-align: center;
            border-bottom: 1px solid #2d3748;
        }

        .chat-body {
            flex: 1;
            overflow-y: auto; /* Changed from hidden to allow scrolling if needed */
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
            font-size: 1rem;
            line-height: 1.6;
            max-width: 100%;
            box-shadow: none;
        }

        .ai-message * {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.4 !important;
        }

        .ai-message h1 {
            font-size: 1.6rem;
            font-weight: bold;
        }

        .ai-message h2 {
            font-size: 1.4rem;
            font-weight: bold;
        }

        .ai-message h3 {
            font-size: 1.25rem;
            font-weight: bold;
        }

        .ai-message h4 {
            font-size: 1.1rem;
            font-weight: bold;
        }

        .ai-message h5 {
            font-size: 1rem;
            font-weight: bold;
        }

        .ai-message h6 {
            font-size: 0.95rem;
            font-weight: bold;
        }

        .ai-message ul,
        .ai-message ol {
            margin-left: 1.5rem;
            padding-left: 1rem !important;
        }

        .ai-message li {
            margin-bottom: 0.4rem;
        }

        .ai-message blockquote {
            border-left: 4px solid #ccc;
            padding-left: 1rem;
            color: #555;
            font-style: italic;
            margin: 1rem 0;
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

        .ai-message th,
        .ai-message td {
            border: 1px solid #ccc;
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
            background-color: #f0f0f0;
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
        #pdfEmbedContainer {
            width: 100%;
            height: calc(100vh - 80px); /* Adjusted height with proper unit */
            overflow: auto; /* Keeps PDF scrollable */
            margin-top: 20px; /* Added px unit */
        }
        #pdfEmbed {
            min-height: 600px;
            height: 100%;
            width: 100%;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 8px;
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
    <div class="chat-body" id="chatBody">
    <div id="pdfEmbedContainer" style="display: none;">
    <embed id="pdfEmbed" src="" height="100%" width="100%" type="application/pdf" style="border: 1px solid #ddd; border-radius: 8px; min-height: 600px;">    </div>
    <div id="chatMessages"></div> <!-- Placeholder for chat messages if needed -->
</div>
<script>
   document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const pdfUrl = urlParams.get('pdf_url');
    const pdfEmbed = document.getElementById('pdfEmbed');
    const pdfEmbedContainer = document.getElementById('pdfEmbedContainer');
    if (pdfUrl) {
        try {
            pdfEmbed.src = decodeURIComponent(pdfUrl) + '#toolbar=0'; // Hides toolbar
            pdfEmbedContainer.style.display = 'block';
        } catch (e) {
            console.error('Failed to set PDF src:', e);
        }
    } else {
        console.log('No pdf_url parameter found');
        pdfEmbedContainer.style.display = 'none'; // Hide if no URL
    }
});
</script>
@endsection
    