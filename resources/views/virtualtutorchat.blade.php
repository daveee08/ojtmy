@extends('layouts.chatnavbar')
@extends('layouts.header')
@extends('chatbot')
@extends('makequiz')

@section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css" />
    <style>
        :root {
            --sidebar-width: 240px;

            --primary-hue: 330;
            --primary-accent: hsl(var(--primary-hue), 80%, 50%);
            --primary-accent-hover: hsl(var(--primary-hue), 80%, 40%);

            --dark-text: hsl(220, 10%, 15%);
            --light-text: hsl(220, 5%, 45%);

            --bg-light: hsl(0, 0%, 100%);
            --bg-lighter: hsl(220, 15%, 98%);
            --bg-lightest: hsl(220, 15%, 96%);

            --chat-bg: var(--bg-lighter);
            --user-bubble: hsl(190, 100%, 90%);
            --secondary-bg: var(--bg-light);
            --header-bg: hsl(220, 20%, 15%);
            --border-color: hsl(220, 10%, 90%);
            --strong-border-color: hsl(220, 10%, 85%);

            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--chat-bg);
            margin: 0;
            padding: 0;
            overflow: hidden;
            color: var(--dark-text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .chat-container {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: column; /* Changed to column to stack header, body, footer */
            background: var(--bg-light);
            border-left: 1px solid var(--border-color);
            height: 100vh;
            transition: left 0.3s ease-in-out;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.05);
        }

        body.sidebar-collapsed .chat-container {
            left: 70px;
        }

        .chat-header {
            padding: 1rem 1.5rem;
            background-color: var(--header-bg);
            color: white;
            font-size: 1.6rem;
            font-weight: 700;
            text-align: center;
            border-bottom: 1px solid var(--strong-border-color);
            box-shadow: var(--shadow-sm);
            z-index: 10;
            flex-shrink: 0; /* Ensures header always takes its height */
        }

        .chat-body {
            display: flex;
            flex: 1; /* Allows chat-body to take remaining vertical space */
            overflow: hidden;
            padding: 1.5rem 2rem; /* Keep existing horizontal and vertical padding here */
            background-color: var(--chat-bg);
            gap: 2rem;
        }

        .chat-chapter {
            flex: 1;
            max-width: 50%;
            overflow: hidden; /* This needs to be on the pdf-overall-wrapper now */
            background-color: transparent; /* Changed to transparent if overall-wrapper handles background */
            padding: 0; /* No padding here, handled by pdf-overall-wrapper */
            border: none; /* No border here, handled by pdf-overall-wrapper */
            border-radius: 0; /* No border-radius here */
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: none; /* No shadow here */
        }

        .chat-messages {
            flex: 1;
            max-width: 50%;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            scroll-behavior: smooth;
            padding-right: 0.5rem;
        }

        .chat-messages::-webkit-scrollbar,
        .chat-chapter::-webkit-scrollbar,
        #pdf-viewer::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .chat-messages::-webkit-scrollbar-track,
        .chat-chapter::-webkit-scrollbar-track,
        #pdf-viewer::-webkit-scrollbar-track {
            background: var(--bg-lighter);
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb,
        .chat-chapter::-webkit-scrollbar-thumb,
        #pdf-viewer::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover,
        .chat-chapter::-webkit-scrollbar-thumb:hover,
        #pdf-viewer::-webkit-scrollbar-thumb:hover {
            background: hsl(220, 10%, 75%);
        }


        .message {
            max-width: 75%;
            padding: 1rem 1.25rem;
            border-radius: 16px;
            font-size: 1rem;
            line-height: 1.6;
            word-wrap: break-word;
            white-space: pre-wrap;
            box-shadow: var(--shadow-sm);
        }

        .user-message {
            background-color: var(--user-bubble);
            align-self: flex-end;
            color: var(--dark-text);
            border-bottom-right-radius: 6px;
        }

        .ai-message {
            background-color: transparent;
            color: var(--dark-text);
            padding: 0;
            font-size: 1rem;
            line-height: 1.6;
            max-width: 100%;
            box-shadow: none;
            align-self: flex-start;
        }

        .ai-message * {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1.5 !important;
        }

        .ai-message p {
            margin-bottom: 0.8em !important;
        }

        .ai-message h1,
        .ai-message h2,
        .ai-message h3,
        .ai-message h4,
        .ai-message h5,
        .ai-message h6 {
            margin-top: 1.5rem !important;
            margin-bottom: 0.8rem !important;
            line-height: 1.2 !important;
            color: var(--dark-text);
        }

        .ai-message h1 {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .ai-message h2 {
            font-size: 1.6rem;
            font-weight: 700;
        }

        .ai-message h3 {
            font-size: 1.35rem;
            font-weight: 600;
        }

        .ai-message h4 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .ai-message h5 {
            font-size: 1.05rem;
            font-weight: 600;
        }

        .ai-message h6 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--light-text);
        }

        .ai-message ul,
        .ai-message ol {
            margin-left: 2rem !important;
            padding-left: 0 !important;
            margin-top: 0.5rem !important;
            margin-bottom: 0.8rem !important;
        }

        .ai-message li {
            margin-bottom: 0.6rem;
            line-height: 1.6 !important;
        }

        .ai-message blockquote {
            border-left: 5px solid var(--primary-accent);
            padding-left: 1.2rem;
            color: var(--light-text);
            font-style: italic;
            margin: 1.2rem 0;
            background-color: var(--bg-lighter);
            border-radius: 4px;
            padding-top: 0.8rem;
            padding-bottom: 0.8rem;
        }

        .ai-message hr {
            border: none;
            border-top: 1px solid var(--border-color);
            margin: 1.5rem 0;
        }

        .ai-message table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            font-size: 0.95rem;
        }

        .ai-message th,
        .ai-message td {
            border: 1px solid var(--border-color);
            padding: 0.8rem;
            text-align: left;
        }
        .ai-message th {
            background-color: var(--bg-lightest);
            font-weight: 600;
        }

        .ai-message code:not(pre code) {
            background: hsl(0, 0%, 95%);
            color: var(--primary-accent);
            padding: 0.3em 0.5em;
            border-radius: 5px;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.9em;
        }

        .ai-message pre {
            background: #282c34;
            color: #abb2bf;
            padding: 1.2rem;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.9rem;
            margin: 1.5rem 0;
            box-shadow: var(--shadow-sm);
        }
        .ai-message pre code {
            padding: 0 !important;
            background: none !important;
            color: inherit !important;
        }


        .chat-footer {
            padding: 1rem 1.5rem;
            background-color: var(--bg-lightest);
            display: flex;
            align-items: center;
            gap: 1rem;
            border-top: 1px solid var(--strong-border-color);
            box-shadow: var(--shadow-sm);
            z-index: 10;
            flex-shrink: 0; /* Ensures footer always takes its height */
        }

        .chat-footer textarea {
            resize: none;
            flex: 1;
            border-radius: 10px;
            padding: 0.85rem 1.25rem;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            height: 50px;
            line-height: 1.5;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .chat-footer textarea:focus {
            outline: none;
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 3px hsla(var(--primary-hue), 80%, 50%, 0.2);
        }

        .chat-footer button {
            background-color: var(--primary-accent);
            color: white;
            padding: 0.85rem 1.75rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .chat-footer button:hover {
            background-color: var(--primary-accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .chat-footer button:active {
            transform: translateY(0);
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.15);
        }

        .chat-footer button:disabled {
            background-color: hsl(220, 10%, 75%);
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        /* --- PDF Viewer Styles (Adjusted for Top Controls) --- */

        .pdf-overall-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            height: 100%; /* Ensure it takes full height within chat-chapter */
            margin-top: 2.5%;
        }

        .pdf-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 1.5rem;
            background-color: var(--bg-lightest);
            border-bottom: 1px solid var(--border-color);
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            box-shadow: var(--shadow-sm);
            flex-shrink: 0;
        }

        .pdf-control-button {
            color: var(--dark-text);
            border: none;
            width: 44px;
            height: 44px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
            background-color: var(--bg-lightest);
        }

        .pdf-control-button:hover {
            color: #e91e63;
        }

        .pdf-control-button:active {
            transform: translateY(0);
            box-shadow: inset 0 1px 5px rgba(0, 0, 0, 0.1);
        }

        .pdf-control-button:disabled {
            background-color: hsl(220, 15%, 96%);
            color: hsl(220, 5%, 55%);
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .pdf-page-info {
            font-weight: 600;
            color: var(--dark-text);
            margin: 0 0.5rem;
            white-space: nowrap;
            font-size: 1rem;
        }

        .zoom-level-display {
            font-weight: 600;
            color: var(--dark-text);
            margin: 0 0.5rem;
            min-width: 60px;
            text-align: center;
            font-size: 1rem;
        }

        #pdf-viewer {
            width: 100%;
            flex-grow: 1;
            overflow-y: auto;
            background: var(--bg-light);
            border: none;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        #pdf-viewer canvas {
            display: block;
            margin: 0 auto 1.5rem auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            background-color: #fff;
        }

        .no-pdf-message {
            color: var(--light-text);
            text-align: center;
            padding: 2rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            background-color: var(--bg-light);
            border-radius: 12px;
            border: 1px dashed var(--border-color);
            /* margin: 1rem; Removed as pdf-overall-wrapper now has the border/shadow */
        }
        #pdf-viewer > p {
            color: var(--primary-accent);
            text-align: center;
            padding-top: 20px;
            font-weight: 500;
        }


        @media (max-width: 1024px) {
            .chat-body {
                padding: 1rem;
                gap: 1.5rem;
            }
            .chat-chapter,
            .chat-messages {
                max-width: 48%;
            }
        }

        @media (max-width: 768px) {
            :root {
                --sidebar-width: 0;
            }

            .chat-container {
                left: 0;
                border-left: none;
            }

            body.sidebar-collapsed .chat-container {
                left: 0;
            }

            .chat-body {
                flex-direction: column;
                padding: 1rem;
                gap: 1.5rem;
            }

            .chat-chapter,
            .chat-messages {
                max-width: 100%;
                height: 50vh;
                flex-shrink: 0;
                min-height: 250px;
            }
            .chat-messages {
                order: 2;
            }
            .chat-chapter {
                order: 1;
                /* margin-top: 0.5rem; Removed, will be handled by chat-body's gap */
            }


            .message {
                max-width: 90%;
            }

            .chat-footer {
                flex-direction: column;
                padding: 0.75rem;
                gap: 0.75rem;
            }

            .chat-footer textarea {
                height: 45px;
                padding: 0.65rem 1rem;
            }

            .chat-footer button {
                width: 100%;
                padding: 0.65rem 1.5rem;
            }

            .pdf-control-button {
                width: 38px;
                height: 38px;
                font-size: 1.1rem;
            }
            .pdf-controls {
                padding: 0.6rem 0.8rem;
                gap: 0.6rem;
            }
            .pdf-page-info, .zoom-level-display {
                font-size: 0.9rem;
            }

            .no-pdf-message {
                margin: 0; /* Remove margin on mobile as it's already inside a bounded container */
                border-radius: 0; /* Remove border-radius on mobile if the parent container already has it */
            }
        }

        @media (max-width: 480px) {
            .chat-header {
                font-size: 1.3rem;
                padding: 0.8rem 1rem;
            }
            .chat-body {
                padding: 0.75rem;
                gap: 1rem;
            }
            .chat-chapter, .chat-messages {
                min-height: 200px;
                height: 48vh;
            }
            .chat-footer {
                padding: 0.5rem;
            }
            .pdf-control-button {
                width: 34px;
                height: 34px;
                font-size: 1rem;
            }
            .pdf-controls {
                gap: 0.4rem;
            }
        }
    </style>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pdfUrl = "{{ asset('storage/' . ($lesson->pdf_path ?? '')) }}";
            const pdfViewerContainer = document.getElementById('pdf-viewer');
            const prevPageBtn = document.getElementById('prevPageBtn');
            const nextPageBtn = document.getElementById('nextPageBtn');
            const zoomInBtn = document.getElementById('zoomInBtn');
            const zoomOutBtn = document.getElementById('zoomOutBtn');
            const currentPageSpan = document.getElementById('currentPage');
            const totalPagesSpan = document.getElementById('totalPages');
            const zoomLevelSpan = document.getElementById('zoomLevel');

            let pdfDoc = null;
            let pageNum = 1;
            let pageRendering = false;
            let pageNumPending = null;
            let scale = 1.0;

            const ZOOM_STEP = 0.2;
            const MIN_SCALE = 0.5;
            const MAX_SCALE = 3.0;

            function togglePdfControls(disable) {
                const buttons = [prevPageBtn, nextPageBtn, zoomInBtn, zoomOutBtn];
                buttons.forEach(btn => {
                    if (btn) btn.disabled = disable;
                });
            }

            if (!pdfUrl) {
                pdfViewerContainer.innerHTML = '<p style="color: var(--primary-accent); text-align: center; padding-top: 20px;">No lesson PDF available.</p>';
                togglePdfControls(true);
                return;
            }

            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.worker.min.js';

            function renderPage(num) {
                pageRendering = true;
                while (pdfViewerContainer.firstChild) {
                    pdfViewerContainer.removeChild(pdfViewerContainer.firstChild);
                }

                pdfDoc.getPage(num).then(function(page) {
                    const viewport = page.getViewport({
                        scale: scale
                    });
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');

                    const outputScale = window.devicePixelRatio || 1;
                    canvas.height = Math.floor(viewport.height * outputScale);
                    canvas.width = Math.floor(viewport.width * outputScale);
                    canvas.style.width = Math.floor(viewport.width) + 'px';
                    canvas.style.height = Math.floor(viewport.height) + 'px';

                    context.scale(outputScale, outputScale);

                    canvas.style.display = "block";
                    canvas.style.margin = "0 auto 1.5rem auto";
                    canvas.style.boxShadow = "0 2px 10px rgba(0,0,0,0.1)";
                    canvas.style.borderRadius = "6px";
                    canvas.style.backgroundColor = "#fff";

                    pdfViewerContainer.appendChild(canvas);

                    const renderContext = {
                        canvasContext: context,
                        viewport: viewport,
                    };
                    const renderTask = page.render(renderContext);

                    renderTask.promise.then(function() {
                        pageRendering = false;
                        if (pageNumPending !== null) {
                            renderPage(pageNumPending);
                            pageNumPending = null;
                        }
                        updatePageControls();
                        updateZoomLevelDisplay();
                    }).catch(function(error) {
                        console.error("Error rendering PDF page:", error);
                        pdfViewerContainer.innerHTML = `<p style="color: var(--primary-accent); text-align: center; padding-top: 20px;">Error rendering page: ${error.message}</p>`;
                        togglePdfControls(true);
                    });
                });
            }

            function queueRenderPage(num) {
                if (pageRendering) {
                    pageNumPending = num;
                } else {
                    renderPage(num);
                }
            }

            function updatePageControls() {
                if (prevPageBtn) prevPageBtn.disabled = pageNum <= 1 || pageRendering;
                if (nextPageBtn) nextPageBtn.disabled = pageNum >= pdfDoc.numPages || pageRendering;
                if (currentPageSpan) currentPageSpan.textContent = pageNum;
                if (totalPagesSpan) totalPagesSpan.textContent = pdfDoc.numPages;

                if (zoomInBtn) zoomInBtn.disabled = scale >= MAX_SCALE || pageRendering;
                if (zoomOutBtn) zoomOutBtn.disabled = scale <= MIN_SCALE || pageRendering;
            }

            function updateZoomLevelDisplay() {
                if (zoomLevelSpan) {
                    zoomLevelSpan.textContent = `${Math.round(scale * 100)}%`;
                }
            }

            function onPrevPage() {
                if (pageNum <= 1 || pageRendering) {
                    return;
                }
                pageNum--;
                queueRenderPage(pageNum);
            }

            function onNextPage() {
                if (pageNum >= pdfDoc.numPages || pageRendering) {
                    return;
                }
                pageNum++;
                queueRenderPage(pageNum);
            }

            function onZoomIn() {
                if (scale < MAX_SCALE && !pageRendering) {
                    scale = Math.min(MAX_SCALE, scale + ZOOM_STEP);
                    queueRenderPage(pageNum);
                }
            }

            function onZoomOut() {
                if (scale > MIN_SCALE && !pageRendering) {
                    scale = Math.max(MIN_SCALE, scale - ZOOM_STEP);
                    queueRenderPage(pageNum);
                }
            }

            if (prevPageBtn) prevPageBtn.addEventListener('click', onPrevPage);
            if (nextPageBtn) nextPageBtn.addEventListener('click', onNextPage);
            if (zoomInBtn) zoomInBtn.addEventListener('click', onZoomIn);
            if (zoomOutBtn) zoomOutBtn.addEventListener('click', onZoomOut);

            togglePdfControls(true);

            pdfjsLib.getDocument(pdfUrl).promise.then(function(pdfDoc_) {
                pdfDoc = pdfDoc_;
                if (totalPagesSpan) totalPagesSpan.textContent = pdfDoc.numPages;

                renderPage(pageNum);
                togglePdfControls(false);

            }).catch(function(error) {
                pdfViewerContainer.innerHTML = `<p style="color: var(--primary-accent); text-align: center; padding-top: 20px;">Failed to load PDF: ${error.message}</p>`;
                console.error("Error loading PDF:", error);
                togglePdfControls(true);
            });
        });
    </script>
@endsection

@section('pdf')
    <div class="pdf-overall-wrapper">
        @if ($lesson && $lesson->pdf_path)
            <div class="pdf-controls">
                <button id="prevPageBtn" class="pdf-control-button" title="Previous Page">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="pdf-page-info">
                    Page <span id="currentPage">1</span> of <span id="totalPages"></span>
                </span>
                <button id="nextPageBtn" class="pdf-control-button" title="Next Page">
                    <i class="fas fa-chevron-right"></i>
                </button>

                <div class="controls-separator"></div>

                <button id="zoomOutBtn" class="pdf-control-button" title="Zoom Out">
                    <i class="fas fa-minus"></i>
                </button>
                <span id="zoomLevel" class="zoom-level-display">100%</span>
                <button id="zoomInBtn" class="pdf-control-button" title="Zoom In">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <div id="pdf-viewer"></div>
        @else
            <p class="no-pdf-message">No lesson PDF available.</p>
        @endif
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endsection