@extends('layouts.header')
@extends('layouts.navbar')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('title', 'CK Virtual Tutor')

@section('styles')
<style>\
    .container {
        margin-top: 100px;
        max-width: 1100px;
        margin-left: auto;
        margin-right: auto;
        padding: 0 20px;
    }

    body {
        overflow-y: scroll; /* Forces the vertical scrollbar to always be visible */
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
        -webkit-appearance: none; /* For custom dropdown arrow */
        -moz-appearance: none;    /* For custom dropdown arrow */
        appearance: none;         /* For custom dropdown arrow */
        padding-right: 35px; /* Make space for an arrow icon */
    }

    .search-wrapper input:focus {
        border-color: #e91e63;
        box-shadow: 0 0 0 0.1rem rgba(234, 114, 114, 0.1);
    }

    .grade-level-section {
        margin-bottom: 30px;
    }

    .grade-level-section h3 {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 20px;
        border-bottom: 2px solid #e0e0e0;
        padding-bottom: 10px;
    }

    .books-grid {
        grid-template-columns: repeat(auto-fill, minmax(1000px, 1fr));
        gap: 15px; 
    }

    .book-card {
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 1px 20px;
        margin-top: 10; 
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        position: relative;
    }

    .book-card.expanded {
        height: auto;
    }

    .book-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
        border-color: #e91e63;
    }

    .book-card h5 {
        font-size: 1.1rem;
        color: #333333;
        font-weight: 600;
        margin-top: 30;
    }

    .book-card p {
        font-size: 0.9rem;
        color: #666;
        margin: 10;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .book-actions {
        display: flex;
        opacity: 0;
        max-height: 0;
        overflow: hidden;
        transition: opacity 0.3s ease, max-height 0.3s ease;
    }

    .book-card.expanded .book-actions {
        opacity: 1;
        max-height: 50px; 
    }

    .unit-container {
        opacity: 0;
        max-height: 0;
        overflow: hidden;
        transition: opacity 0.3s ease, max-height 0.3s ease;
    }

    .book-card.expanded .unit-container {
        opacity: 1;
        max-height: 1000px;
    }

    .unit-item {
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 18px 25px;
        margin-bottom: 20px;
        background-color: #f9f9f9;
        display: flex;
        flex-direction: column;
    }

    .unit-item strong {
        color: #e91e63;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-right: 15px;
        display: flex; /* Added for icon alignment */
        align-items: center; /* Added for icon alignment */
        gap: 8px; /* Added space between icon and text */
    }

    .unit-item > .d-flex {
        align-items: center;
        flex-wrap: nowrap;
        justify-content: space-between;
        row-gap: 8px;
        margin-bottom: 10px;
        width: 100%;
    }

    .unit-item > .d-flex > div:first-child {
        flex-grow: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .unit-item > .d-flex .button-group {
        display: flex;
        gap: 15px;
        flex-shrink: 0;
    }

    .unit-item .d-flex button {
        padding: 7px 14px;
        font-size: 0.875rem;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .btn-add-chapter, .btn-hide-chapters {
        
    }

    .unit-item .chapter-container {
        padding-left: 30px;
        margin-top: 15px; /* Adjusted margin-top for more space */
    }

    .chapter-item {
        border-left: 3px solid #e0e0e0;
        padding-left: 20px;
        padding-bottom: 10px;
        margin-bottom: 15px;
        position: relative; /* Added for icon positioning */
    }

    .chapter-item strong {
        color: #4CAF50;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-right: 15px;
        display: flex; /* Changed to flex for icon alignment */
        align-items: center; /* Added for icon alignment */
        gap: 8px; /* Added space between icon and text */
        max-width: 200px; /* Kept existing max-width */
    }

    .chapter-item > .d-flex {
        align-items: center;
        flex-wrap: nowrap;
        justify-content: space-between;
        row-gap: 8px;
        margin-bottom: 10px;
        width: 100%;
    }

    .chapter-item > .d-flex > div:first-child {
        flex-grow: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chapter-item > .d-flex .button-group {
        display: flex;
        gap: 15px;
        flex-shrink: 0;
    }

    .chapter-item .d-flex button {
        padding: 7px 14px;
        font-size: 0.875rem;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .btn-add-lesson, .btn-show-lessons, .btn-hide-lessons {
        
    }

    .lesson-item {
        padding-left: 25px;
        margin-bottom: 10px;
        font-size: 0.95rem;
        display: flex; /* Added for icon alignment */
        align-items: center; /* Added for icon alignment */
        gap: 8px; /* Added space between icon and text */
    }

    .toggle-icon {
        transition: transform 0.3s ease-in-out;
    }

    .chapter-item.expanded .toggle-icon {
        transform: rotate(180deg);
    }

    .modal .modal-dialog {
        max-width: 600px;
        margin: 1.75rem auto;
    }
    .modal .modal-content {
        border-radius: 16px;
        padding: 20px;
        border: none;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
    .modal .modal-header {
        border-bottom: none;
        padding-bottom: 0;
    }
    .modal .modal-title {
        font-weight: 600;
        font-size: 1.25rem;
    }
    .modal .form-label {
        font-weight: 500;
        color: #333;
    }
    .modal .form-control,
    .modal .form-select {
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 1rem;
    }
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
    .modal .btn.btn-primary {
        background-color: #e91e63;
        border-color: #e91e63;
    }
    .modal .btn.btn-primary:hover {
        background-color: #d81b60;
        border-color: #d81b60;
    }
    .modal .form-control:focus,
    .modal .form-select:focus {
        border-color: #e91e63;
        box-shadow: 0 0 0 0.15rem rgba(233, 30, 99, 0.25);
    }
    .btn-add-unit {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
        font-weight: 500;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        white-space: nowrap;
    }
    .btn-add-unit:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }
    .btn-open-tutor {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
        font-weight: 500;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        white-space: nowrap;
    }
    .btn-open-tutor:hover {
        background-color: #218838;
        border-color: #218838;
    }

    @media (max-width: 1200px) {
        .books-grid {
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        }
    }

    @media (max-width: 992px) {
        .books-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .book-card {
            padding: 10px;
        }
        .unit-item {
            padding: 15px 20px;
        }
        .unit-item .chapter-container {
            padding-left: 20px;
        }
        .chapter-item {
            padding-left: 15px;
        }
        .lesson-item {
            padding-left: 20px;
        }

        .unit-item .d-flex button,
        .chapter-item .d-flex button {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
        .unit-item > .d-flex,
        .chapter-item > .d-flex {
            flex-wrap: wrap;
            justify-content: flex-start;
        }
        .unit-item > .d-flex > div:first-child,
        .chapter-item > .d-flex > div:first-child {
            width: 100%;
            margin-bottom: 5px;
        }
        .unit-item .d-flex button,
        .chapter-item .d-flex button {
            margin-left: 0;
            margin-right: 8px;
            margin-bottom: 5px;
        }
    }

    @media (max-width: 768px) {
        .books-grid {
            grid-template-columns: 1fr;
        }
        .book-card {
            padding: 20px;
        }
        .unit-item {
            padding: 15px 20px;
        }
        .unit-item .chapter-container {
            padding-left: 20px;
        }
        .chapter-item {
            padding-left: 15px;
        }
        .lesson-item {
            padding-left: 18px;
        }
        .unit-item > .d-flex,
        .chapter-item > .d-flex {
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        .unit-item .d-flex button:first-of-type,
        .chapter-item .d-flex button:first-of-type {
            margin-left: 0;
        }
        .unit-item .d-flex button,
        .chapter-item .d-flex button {
            margin-left: 0;
            margin-right: 0;
            width: 100%;
            margin-bottom: 5px;
            padding: 8px 10px;
        }
    }

    @media (max-width: 576px) {
        .books-grid {
            grid-template-columns: 1fr;
        }
        .book-card {
            padding: 10px;
        }
        .unit-item {
            padding: 10px 15px;
            margin-bottom: 10px;
        }
        .unit-item .chapter-container {
            padding-left: 15px;
        }
        .chapter-item {
            padding-left: 10px;
            margin-bottom: 8px;
        }
        .lesson-item {
            padding-left: 15px;
            margin-bottom: 6px;
        }
        .unit-item > .d-flex,
        .chapter-item > .d-flex {
            flex-direction: column;
            align-items: flex-start;
            row-gap: 5px;
            margin-bottom: 5px;
        }
        .unit-item .d-flex button,
        .chapter-item .d-flex button {
            margin-left: 0;
            width: 100%;
            margin-bottom: 5px;
            padding: 8px 10px;
        }
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

        <div class="search-wrapper mb-4 d-flex gap-3 align-items-center position-relative">
            <select id="subjectSelect" class="form-select me-2">
                <option value="">Select Grade Level</option>
                <option value="Grade 1">Grade 1</option>
                <option value="Grade 2">Grade 2</option>
                <option value="Grade 3">Grade 3</option>
            </select>
            {{-- A Font Awesome icon for dropdown arrow can be added here or via pseudo-element in CSS if JS handles it.
                 For now, CSS `appearance: none;` prepares the select for a custom arrow.
                 Example (requires JS to toggle classes): <i class="fas fa-chevron-down dropdown-arrow-icon"></i> --}}

            <button class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center"
                style="width: 44px; height: 44px;" data-bs-toggle="modal" data-bs-target="#uploadModal" title="Add Book">
                <i class="fas fa-plus"></i>
            </button>
        </div>

        <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="uploadForm">
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
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
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

        <div id="gradeLevelSections">
            {{-- Content for grade levels will be dynamically inserted here by JavaScript --}}
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
                    alert("Book added successfully!");
                    document.getElementById("uploadForm").reset();
                    bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
                    loadBooksByGrade(); // Reload books for the selected grade
                } else {
                    alert("Error adding book.");
                }
            })
            .catch(err => {
                console.error(err);
                alert("Something went wrong.");
            });
    });

    function loadBooksByGrade() {
        const selectedGrade = document.getElementById("subjectSelect").value;
        const gradeLevelSections = document.getElementById("gradeLevelSections");
        gradeLevelSections.innerHTML = ''; // Clear previous content

        if (!selectedGrade) {
            // If no grade is selected, don't show any books.
            // You might want to display a message like "Please select a grade level."
            gradeLevelSections.innerHTML = '<p class="text-muted text-center mt-4">Please select a grade level to view books.</p>';
            return;
        }

        fetch("/books")
            .then(response => response.json())
            .then(data => {
                if (data.status !== "success") {
                    gradeLevelSections.innerHTML = '<p class="text-danger">Failed to fetch books.</p>';
                    return;
                }

                const filteredBooks = data.books.filter(book => book.grade_level === selectedGrade);

                if (filteredBooks.length === 0) {
                    gradeLevelSections.innerHTML = `<p class="text-muted text-center mt-4">No books found for ${selectedGrade}.</p>`;
                    return;
                }

                let gradeSection = document.createElement('div');
                gradeSection.className = 'grade-level-section';
                gradeSection.innerHTML = `<h3>${selectedGrade}</h3><div class="books-grid" id="books-grid-${selectedGrade.replace(/\s/g, '-')}"></div>`;
                gradeLevelSections.appendChild(gradeSection);

                const booksGrid = document.getElementById(`books-grid-${selectedGrade.replace(/\s/g, '-')}`);

                filteredBooks.forEach(book => {
                    const bookCard = document.createElement('div');
                    bookCard.className = 'book-card';
                    bookCard.setAttribute('data-book-id', book.id);
                    bookCard.innerHTML = `
                        <h5>${book.title}</h5>
                        <p>${book.description}</p>
                        <div class="book-actions mt-3 d-flex justify-content-between align-items-center">
                            <button class="btn btn-sm btn-add-unit" onclick="event.stopPropagation(); openUnitModal(${book.id})">
                                <i class="fas fa-plus-circle me-1"></i> Add Unit
                            </button>
                            <button class="btn btn-sm btn-open-tutor" onclick="event.stopPropagation(); redirectToChat(${book.id})">
                                <i class="fa fa-brain me-1"></i> Open Tutor
                            </button>
                        </div>
                        <div id="unit-container-${book.id}" class="unit-container mt-3"></div>
                    `;
                    bookCard.addEventListener('click', function() {
                        this.classList.toggle('expanded');
                        if (this.classList.contains('expanded')) {
                            loadUnits(book.id);
                        } else {
                            const unitContainer = document.getElementById(`unit-container-${book.id}`);
                            unitContainer.innerHTML = ''; // Clear units when collapsed
                        }
                    });
                    booksGrid.appendChild(bookCard);
                });
            })
            .catch(err => {
                console.error("Fetch error:", err);
                gradeLevelSections.innerHTML = '<p class="text-danger">Error loading books.</p>';
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById("subjectSelect").addEventListener('change', loadBooksByGrade);
        loadBooksByGrade(); // Initial load when the page loads
    });

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
                    alert("No lessons found for this book. Please add lessons to enable the tutor.");
                }
            })
            .catch(err => {
                console.error("Error redirecting to chat:", err);
                alert("Could not load tutor. Please try again later.");
            });
    }

    function openUnitModal(bookId) {
        document.getElementById("unitBookId").value = bookId;
        new bootstrap.Modal(document.getElementById("addUnitModal")).show();
    }

    function openChapterModal(unitId) { // This is now "Add Chapter"
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
                    alert("Unit added!");
                    bootstrap.Modal.getInstance(document.getElementById("addUnitModal")).hide();
                    this.reset();
                    loadUnits(form.get("book_id")); // Reload units for the specific book
                } else {
                    alert("Error adding unit.");
                }
            })
            .catch(err => {
                console.error("Error adding unit:", err);
                alert("Something went wrong while adding unit.");
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
                    alert("Chapter added!");
                    bootstrap.Modal.getInstance(document.getElementById("addChapterModal")).hide();
                    this.reset();
                    loadChapters(form.get("unit_id")); // Reload chapters for the specific unit
                } else {
                    alert("Error adding chapter.");
                }
            })
            .catch(err => {
                console.error("Error adding chapter:", err);
                alert("Something went wrong while adding chapter.");
            });
    });

    function loadUnits(bookId) {
        fetch(`/units?book_id=${bookId}`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById(`unit-container-${bookId}`);
                container.innerHTML = ''; // Clear existing units

                if (data.units && data.units.length > 0) {
                    data.units.sort((a,b) => a.unit_number - b.unit_number); // Sort units
                    data.units.forEach(unit => {
                        const unitId = `unit-${unit.id}`;
                        const unitDiv = document.createElement('div');
                        unitDiv.className = 'unit-item';
                        unitDiv.innerHTML = `
                            <div class="d-flex align-items-center mb-2">
                                <i class="text-primary"></i> <strong>Unit ${unit.unit_number}:</strong> ${unit.title}
                                <button class="btn btn-sm btn-outline-info ms-auto" onclick="event.stopPropagation(); openChapterModal(${unit.id})">
                                    <i class="fas fa-plus-circle me-1"></i> Add Chapter
                                </button>
                                <span class="ms-3 toggle-span" onclick="event.stopPropagation(); toggleVisibility('${unitId}', this)">
                                    <i class="fas fa-chevron-down toggle-icon"></i>
                                </span>
                            </div>
                            <div id="${unitId}" class="chapter-container" style="display:none;"></div>
                        `;
                        container.appendChild(unitDiv);
                        loadChapters(unit.id); // Load chapters for this unit
                    });
                } else {
                    container.innerHTML = '<p class="ms-3 text-muted">No units added yet.</p>';
                }
            })
            .catch(err => {
                console.error("Error loading units:", err);
                const container = document.getElementById(`unit-container-${bookId}`);
                container.innerHTML = '<p class="ms-3 text-danger">Error loading units.</p>';
            });
    }

    function loadChapters(unitId) {
        fetch(`/chapters?unit_id=${unitId}`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById(`unit-${unitId}`);
                container.innerHTML = ''; // Clear existing chapters

                if (data.chapters && data.chapters.length > 0) {
                    data.chapters.sort((a, b) => a.chapter_number - b.chapter_number); // Sort chapters
                    data.chapters.forEach(chapter => {
                        const chapterId = `chapter-${chapter.id}`;
                        const chapterDiv = document.createElement('div');
                        chapterDiv.className = 'chapter-item';
                        chapterDiv.innerHTML = `
                            <div class="d-flex align-items-center mb-1">
                                <i class="text-success"></i> <strong>Chapter ${chapter.chapter_number}:</strong> ${chapter.chapter_title}
                                <button class="btn btn-sm btn-outline-success ms-auto" onclick="event.stopPropagation(); openLessonModal(${chapter.id})">
                                    <i class="fas fa-plus-circle me-1"></i> Add Lesson
                                </button>
                                <span class="ms-3 toggle-span" onclick="event.stopPropagation(); toggleVisibility('${chapterId}', this)">
                                    <i class="fas fa-chevron-down toggle-icon"></i>
                                </span>
                            </div>
                            <div id="${chapterId}" class="ps-3 mt-2" style="display:none;"></div>
                        `;
                        container.appendChild(chapterDiv);
                        loadLessons(chapter.id); // Load lessons for this chapter
                    });
                } else {
                    container.innerHTML = '<p class="ms-3 text-muted">No chapters added yet.</p>';
                }
            })
            .catch(err => {
                console.error("Error loading chapters:", err);
                const container = document.getElementById(`unit-${unitId}`);
                container.innerHTML = '<p class="ms-3 text-danger">Error loading chapters.</p>';
            });
    }

    function loadLessons(chapterId) {
        fetch(`/lessons?chapter_id=${chapterId}`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById(`chapter-${chapterId}`);
                container.innerHTML = ''; // Clear existing lessons

                if (data.lessons && data.lessons.length > 0) {
                    data.lessons.sort((a, b) => a.lesson_number - b.lesson_number); // Sort lessons
                    data.lessons.forEach(lesson => {
                        const lessonDiv = document.createElement('div');
                        lessonDiv.className = 'lesson-item d-flex align-items-center'; // Added d-flex and align-items-center
                        lessonDiv.innerHTML = `
                            <i class="fas fa-book-reader me-2 text-info"></i> <div>
                                <strong>Lesson ${lesson.lesson_number}:</strong> ${lesson.lesson_title}
                            </div>
                        `;
                        container.appendChild(lessonDiv);
                    });
                } else {
                    container.innerHTML = '<p class="ms-3 text-muted">No lessons added yet.</p>';
                }
            })
            .catch(err => {
                console.error("Error loading lessons:", err);
                const container = document.getElementById(`chapter-${chapterId}`);
                container.innerHTML = '<p class="ms-3 text-danger">Error loading lessons.</p>';
            });
    }

    // Refactored toggleVisibility to handle both units and chapters
    function toggleVisibility(id, buttonElement) {
        const el = document.getElementById(id);
        if (el) {
            const icon = buttonElement.querySelector('.toggle-icon');
            if (el.style.display === 'none' || el.style.display === '') {
                el.style.display = 'block';
                if (icon) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
                // For unit toggle, change button text if it exists (Show Chapters/Hide Chapters)
                const unitToggleButton = buttonElement.closest('.d-flex').querySelector('.btn[onclick*="toggleVisibility"]');
                if (unitToggleButton) {
                    unitToggleButton.textContent = unitToggleButton.textContent.replace('Show', 'Hide');
                }
            } else {
                el.style.display = 'none';
                if (icon) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
                // For unit toggle, change button text if it exists (Show Chapters/Hide Chapters)
                const unitToggleButton = buttonElement.closest('.d-flex').querySelector('.btn[onclick*="toggleVisibility"]');
                if (unitToggleButton) {
                    unitToggleButton.textContent = unitToggleButton.textContent.replace('Hide', 'Show');
                }
            }
        } else {
            console.error('Element not found for ID:', id);
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
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    alert("Lesson added!");
                    bootstrap.Modal.getInstance(document.getElementById('addLessonModal')).hide();
                    this.reset();
                    loadLessons(form.get("chapter_id")); // Reload lessons for the specific chapter
                } else {
                    alert("Error adding lesson.");
                }
            })
            .catch(err => {
                console.error("Error adding lesson:", err);
                alert("Something went wrong while adding lesson.");
            });
    });
</script>
@endsection
