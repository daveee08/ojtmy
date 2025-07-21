@extends('layouts.header')
@extends('layouts.navbar')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('title', 'CK Virtual Tutor')

@section('styles')
    <style>
        .container {
            margin-top: 100px;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 20px;
        }

        .hero {
            background-color: #F5F5F5;
            border: 1px solid #F5F5F5;
            padding: 50px;
            border-radius: 12px;
            margin-bottom: 40px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            color: #e91e63;
            font-weight: 700;
        }

        .hero p {
            font-size: 1rem;
            color: #555;
            max-width: 600px;
            margin: 15px auto 0;
        }

        .search-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .search-wrapper select {
            max-width: 400px;
            padding: 10px 16px 10px 16px;
            font-size: 1rem;
            border-radius: 50px;
            outline: none;
            background-color: #fff;
            background-image: none;
            box-shadow: none;
            transition: 0.3s;
        }

        .search-wrapper input:focus {
            border-color: #e91e63;
            box-shadow: 0 0 0 0.1rem rgba(234, 114, 114, 0.1);
        }

        .tool-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .tool-card-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            height: 100%;
        }

        .tool-card {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: stretch;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            width: 100%;
            height: 120px;
        }

        .tool-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
            border-color: #e91e63;
        }

        .tool-card h5 {
            font-size: 1.1rem;
            color: #333333;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 5px;
        }

        .tool-card h5 a {
            color: inherit;
            text-decoration: none;
            pointer-events: none;
            cursor: default;
        }

        .tool-card p {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .tool-card-content {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            flex-grow: 1;
        }

        .tool-card-icon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tool-card-icon img {
            width: 120%;
            height: 120%;
            object-fit: contain;
        }

        .tool-card-favorite {
            position: absolute;
            top: 15px;
            right: 15px;
            color: #ccc;
            font-size: 1.2rem;
            cursor: pointer;
            transition: color 0.02s ease;
        }

        .tool-card-link:hover .tool-card {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }

        /* General modal dialog styling */
        .modal .modal-dialog {
            max-width: 600px;
            margin: 1.75rem auto;
        }

        /* Modal content box */
        .modal .modal-content {
            border-radius: 16px;
            padding: 20px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        /* Header of the modal */
        .modal .modal-header {
            border-bottom: none;
            padding-bottom: 0;
        }

        /* Title inside modal */
        .modal .modal-title {
            font-weight: 600;
            font-size: 1.25rem;
        }

        /* Labels for inputs */
        .modal .form-label {
            font-weight: 500;
            color: #333;
        }

        /* Inputs, selects, and textareas inside modal */
        .modal .form-control,
        .modal .form-select {
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 1rem;
        }

        /* Footer section with buttons */
        .modal .modal-footer {
            border-top: none;
            justify-content: space-between;
            padding-top: 0;
        }

        .btn.btn-primary {
            background-color: #e91e63;
            border-color: #d81b60;
            color: white;
            border: none;
            font-weight: 500;
            font-size: 1rem;
            padding: 0.65rem 1.5rem;
            border-radius: 10px;
            letter-spacing: 0.05em;
        }

        .btn.btn-primary:hover {
            background-color: #d81b60;
            border-color: #d81b60;
        }

        .btn.btn-primary:focus {
            background-color: #d81b60;
            border-color: #d81b60;
        }


        /* Primary button style with e91e63 */
        .modal .btn.btn-primary {
            background-color: #e91e63;
            border-color: #e91e63;
        }

        .modal .btn.btn-primary:hover {
            background-color: #d81b60;
            border-color: #d81b60;
        }

        /* Input/select focus outline color */
        .modal .form-control:focus,
        .modal .form-select:focus {
            border-color: #e91e63;
            box-shadow: 0 0 0 0.15rem rgba(233, 30, 99, 0.25);
        }

        @media (max-width: 992px) {
            .tool-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .tool-grid {
                grid-template-columns: 1fr;
            }
        }

        .notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #e91e63;
            color: #fff;
            padding: 12px 24px; /* Slightly larger padding for better appearance */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 10000; /* Increased z-index to ensure it appears above all elements */
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            font-size: 1rem;
            font-weight: 500;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0)
        }

    </style>

@endsection


@section('content')

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first('message') }}
        </div>
    @endif
    <div id="notification" class="notification"></div>

    <div class="container">
        <div class="hero">
            <h1>Welcome to CK Virtual Tutor</h1>
            <p>Your smart and friendly learning companion designed to make studying fun and easy.</p>
        </div>

        <!-- 📌 Add Chapter/Subject Selector Here -->
        <div class="search-wrapper mb-4 d-flex gap-3 align-items-center">
            <select id="subjectSelect" class="form-select">
                <option value="">Select Grade Level</option>
                <option value="Grade 1">Grade 1</option>
                <option value="Grade 2">Grade 2</option>
                <option value="Grade 3">Grade 3</option>
                <option value="Grade 3">Grade 3</option>
                <option value="Grade 4">Grade 4</option>
                <option value="Grade 5">Grade 5</option>
                <option value="Grade 6">Grade 6</option>
                <option value="Grade 7">Grade 7</option>
                <option value="Grade 8">Grade 8</option>
                <option value="Grade 9">Grade 9</option>
                <option value="Grade 10">Grade 10</option>
                <option value="Grade 11">Grade 11</option>
                <option value="Grade 12">Grade 12</option>
            </select>

        <button class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center"
            style="width: 44px; height: 44px;"
            data-bs-toggle="modal" data-bs-target="#uploadModal"
            title="Add Book">
            <i class="fas fa-plus"></i>
        </button>

        </div>

        <!-- Upload Modal -->
        <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="uploadForm">
                        <!-- enctype="multipart/form-data"> -->
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadModalLabel">Book Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="subjectName" class="form-label">Subject Name</label>
                                <input type="text" class="form-control" id="subjectName" name="subject_name" required>
                            </div>

                            <div class="mb-3">
                                <label for="gradeLevel" class="form-label">Grade Level</label>
                                <select class="form-select" id="gradeLevel" name="grade_level" required>
                                    <option value="">Select Grade</option>
                                                    <option value="">Select Grade Level</option>
                                            <option value="Grade 1">Grade 1</option>
                                            <option value="Grade 2">Grade 2</option>
                                            <option value="Grade 3">Grade 3</option>
                                            <option value="Grade 3">Grade 3</option>
                                            <option value="Grade 4">Grade 4</option>
                                            <option value="Grade 5">Grade 5</option>
                                            <option value="Grade 6">Grade 6</option>
                                            <option value="Grade 7">Grade 7</option>
                                            <option value="Grade 8">Grade 8</option>
                                            <option value="Grade 9">Grade 9</option>
                                            <option value="Grade 10">Grade 10</option>
                                            <option value="Grade 11">Grade 11</option>
                                            <option value="Grade 12">Grade 12</option>
                                    <!-- Add more as needed -->
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>

                            <!-- <div class="mb-3">
                                                                        <label for="pdfFile" class="form-label">Upload PDF File</label>
                                                                        <input type="file" class="form-control" id="pdfFile" name="pdf_file"
                                                                            accept="application/pdf" required>
                                                                    </div> -->
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i> Add
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form id="addUnitForm">
                    <div class="modal-content p-3">
                        <h5>Add Unit</h5>
                        <input type="hidden" name="book_id" id="unitBookId">
                        <input type="text" name="title" placeholder="Unit Title" class="form-control mb-2" required>
                        <input type="number" name="unit_number" placeholder="Unit Number" class="form-control mb-2"
                            required>
                        <button type="submit" class="btn btn-primary">Add Unit</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal fade" id="addChapterModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form id="addChapterForm">
                    <div class="modal-content p-3">
                        <h5>Add Chapter</h5>
                        <input type="hidden" name="unit_id" id="chapterUnitId">
                        <input type="text" name="chapter_title" placeholder="Chapter Title" class="form-control mb-2"
                            required>
                        <input type="number" name="chapter_number" placeholder="Chapter Number"
                            class="form-control mb-2" required>
                        <button type="submit" class="btn btn-primary">Add Chapter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="addLessonModal" tabindex="-1" aria-hidden="true">
            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first('message') }}
                </div>
            @endif
            <div class="modal-dialog">
                <form id="addLessonForm" enctype="multipart/form-data">
                    <div class="modal-content p-3">
                        <h5>Add Lesson</h5>
                        <input type="hidden" name="chapter_id" id="lessonChapterId">
                        <input type="text" name="lesson_title" placeholder="Lesson Title" class="form-control mb-2"
                            required>
                        <input type="number" name="lesson_number" placeholder="Lesson Number" class="form-control mb-2"
                            required>
                        <div class="mb-3">
                            <label for="pdfFile" class="form-label">Upload PDF File</label>
                            <input type="file" class="form-control" id="pdfFile" name="pdf_file"
                                accept="application/pdf" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Lesson</button>
                    </div>
                </form>
            </div>
        </div>



        <div id="bookList" class="tool-grid mt-4">
            <!-- Filtered books will appear here -->
        </div>

    </div>
    </div>

    <script>
        document.getElementById("uploadForm").addEventListener("submit", function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                title: document.getElementById("subjectName").value,
                grade_level: document.getElementById("gradeLevel").value,
                subject_name: document.getElementById("subjectName").value,
                description: document.getElementById("description").value
            };

            fetch("/books", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify(data)
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        // alert("Book added successfully!");
                        showNotification(`Book added successfully!`);

                        document.getElementById("uploadForm").reset();
                        bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
                        loadBooks(); // Reload books
                    } else {
                        showNotification(`Error adding book.`);

                    }
                })
                .catch(err => {
                    console.error(err);
                    showNotification(`Something went wrong.`);

                });
        });

        function loadBooks() {
            const selectedGrade = document.getElementById("subjectSelect").value;
            const bookList = document.getElementById("bookList");
            bookList.innerHTML = '';

            if (!selectedGrade) return;

            fetch("/books")
                .then(response => response.json())
                .then(data => {
                    if (data.status !== "success") {
                        bookList.innerHTML = '<p>Failed to fetch books.</p>';
                        return;
                    }

                    const filtered = data.books.filter(book => book.grade_level === selectedGrade);

                    if (filtered.length === 0) {
                        bookList.innerHTML = '<p>No books found for this grade level.</p>';
                        return;
                    }

                    filtered.forEach(book => {
                        const card = document.createElement('div');
                        card.className = 'tool-card';
                        card.innerHTML = `
                <h5>${book.title}</h5>
                <p>${book.description}</p>
                <small>${book.grade_level}</small>

                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="openUnitModal(${book.id})">+ Add Unit</button>
                    <button class="btn btn-sm btn-outline-success" onclick="redirectToChat(${book.id})">
                        <i class="fa fa-brain me-1"></i> Open Tutor
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleUnits(${book.id})">
                        <i class="fa fa-chevron-down me-1"></i> Show Units
                    </button>
                </div>

                <div id="unit-container-${book.id}" class="mt-3 ps-3" style="display:none;"></div>
            `;
                        bookList.appendChild(card);
                    });
                })
                .catch(err => {
                    console.error("Fetch error:", err);
                    bookList.innerHTML = '<p>Error loading books.</p>';
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById("subjectSelect").addEventListener('change', loadBooks);

            const notification = document.getElementById('notification');

        });

        // Show notification
    function showNotification(message) {
        notification.textContent = message;
        notification.classList.add('show');
        setTimeout(() => {
            notification.classList.remove('show');
        }, 2000);
    }

        function redirectToChat(bookId) {
            fetch(`/get-first-lesson?book_id=${bookId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        const {
                            book_id,
                            unit_id,
                            chapter_id,
                            lesson_id
                        } = data;
                        const url =
                            `/virtual-tutor-chat?book_id=${book_id}&unit_id=${unit_id}&chapter_id=${chapter_id}&lesson_id=${lesson_id}`;
                        window.location.href = url;
                    } else {
                        showNotification(`No lessons found for this book.`);

                    }
                });
        }

        function openUnitModal(bookId) {
            document.getElementById("unitBookId").value = bookId;
            new bootstrap.Modal(document.getElementById("addUnitModal")).show();
        }

        function openChapterModal(unitId) {
            document.getElementById("chapterUnitId").value = unitId;
            new bootstrap.Modal(document.getElementById("addChapterModal")).show();
        }

        function openLessonModal(chapterId) {
            document.getElementById("lessonChapterId").value = chapterId;
            new bootstrap.Modal(document.getElementById("addLessonModal")).show();
        }
        document.getElementById("addUnitForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const form = new FormData(this);
            fetch("/units", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: form
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        showNotification(`Unit added!`);

                        bootstrap.Modal.getInstance(document.getElementById("addUnitModal")).hide();
                        this.reset();
                        loadUnits(form.get("book_id"));
                    }
                });
        });

        document.getElementById("addChapterForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const form = new FormData(this);
            fetch("/chapters", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: form
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        showNotification(`Chapter added!`);

                        bootstrap.Modal.getInstance(document.getElementById("addChapterModal")).hide();
                        this.reset();
                        loadChapters(form.get("unit_id"));
                    }
                });
        });

        function loadUnits(bookId) {
            fetch(`/units?book_id=${bookId}`)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById(`unit-container-${bookId}`);
                    container.innerHTML = '';

                    data.units.forEach(unit => {
                        const unitId = `unit-${unit.id}`;
                        const unitDiv = document.createElement('div');
                        unitDiv.innerHTML = `
                    <div class="border p-2 rounded mb-2">
                        <strong>Unit ${unit.unit_number}:</strong> ${unit.title}
                        <button class="btn btn-sm btn-outline-success ms-2" onclick="openChapterModal(${unit.id})">+ Add Chapter</button>
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="toggleVisibility('${unitId}')">Toggle Chapters</button>
                        <div id="${unitId}" class="ps-3 mt-2" style="display:none;"></div>
                    </div>
                `;
                        container.appendChild(unitDiv);
                        loadChapters(unit.id);
                    });
                });
        }


        function loadChapters(unitId) {
            fetch(`/chapters?unit_id=${unitId}`)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById(`unit-${unitId}`);
                    container.innerHTML = '';

                    data.chapters.forEach(chapter => {
                        const chapterId = `chapter-${chapter.id}`;
                        const chapterDiv = document.createElement('div');
                        chapterDiv.innerHTML = `
                    <div class="border-start ps-2 mb-2">
                        <strong>Chapter ${chapter.chapter_number}:</strong> ${chapter.chapter_title}
                        <button class="btn btn-sm btn-outline-info ms-2" onclick="openLessonModal(${chapter.id})">+ Add Lesson</button>
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="toggleVisibility('${chapterId}')">Toggle Lessons</button>
                        <div id="${chapterId}" class="ps-3 mt-2" style="display:none;"></div>
                    </div>
                `;
                        container.appendChild(chapterDiv);
                        loadLessons(chapter.id);
                    });
                });
        }


        function loadLessons(chapterId) {
            fetch(`/lessons?chapter_id=${chapterId}`)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById(`chapter-${chapterId}`);
                    container.innerHTML = '';

                    data.lessons.forEach(lesson => {
                        const lessonDiv = document.createElement('div');
                        lessonDiv.innerHTML = `
                    <div class="ps-2">
                        📘 <strong>Lesson ${lesson.lesson_number}:</strong> ${lesson.lesson_title}
                    </div>
                `;
                        container.appendChild(lessonDiv);
                    });
                });
        }

        function toggleVisibility(id) {
            const el = document.getElementById(id);
            if (el) {
                el.style.display = el.style.display === 'none' ? 'block' : 'none';
            }
        }

        function toggleUnits(bookId) {
            const container = document.getElementById(`unit-container-${bookId}`);
            const isVisible = container.style.display === 'block';

            if (isVisible) {
                container.style.display = 'none';
            } else {
                container.style.display = 'block';
                if (!container.hasChildNodes()) {
                    loadUnits(bookId);
                }
            }
        }

        document.getElementById("addLessonForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const form = new FormData(this);

    fetch("/lessons", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: form
    })
    .then(async res => {
        const data = await res.json();

        if (!res.ok || data.status !== "success") {
            alert(data.message || "Something went wrong.");
            console.error("FastAPI Error:", data.fastapi_error || data);
            return;
        }

        // ✅ Success
        // alert("Lesson added!");
            showNotification(`Lesson added!`);

        bootstrap.Modal.getInstance(document.getElementById('addLessonModal')).hide();
        this.reset();
        loadLessons(form.get("chapter_id"));
    })
    .catch(err => {
        showNotification(`Unexpected error occurred.`);

        console.error(err);
    });
});
    </script>

@endsection
