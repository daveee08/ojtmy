@extends('layouts.bootstrap')

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
            --pink-light-tint: rgba(233, 30, 99, 0.1);
            --white: #ffffff;
            --dark: #191919;
            --light-grey: #f5f5f5;
            --text-dark-grey: #333333;
            --text-medium-grey: #555555;
            --text-light-grey: #888888;

            --indent-base: 18px;
            --indent-level-1: 36px;
            --indent-level-2: 54px;
            --indent-level-3: 72px;
            --active-border-width: 4px;
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
            width: 300px;
            background-color: var(--white);
            padding: 40px 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            transition: width 0.3s ease-in-out, padding 0.3s ease-in-out;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--pink-light-tint) var(--light-grey);
        }

        .sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: var(--light-grey);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--pink-light-tint);
            border-radius: 10px;
            border: 2px solid var(--light-grey);
        }

        .sidebar.collapsed {
            width: 70px;
            padding: 40px 10px;
        }

        .sidebar h2 {
            font-size: 1.6rem;
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
            color: var(--text-dark-grey);
            text-decoration: none;
            margin: 8px 0;
            font-size: 0.95rem;
            padding: 10px 15px;
            border-radius: 8px;
            transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease, border-left 0.25s ease;
            position: relative;
            will-change: transform;
        }

        .sidebar.collapsed a {
            justify-content: center;
            padding-left: 10px;
            padding-right: 10px;
        }

        .sidebar a i {
            margin-right: 12px;
            font-size: 1.1rem;
            min-width: 20px;
            width: 20px;
            text-align: center;
            color: var(--text-medium-grey);
            transition: margin 0.3s ease, font-size 0.3s ease, color 0.25s ease, transform 0.25s ease;
        }

        .sidebar.collapsed a i {
            margin-right: 0;
        }

        .link-text {
            display: inline-block;
            opacity: 1;
            width: auto;
            max-width: 200px;
            white-space: nowrap;
            transition: opacity 0.3s ease, max-width 0.3s ease;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar.collapsed .link-text {
            opacity: 0;
            max-width: 0;
            padding: 0;
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
            width: 100%;
        }

        body.sidebar-collapsed .content {

        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        .layout {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .sidebar a:hover {
            background-color: var(--pink-light-tint);
            color: var(--text-dark-grey);
            transform: translateX(3px);
        }

        .sidebar a:hover i {
            color: var(--pink);
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
            background-color: var(--pink-light-tint);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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

        .sidebar.collapsed .hide-when-collapsed {
            display: none !important;
        }

        .glow {background-color: var(--pink-light-tint) !important;
            color: var(--text-dark-grey) !important;
            font-weight: 600;
            border-left: var(--active-border-width) solid var(--pink);
            transform: none !important;
        }

        .glow i {
            color: var(--pink) !important;
        }

        .sidebar .book-link {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .sidebar .book-link i {
            font-size: 1.4rem;
            color: var(--text-medium-grey);
        }

        .sidebar .unit-link {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-medium-grey);
        }

        .sidebar .unit-link i,
        .sidebar .chapter-link i {
            font-size: 0.9rem;
            margin-right: 8px;
            transition: transform 0.25s ease, color 0.25s ease;
            color: var(--text-medium-grey);
        }

        .sidebar .chapter-link {
            font-size: 0.95rem;
            font-weight: 400;
            color: var(--text-medium-grey);
        }

        .sidebar .lesson-link {
            font-size: 0.9rem;
            color: var(--text-light-grey);
            margin: 6px 0;
        }

        .lesson-wrap {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding-right: 12px;
            flex-grow: 1;
        }

        .lesson-wrap .lesson-icon {
            flex-shrink: 0;
            margin-top: 5px;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: var(--text-light-grey);
            transition: background-color 0.25s ease;
        }

        .lesson-link.glow .lesson-icon {
            background-color: var(--pink) !important;
        }

        .lesson-wrap .lesson-text {
            white-space: normal;
            word-wrap: break-word;
            flex: 1;
        }

        .sidebar .bi-chevron-right.rotated {
            transform: rotate(90deg);
        }

        .book-level-item {
            padding-left: var(--indent-base);
        }
        .unit-level-item {
            padding-left: var(--indent-level-1);
        }
        .chapter-level-item {
            padding-left: var(--indent-level-2);
        }
        .lesson-level-item {
            padding-left: var(--indent-level-3);
        }

        .book-level-item.glow {
            padding-left: calc(var(--indent-base) - var(--active-border-width));
        }
        .unit-level-item.glow {
            padding-left: calc(var(--indent-level-1) - var(--active-border-width));
        }
        .chapter-level-item.glow {
            padding-left: calc(var(--indent-level-2) - var(--active-border-width));
        }
        .lesson-level-item.glow {
            padding-left: calc(var(--indent-level-3) - var(--active-border-width));
        }

        .sidebar .ps-3, .sidebar .ps-4 {
            padding-left: 0 !important;
        }
        .sidebar .unit-container,
        .sidebar .chapter-container,
        .sidebar .lesson-container {
            margin-left: 18px;
        }
        .sidebar .unit-container {
            margin-left: 18px;
        }
        .sidebar .chapter-container {
            margin-left: 18px;
        }
        .sidebar .lesson-container {
            margin-left: 18px;
        }

    </style>
</head>

<body>
    @php
        $currentBookId = request('book_id');
        $currentUnitId = request('unit_id');
        $currentChapterId = request('chapter_id');
        $currentLessonId = request('lesson_id');
    @endphp

    <button id="toggleSidebar">☰</button>

    <div class="layout">
        <div class="sidebar" id="sidebar">
            <h2></h2>
            @foreach ($books as $book)
                <div class="mb-2">
                    <a href="javascript:void(0);"
                        class="book-link book-level-item {{ $book->id == $currentBookId ? 'glow' : '' }}"
                        onclick="toggleUnits({{ $book->id }}, this)">
                        <i class="bi bi-journal-bookmark"></i>
                        <span class="link-text">{{ $book->title }}</span>
                    </a>
                    <div id="units-{{ $book->id }}" class="collapse unit-container"
                        style="{{ $book->id == $currentBookId ? 'display:block;' : 'display:none;' }}">
                        @php $units = DB::table('units')->where('book_id', $book->id)->orderBy('unit_number')->get(); @endphp
                        @foreach ($units as $unit)
                            <a href="javascript:void(0);"
                                onclick="toggleChapters({{ $unit->id }}, this)"
                                class="unit-link unit-level-item {{ $unit->id == $currentUnitId ? 'glow' : '' }}">
                                <i class="bi bi-chevron-right unit-chapter-icon {{ $unit->id == $currentUnitId ? 'rotated' : '' }}"></i>
                                <span class="link-text">Unit {{ $unit->unit_number }}: {{ $unit->title }}</span>
                            </a>
                            <div id="chapters-{{ $unit->id }}" class="collapse chapter-container"
                                style="{{ $unit->id == $currentUnitId ? 'display:block;' : 'display:none;' }}">
                                @php
                                    $chapters = DB::table('chapter')
                                        ->where('unit_id', $unit->id)
                                        ->orderBy('chapter_number')
                                        ->get();
                                @endphp
                                @foreach ($chapters as $chapter)
                                    <a href="javascript:void(0);"
                                        onclick="toggleLessons({{ $chapter->id }}, this)"
                                        class="chapter-link chapter-level-item {{ $chapter->id == $currentChapterId ? 'glow' : '' }}">
                                        <i class="bi bi-chevron-right unit-chapter-icon {{ $chapter->id == $currentChapterId ? 'rotated' : '' }}"></i>
                                        <span class="link-text">Chapter {{ $chapter->chapter_number }}: {{ $chapter->chapter_title }}</span>
                                    </a>
                                    <div id="lessons-{{ $chapter->id }}" class="collapse lesson-container"
                                        style="{{ $chapter->id == $currentChapterId ? 'display:block;' : 'display:none;' }}">
                                        @php
                                            $lessons = DB::table('lesson')
                                                ->where('chapter_id', $chapter->id)
                                                ->orderBy('lesson_number')
                                                ->get();
                                        @endphp
                                        @foreach ($lessons as $lesson)
                                            <a href="{{ url('/virtual-tutor-chat') }}?book_id={{ $book->id }}&unit_id={{ $unit->id }}&chapter_id={{ $chapter->id }}&lesson_id={{ $lesson->id }}"
                                                class="lesson-link lesson-level-item {{ $lesson->id == $currentLessonId ? 'glow' : '' }}">
                                                <div class="lesson-wrap">
                        
                                                    <span class="lesson-text">Lesson {{ $lesson->lesson_number }}: {{ $lesson->lesson_title }}</span>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="content" id="mainContent">
            @yield('pdf')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleContent(elementId, clickedElement) {
            const el = document.getElementById(elementId);
            const isCurrentlyVisible = el.style.display === 'block';

            const parent = el.closest('.unit-container, .chapter-container, .sidebar');
            if (parent) {
                Array.from(parent.children).forEach(child => {
                    if (child.classList.contains('collapse') && child.id !== elementId) {
                        child.style.display = 'none';
                        const siblingIcon = child.previousElementSibling ? child.previousElementSibling.querySelector('.unit-chapter-icon') : null;
                        if (siblingIcon) {
                            siblingIcon.classList.remove('rotated');
                        }
                    }
                });
            }

            el.style.display = isCurrentlyVisible ? 'none' : 'block';

            const icon = clickedElement.querySelector('.unit-chapter-icon');
            if (icon) {
                icon.classList.toggle('rotated', !isCurrentlyVisible);
            }
        }

        function toggleUnits(bookId, clickedElement) {
            toggleContent(`units-${bookId}`, clickedElement);
        }

        function toggleChapters(unitId, clickedElement) {
            toggleContent(`chapters-${unitId}`, clickedElement);
        }

        function toggleLessons(chapterId, clickedElement) {
            toggleContent(`lessons-${chapterId}`, clickedElement);
        }

        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const body = document.body;

            sidebar.classList.toggle('collapsed');
            body.classList.toggle('sidebar-collapsed');

            if (sidebar.classList.contains('collapsed')) {
                const allCollapsibles = sidebar.querySelectorAll('.collapse');
                allCollapsibles.forEach(item => {
                    item.style.display = 'none';
                });

                const allIcons = sidebar.querySelectorAll('.unit-chapter-icon');
                allIcons.forEach(icon => {
                    icon.classList.remove('rotated');
                });
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const currentLessonLink = document.querySelector('.lesson-link.glow');
            if (currentLessonLink) {
                let currentElement = currentLessonLink;
                while (currentElement) {
                    currentElement = currentElement.parentElement;
                    if (currentElement && currentElement.classList.contains('collapse')) {
                        currentElement.style.display = 'block';
                        const parentLink = currentElement.previousElementSibling;
                        if (parentLink) {
                            const icon = parentLink.querySelector('.unit-chapter-icon');
                            if (icon) {
                                icon.classList.add('rotated');
                            }
                        }
                    } else if (currentElement && currentElement.classList.contains('book-link')) {
                        const bookId = currentElement.onclick.toString().match(/toggleUnits\((\d+)/)?.[1];
                        if (bookId) {
                            const unitsContainer = document.getElementById(`units-${bookId}`);
                            if (unitsContainer) {
                                unitsContainer.style.display = 'block';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>