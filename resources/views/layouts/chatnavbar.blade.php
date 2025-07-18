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

        .glow {
            background-color: #ffe3f0 !important;
            color: #d63384 !important;
            font-weight: 600;
            border-left: 4px solid #d63384;
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

    <!-- Toggle Sidebar Button -->
    <button id="toggleSidebar">☰</button>

    <!-- Sidebar + Content Layout -->
    <div class="layout">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <h2></h2>


            {{-- Show chapter select and sessions list only on /virtual_tutor --}}
            @php
                use Illuminate\Support\Facades\DB;
                $books = DB::table('book')->orderBy('grade_level')->get();
            @endphp

            @foreach ($books as $book)
                <div class="mb-2">
                    <a href="javascript:void(0);" class="fw-bold {{ $book->id == $currentBookId ? 'glow' : '' }}"
                        onclick="toggleUnits({{ $book->id }})">
                        <i class="bi bi-journal-bookmark"></i>
                        <span class="link-text">{{ $book->title }}</span>
                    </a>
                    <div id="units-{{ $book->id }}" class="ps-3"
                        style="{{ $book->id == $currentBookId ? 'display:block;' : 'display:none;' }}">
                        @php
                            $units = DB::table('units')->where('book_id', $book->id)->orderBy('unit_number')->get();
                        @endphp
                        @foreach ($units as $unit)
                            <a href="javascript:void(0);" onclick="toggleChapters({{ $unit->id }})"
                                class="text-muted d-block ps-3 {{ $unit->id == $currentUnitId ? 'glow' : '' }}">
                                ▸ Unit {{ $unit->unit_number }}: {{ $unit->title }}
                            </a>
                            <div id="chapters-{{ $unit->id }}" class="ps-4"
                                style="{{ $unit->id == $currentUnitId ? 'display:block;' : 'display:none;' }}">

                                @php
                                    $chapters = DB::table('chapter')
                                        ->where('unit_id', $unit->id)
                                        ->orderBy('chapter_number')
                                        ->get();
                                @endphp
                                @foreach ($chapters as $chapter)
                                    <a href="javascript:void(0);" onclick="toggleLessons({{ $chapter->id }})"
                                        class="d-block ps-2 text-secondary {{ $chapter->id == $currentChapterId ? 'glow' : '' }}">
                                        ▹ Chapter {{ $chapter->chapter_number }}: {{ $chapter->chapter_title }}
                                    </a>
                                    <div id="lessons-{{ $chapter->id }}" class="ps-4"
                                        style="{{ $chapter->id == $currentChapterId ? 'display:block;' : 'display:none;' }}">

                                        @php
                                            $lessons = DB::table('lesson')
                                                ->where('chapter_id', $chapter->id)
                                                ->orderBy('lesson_number')
                                                ->get();
                                        @endphp
                                        @foreach ($lessons as $lesson)
                                            <a href="{{ url('/virtual-tutor-chat') }}?book_id={{ $book->id }}&unit_id={{ $unit->id }}&chapter_id={{ $chapter->id }}&lesson_id={{ $lesson->id }}"
                                                class="d-block text-muted ps-3 {{ $lesson->id == $currentLessonId ? 'glow' : '' }}">
                                                📘 Lesson {{ $lesson->lesson_number }}: {{ $lesson->lesson_title }}
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

        <!-- Content -->
        <div class="content" id="mainContent">
            @yield('pdf')
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function toggleUnits(bookId) {
            const el = document.getElementById(`units-${bookId}`);
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }

        function toggleChapters(unitId) {
            const el = document.getElementById(`chapters-${unitId}`);
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }

        function toggleLessons(chapterId) {
            const el = document.getElementById(`lessons-${chapterId}`);
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
    </script>


</body>

</html>
