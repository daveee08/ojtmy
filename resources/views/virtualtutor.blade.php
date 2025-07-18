@extends('layouts.header')
@extends('layouts.navbar')

@section('title', 'CK Virtual Tutor')
@section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: #fdfdfd;
        }

        .container {
            margin-top: 100px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding: 0 24px;
        }

        .hero {
            background-color: #fff0f5;
            border: 1px solid #ffe4ec;
            padding: 60px 30px;
            border-radius: 16px;
            margin-bottom: 50px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3.5rem;
            color: #d81b60;
            font-weight: 800;
        }

        .hero p {
            font-size: 1.2rem;
            color: #666;
            max-width: 650px;
            margin: 24px auto 0;
        }

        .btn-container {
            position: fixed;
            top: 100px;
            right: 40px;
            z-index: 1050;
        }

        .tool-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .tool-card {
            background: white;
            border-radius: 20px;
            padding: 30px 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .tool-card:hover {
            transform: translateY(-6px);
            border-color: #d81b60;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.08);
        }

        .tool-card h5 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2c2c2c;
        }

        .tool-card small {
            font-size: 0.9rem;
            color: #999;
        }

        .tool-card p {
            font-size: 1rem;
            color: #666;
            margin-top: 8px;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .toggle-units {
            margin-top: 10px;
            padding: 6px 14px;
            font-size: 0.9rem;
            border-radius: 8px;
            border: 1px solid #d81b60;
            background: #fff;
            color: #d81b60;
            transition: background 0.2s, color 0.2s;
        }

        .toggle-units:hover {
            background-color: #d81b60;
            color: white;
        }

        .unit-list,
        .chapter-list,
        .lesson-list {
            transition: all 0.3s ease;
            overflow: hidden;
            padding-left: 1rem;
            border-left: 3px solid #eee;
        }

        .unit-item,
        .chapter-item,
        .lesson-item {
            position: relative;
            cursor: pointer;
            padding-left: 24px;
            margin: 6px 0;
            font-weight: 500;
            color: #444;
        }

        .unit-item::before,
        .chapter-item::before {
            content: "\f078";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            transition: transform 0.2s ease;
        }

        .unit-item.expanded::before,
        .chapter-item.expanded::before {
            transform: rotate(90deg);
        }

        .lesson-item {
            font-size: 0.95rem;
            color: #666;
            padding-left: 32px;
        }

        .modal .modal-content {
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .btn.btn-primary {
            background-color: #e91e63;
            border-color: #d81b60;
            color: white;
            font-weight: 500;
            font-size: 1rem;
            padding: 0.65rem 1.5rem;
            border-radius: 10px;
            letter-spacing: 0.05em;
        }

        .btn.btn-primary:hover,
        .btn.btn-primary:focus {
            background-color: #d81b60;
            border-color: #d81b60;
        }
    </style>
@endsection

@section('content')
    <div class="btn-container">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-1"></i> Add Subject
        </button>
    </div>

    <div class="container">
        <div class="hero">
            <h1>Welcome to CK Virtual Tutor</h1>
            <p>AI-powered tutor will utilize a local knowledge base sourced from CK Grade 7 books in Science, English, and
                Math.</p>
        </div>

        <!-- 📌 Add Chapter/Subject Selector Here -->
        <div class="search-wrapper mb-4 d-flex gap-3 align-items-center">
            <select id="subjectSelect" class="form-select">
                <option value="">Select Grade Level</option>
                <option value="Grade 1">Grade 1</option>
                <option value="Grade 2">Grade 2</option>
                <option value="Grade 3">Grade 3</option>
            </select>

            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="fas fa-upload me-1"></i> Add Book
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
                                    <option value="Grade 1">Grade 1</option>
                                    <option value="Grade 2">Grade 2</option>
                                    <option value="Grade 3">Grade 3</option>
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
                <p>Addition and Multiplications Lessons</p>
                <button class="toggle-units">Show Units</button>
                <ul class="list-group mt-2 unit-list d-none">
                    <li class="list-group-item unit-item" data-unit="Unit 1">Unit 1: Numbers
                        <ul class="list-group mt-2 chapter-list d-none">
                            <li class="list-group-item chapter-item" data-chapter="Chapter 1">Chapter 1: Counting
                                <ul class="list-group mt-2 lesson-list d-none">
                                    <li class="list-group-item lesson-item">Lesson 1: Counting up to 10</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="addUnitForm">
            <div class="modal-content p-3">
                <h5>Add Unit</h5>
                <input type="hidden" name="book_id" id="unitBookId">
                <input type="text" name="title" placeholder="Unit Title" class="form-control mb-2" required>
                <input type="number" name="unit_number" placeholder="Unit Number" class="form-control mb-2" required>
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
                <input type="text" name="chapter_title" placeholder="Chapter Title" class="form-control mb-2" required>
                <input type="number" name="chapter_number" placeholder="Chapter Number" class="form-control mb-2" required>
                <button type="submit" class="btn btn-primary">Add Chapter</button>
            </div>
            </form>
        </div>
        </div>

        <div class="modal fade" id="addLessonModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form id="addLessonForm" enctype="multipart/form-data">
                    <div class="modal-content p-3">
                        <h5>Add Lesson</h5>
                        <input type="hidden" name="chapter_id" id="lessonChapterId">
                        <input type="text" name="lesson_title" placeholder="Lesson Title" class="form-control mb-2" required>
                        <input type="number" name="lesson_number" placeholder="Lesson Number" class="form-control mb-2" required>
                        <div class="mb-3">
                            <label for="pdfFile" class="form-label">Upload PDF File</label>
                            <input type="file" class="form-control" id="pdfFile" name="pdf_file" accept="application/pdf" required>
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
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                alert("Book added successfully!");
                document.getElementById("uploadForm").reset();
                bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
                loadBooks(); // Reload books
            } else {
                alert("Error adding book.");
            }
        })
        .catch(err => {
            console.error(err);
            alert("Something went wrong.");
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
    });

    function redirectToChat(bookId) {
    fetch(`/get-first-lesson?book_id=${bookId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                const { book_id, unit_id, chapter_id, lesson_id } = data;
                const url = `/virtual-tutor-chat?book_id=${book_id}&unit_id=${unit_id}&chapter_id=${chapter_id}&lesson_id=${lesson_id}`;
                window.location.href = url;
            } else {
                alert("No lessons found for this book.");
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
        headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
        body: form
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            alert("Unit added!");
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
        headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
        body: form
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            alert("Chapter added!");
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
        headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
        body: form
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            alert("Lesson added!");
            bootstrap.Modal.getInstance(document.getElementById('addLessonModal')).hide();
            this.reset();
            loadLessons(form.get("chapter_id"));
        }
    });
});


</script>

@endsection
