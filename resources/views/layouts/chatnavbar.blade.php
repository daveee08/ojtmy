<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'CK AI Tools')</title>
    @yield('styles')

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

    <style>
        :root {
            --pink: #e91e63;
            --white: #ffffff;
            --dark: #191919;
            --light-grey: #f5f5f5;
        }

        a {
            text-decoration: none;
        }

        body {
            font-family: 'Poppins', system-ui, sans-serif;
            background-color: var(--white);
            color: var(--dark);
            margin: 0;
        }

        .sidebar {
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: var(--white);
            padding: 40px 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            transition: width 0.3s ease;
        }

        .sidebar.collapsed {
            width: 70px;
            padding: 40px 10px;
        }

        .sidebar h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--pink);
            margin-bottom: 50px;
            text-align: center;
            transition: opacity 0.2s ease;
        }

        .sidebar.collapsed h2 {
            opacity: 0;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            color: var(--dark);
            text-decoration: none;
            margin: 12px 0;
            font-size: 1rem;
            padding: 12px 18px;
            border-radius: 10px;
            transition: background 0.3s ease, color 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed a {
            justify-content: center;
            padding-left: 12px;
            padding-right: 12px;
        }

        .sidebar a i {
            margin-right: 12px;
            font-size: 1.2rem;
            min-width: 24px;
            width: 24px;
            text-align: center;
            transition: margin 0.3s ease, font-size 0.3s ease;
        }

        .sidebar.collapsed a i {
            margin-right: 0;
        }

        .link-text {
            display: inline-block;
            opacity: 1;
            width: auto;
            max-width: 200px;
            transition: opacity 0.3s ease, max-width 0.3s ease;
            overflow: hidden;
        }

        .sidebar.collapsed .link-text {
            opacity: 0;
            max-width: 0;
        }

        .sidebar:not(.collapsed) a[data-bs-toggle="tooltip"] .link-text {
            pointer-events: none;
        }

        .content {
            flex: 1;
            padding: 50px 30px;
            background-color: var(--light-grey);
            transition: all 0.3s ease;
            overflow-x: hidden;
        }

        .layout {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .sidebar a.active-link {
            background-color: rgba(221, 175, 198, 0.15);
        }

        .sidebar a.active-link i {
            color: inherit !important;
        }

        .sidebar a:hover {
            background-color: rgba(221, 175, 198, 0.15);
        }

        #toggleSidebar {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            border: none;
            background: var(--white);
            color: #5a5959;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 1rem;
        }

        #toggleSidebar:hover {
            color: var(--pink);
        }

        .sidebar .form-select {
            font-size: 0.95rem;
            padding: 8px 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .sidebar .form-label {
            font-size: 0.9rem;
            margin-bottom: 6px;
        }

        /* 🔒 Hide certain elements when sidebar is collapsed */
        .sidebar.collapsed .hide-when-collapsed {
            display: none !important;
        }
    </style>
</head>

<body>

    <!-- Toggle Sidebar Button -->
    <button id="toggleSidebar">☰</button>

    <!-- Sidebar + Content Layout -->
    <div class="layout">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <h2></h2>


            {{-- Show chapter select and sessions list only on /virtual_tutor --}}
            @if (request()->is('virtual_tutor_chat/*'))
                <!-- 🔽 Chapter Selector (Hidden on Collapse) -->
                 <div class="mb-4 hide-when-collapsed">
                    <label for="tutorSelect" class="form-label fw-semibold">Choose Chapter:</label>
                    <select class="form-select" id="tutorSelect" onchange="handleChapterChange(this)">
                        <option value="">Loading chapters...</option>
                    </select>
                </div>
    
                <a href="{{ url('/virtual_tutor_chat/new') }}"
                    class="{{ request()->is('virtual_tutor_chat/new') ? 'active-link' : '' }}" data-bs-toggle="tooltip"
                    title="Start New Chat Session">
                    <i class="bi bi-plus-circle"></i>
                    <span class="link-text">New Chat</span>
                </a>

                <!-- 🔽 Chat Label (Hidden on Collapse) -->
                <div class="mt-3">
                    <label class="form-label fw-semibold px-2 hide-when-collapsed">Chat</label>
                    <a href="{{ url('/virtual_tutor/session/1') }}"
                        class="{{ request()->is('virtual_tutor/session/1') ? 'active-link' : '' }}"
                        data-bs-toggle="tooltip" title="Session 1">
                        <i class="bi bi-chat-dots"></i>
                        <span class="link-text">Session 1</span>
                    </a>
                    <a href="{{ url('/virtual_tutor/session/2') }}"
                        class="{{ request()->is('virtual_tutor/session/2') ? 'active-link' : '' }}"
                        data-bs-toggle="tooltip" title="Session 2">
                        <i class="bi bi-chat-dots"></i>
                        <span class="link-text">Session 2</span>
                    </a>
                    <a href="{{ url('/virtual_tutor/session/3') }}"
                        class="{{ request()->is('virtual_tutor/session/3') ? 'active-link' : '' }}"
                        data-bs-toggle="tooltip" title="Session 3">
                        <i class="bi bi-chat-dots"></i>
                        <span class="link-text">Session 3</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- Content -->
        <div class="content" id="mainContent">
            @yield('content')
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggleBtn = document.getElementById("toggleSidebar");
        // const sidebar = document.getElementById("sidebar");
        const content = document.getElementById("mainContent");

        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            content.classList.toggle("expanded");
        });

        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(el => {
            new bootstrap.Tooltip(el);
        });

        // function handleChapterChange(select) {
        //     const chapter = select.value;
        //     if (chapter) {
        //         console.log("Chapter selected:", chapter);
        //         // You can add logic here
        //     }
        // }

        function handleChapterChange(select) {
            const chapter_number = select.value;
            if (!chapter_number) return;

            const bookId = "{{ $book_id }}"; // Blade variable from backend
            const url = `http://127.0.0.1:5001/view-chapter?book_id=${bookId}&chapter_number=${chapter_number}`;
            window.open(url, "_blank");
        }

            document.addEventListener('DOMContentLoaded', () => {
        const chapterSelect = document.getElementById('tutorSelect');
        const bookId = "{{ $book_id }}"; // From Laravel route param

        fetch("http://127.0.0.1:5001/chapters", {
            method: "POST",
            headers: {
                "Accept": "application/json",
            },
            body: (() => {
                const form = new FormData();
                form.append("book_id", bookId);
                return form;
            })()
        })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') {
                chapterSelect.innerHTML = `<option value="">Failed to load chapters</option>`;
                return;
            }

            chapterSelect.innerHTML = `<option value="">Select Chapter</option>`;
            data.chapters.forEach(chap => {
                const option = document.createElement('option');
                option.value = chap.chapter_number;
                option.textContent = `Chapter ${chap.chapter_number}: ${chap.chapter_title}`;
                chapterSelect.appendChild(option);
            });
        })
        .catch(err => {
            console.error("Error loading chapters:", err);
            chapterSelect.innerHTML = `<option value="">Error loading chapters</option>`;
        });
    });

        
    </script>

</body>

</html>
