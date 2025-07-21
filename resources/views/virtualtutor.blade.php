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
        display: grid;
        /* Significantly increased min-width to give more "kalapad" (width) to expanded cards */
        grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
        gap: 25px; /* Ample gap between book cards */
    }

    .book-card {
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 24px; /* Generous base padding for the card */
        margin-bottom: 15px;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        height: 120px; /* Small initial height */
        overflow: hidden;
        position: relative;
    }

    .book-card.expanded {
        height: auto; /* Expands on click */
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
        margin-top: 0;
        margin-bottom: 5px;
    }

    .book-card p {
        font-size: 0.9rem;
        color: #666;
        margin: 0;
        margin-bottom: 15px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .book-actions {
        display: flex;
        gap: 15px; /* More space between the main action buttons */
        margin-top: 15px;
        opacity: 0;
        max-height: 0;
        overflow: hidden;
        transition: opacity 0.3s ease, max-height 0.3s ease;
    }

    .book-card.expanded .book-actions {
        opacity: 1;
        max-height: 50px; /* Adjust as needed for button height */
    }

    .unit-container {
        margin-top: 25px; /* Increased space above the entire units section */
        opacity: 0;
        max-height: 0;
        overflow: hidden;
        transition: opacity 0.3s ease, max-height 0.3s ease;
    }

    .book-card.expanded .unit-container {
        opacity: 1;
        max-height: 1000px; /* Large enough to show all units, adjust if necessary */
    }

    .unit-item {
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 18px 25px; /* Ample internal padding (vertical & horizontal) */
        margin-bottom: 20px; /* More vertical space between individual units */
        background-color: #f9f9f9;
        display: flex; /* Use flexbox for better control of internal alignment */
        flex-direction: column; /* Stack contents vertically - this applies to the whole unit block, not just header */
    }

    .unit-item strong {
        color: #e91e63;
        /* Keep nowrap and ellipsis for long titles to prevent wrapping if space is tight */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-right: 15px; /* More space between title and buttons */
    }

    /* The flex container for unit header (icon, text, buttons) */
    .unit-item > .d-flex {
        align-items: center; /* Vertically align items */
        /* IMPORTANT: Changed flex-wrap to nowrap for primary horizontal emphasis */
        flex-wrap: nowrap; /* Attempt to keep all on one line primarily */
        justify-content: space-between; /* Push title to left, buttons to right */
        row-gap: 8px; /* Fallback for wrapping if it occurs */
        margin-bottom: 10px; /* Space below the unit title/buttons before chapters */
        width: 100%; /* Ensure it takes full available width */
    }

    /* The unit title itself within the d-flex header */
    .unit-item > .d-flex > div:first-child { /* Assuming the title is in the first div */
        flex-grow: 1; /* Allow title to take up available space */
        min-width: 0; /* Allow title to shrink if needed, but ellipsis will handle overflow */
        display: flex; /* Use flex to align icon and text */
        align-items: center;
        gap: 8px; /* Space between folder icon and title text */
    }

    /* Group of buttons within the unit header */
    .unit-item > .d-flex .button-group {
        display: flex;
        gap: 15px; /* Space between buttons within the group */
        flex-shrink: 0; /* Prevent button group from shrinking */
    }

    /* Spacing and size for buttons within unit/chapter headers */
    .unit-item .d-flex button {
        padding: 7px 14px; /* Ample padding for buttons */
        font-size: 0.875rem; /* Good font size for secondary buttons */
        white-space: nowrap; /* Prevent button text from wrapping */
        flex-shrink: 0; /* Prevent individual buttons from shrinking */
    }

    /* Ensure specific buttons are styled correctly */
    .btn-add-chapter, .btn-hide-chapters { /* Assuming specific classes for these buttons */
        /* No margin-left: auto here as justify-content handles it */
    }


    .unit-item .chapter-container {
        padding-left: 30px; /* Increased indentation for chapters */
        margin-top: 10px; /* Space between unit header and first chapter */
    }

    .chapter-item {
        border-left: 3px solid #e0e0e0;
        padding-left: 20px; /* Increased indentation */
        padding-top: 10px;
        padding-bottom: 10px;
        margin-bottom: 15px; /* More vertical space between chapters */
        position: relative;
    }

    .chapter-item strong {
        color: #4CAF50;
        white-space: nowrap; /* Prevent chapter title from wrapping if possible */
        overflow: hidden;
        text-overflow: ellipsis; /* Add ellipsis if title is too long */
        margin-right: 15px; /* More space between title and buttons */
    }

    /* The flex container for chapter header (icon, text, buttons) */
    .chapter-item > .d-flex {
        align-items: center; /* Vertically align items */
        /* IMPORTANT: Changed flex-wrap to nowrap for primary horizontal emphasis */
        flex-wrap: nowrap; /* Attempt to keep all on one line primarily */
        justify-content: space-between; /* Push title to left, buttons to right */
        row-gap: 8px; /* Fallback for wrapping if it occurs */
        margin-bottom: 10px; /* Space below chapter title/buttons before lessons */
        width: 100%; /* Ensure it takes full available width */
    }

    /* The chapter title itself within the d-flex header */
    .chapter-item > .d-flex > div:first-child { /* Assuming the title is in the first div */
        flex-grow: 1; /* Allow title to take up available space */
        min-width: 0; /* Allow title to shrink if needed, but ellipsis will handle overflow */
        display: flex; /* Use flex to align icon and text */
        align-items: center;
        gap: 8px; /* Space between folder icon and title text */
    }

    /* Group of buttons within the chapter header */
    .chapter-item > .d-flex .button-group {
        display: flex;
        gap: 15px; /* Space between buttons within the group */
        flex-shrink: 0; /* Prevent button group from shrinking */
    }

    /* Spacing and size for buttons within chapter headers */
    .chapter-item .d-flex button {
        padding: 7px 14px; /* Ample padding for buttons */
        font-size: 0.875rem; /* Good font size */
        white-space: nowrap; /* Prevent button text from wrapping */
        flex-shrink: 0; /* Prevent individual buttons from shrinking */
    }

    /* Ensure specific buttons are styled correctly */
    .btn-add-lesson, .btn-show-lessons, .btn-hide-lessons { /* Assuming specific classes for these buttons */
        /* No margin-left: auto here as justify-content handles it */
    }

    .lesson-item {
        padding-left: 25px; /* Significantly increased indentation for lessons */
        margin-bottom: 10px; /* More vertical space between lessons */
        font-size: 0.95rem; /* Slightly larger font for lesson titles */
    }

    /* General modal dialog styling (keeping original as it's not related to layout issues) */
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
        background-color: #007bff; /* Blue */
        border-color: #007bff;
        color: white;
        font-weight: 500;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        white-space: nowrap; /* Prevent text wrapping */
    }
    .btn-add-unit:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }
    .btn-open-tutor {
        background-color: #28a745; /* Green */
        border-color: #28a745;
        color: white;
        font-weight: 500;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        white-space: nowrap; /* Prevent text wrapping */
    }
    .btn-open-tutor:hover {
        background-color: #218838;
        border-color: #218838;
    }

    /* Responsive Adjustments */
    @media (max-width: 1200px) {
        .books-grid {
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); /* Adjusted min-width for slightly smaller screens */
        }
    }

    @media (max-width: 992px) {
        .books-grid {
            grid-template-columns: repeat(2, 1fr); /* Two columns on medium screens */
            gap: 20px;
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
            padding-left: 20px;
        }
        .unit-item .d-flex button,
        .chapter-item .d-flex button {
            padding: 6px 10px;
            font-size: 0.8rem;
        }
        /* Allow buttons to wrap again on medium screens if horizontal space is very limited */
        .unit-item > .d-flex,
        .chapter-item > .d-flex {
            flex-wrap: wrap; /* Allow wrapping */
            justify-content: flex-start; /* Align items to start when wrapping */
        }
        .unit-item > .d-flex > div:first-child,
        .chapter-item > .d-flex > div:first-child {
            width: 100%; /* Title takes full width before buttons */
            margin-bottom: 5px; /* Space below title before buttons wrap */
        }
        .unit-item .d-flex button,
        .chapter-item .d-flex button {
            margin-left: 0; /* Remove horizontal margin when stacked/wrapped */
            margin-right: 8px; /* Add some margin to the right if they don't fill 100% */
            margin-bottom: 5px; /* Space between wrapped buttons */
        }
    }

    @media (max-width: 768px) { /* Tablet breakpoint: Single column, stacking buttons */
        .books-grid {
            grid-template-columns: 1fr; /* Single column on tablets and smaller */
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
        /* Force buttons to stack completely on tablets */
        .unit-item > .d-flex,
        .chapter-item > .d-flex {
            flex-direction: column; /* Stack vertically */
            align-items: flex-start; /* Align stacked items to the left */
            margin-bottom: 8px;
        }
        .unit-item .d-flex button:first-of-type,
        .chapter-item .d-flex button:first-of-type {
            margin-left: 0; /* Remove auto margin when stacked */
        }
        .unit-item .d-flex button,
        .chapter-item .d-flex button {
            margin-left: 0; /* Remove horizontal margin when stacked */
            margin-right: 0; /* Remove horizontal margin when stacked */
            width: 100%; /* Make buttons full width when stacked */
            margin-bottom: 5px; /* Space between stacked buttons */
            padding: 8px 10px; /* Adjust padding for stacked buttons */
        }
    }

    @media (max-width: 576px) { /* Smallest mobile screens */
        .books-grid {
            grid-template-columns: 1fr;
        }
        .book-card {
            padding: 15px;
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
        /* Ensure stacking behavior for buttons is consistent on smallest screens */
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
    <div class="container">
        <div class="hero">
            <h1>Welcome to CK Virtual Tutor</h1>
            <p>Your smart and friendly learning companion designed to make studying fun and easy.</p>
        </div>

        <div class="search-wrapper mb-4 d-flex gap-3 align-items-center">
            <select id="subjectSelect" class="form-select">
                <option value="">Select Grade Level</option>
                <option value="Grade 1">Grade 1</option>
                <option value="Grade 2">Grade 2</option>
                <option value="Grade 3">Grade 3</option>
                </select>

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
                return;
            }

            fetch("/books")
                .then(response => response.json())
                .then(data => {
                    if (data.status !== "success") {
                        gradeLevelSections.innerHTML = '<p>Failed to fetch books.</p>';
                        return;
                    }

                    const filteredBooks = data.books.filter(book => book.grade_level === selectedGrade);

                    if (filteredBooks.length === 0) {
                        gradeLevelSections.innerHTML = `<p>No books found for ${selectedGrade}.</p>`;
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
                            <small>${book.grade_level}</small>
                            <div class="book-actions">
                                <button class="btn btn-add-unit" onclick="event.stopPropagation(); openUnitModal(${book.id})">+ Add Unit</button>
                                <button class="btn btn-open-tutor" onclick="event.stopPropagation(); redirectToChat(${book.id})">
                                    <i class="fa fa-brain me-1"></i> Open Tutor
                                </button>
                            </div>
                            <div id="unit-container-${book.id}" class="unit-container"></div>
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
                    gradeLevelSections.innerHTML = '<p>Error loading books.</p>';
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById("subjectSelect").addEventListener('change', loadBooksByGrade);
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
                        alert("No lessons found for this book.");
                    }
                });
        }

        function openUnitModal(bookId) {
            document.getElementById("unitBookId").value = bookId;
            new bootstrap.Modal(document.getElementById("addUnitModal")).show();
        }

        function openChapterModal(unitId) { // This is now "Add Lesson" in the UI flow for units
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
                        data.units.forEach(unit => {
                            const unitId = `unit-${unit.id}`;
                            const unitDiv = document.createElement('div');
                            unitDiv.className = 'unit-item';
                            unitDiv.innerHTML = `
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-folder me-2"></i> <strong>Unit ${unit.unit_number}:</strong> ${unit.title}
                                    <button class="btn btn-sm btn-outline-info ms-auto" onclick="event.stopPropagation(); openChapterModal(${unit.id})">+ Add Chapter</button>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="event.stopPropagation(); toggleVisibility('${unitId}')">Show Chapters</button>
                                </div>
                                <div id="${unitId}" class="chapter-container" style="display:none;"></div>
                            `;
                            container.appendChild(unitDiv);
                            loadChapters(unit.id); // Load chapters for this unit
                        });
                    } else {
                        container.innerHTML = '<p class="ms-3">No units added yet.</p>';
                    }
                })
                .catch(err => {
                    console.error("Error loading units:", err);
                    const container = document.getElementById(`unit-container-${bookId}`);
                    container.innerHTML = '<p class="ms-3">Error loading units.</p>';
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
                                    <i class="fas fa-file-alt me-2"></i> <strong>Chapter ${chapter.chapter_number}:</strong> ${chapter.chapter_title}
                                    <button class="btn btn-sm btn-outline-success ms-auto" onclick="event.stopPropagation(); openLessonModal(${chapter.id})">+ Add Lesson</button>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="event.stopPropagation(); toggleVisibility('${chapterId}')">Show Lessons</button>
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
                    container.innerHTML = '<p class="ms-3 text-muted">Error loading chapters.</p>';
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
                            lessonDiv.className = 'lesson-item';
                            lessonDiv.innerHTML = `
                                <div>
                                    📘 <strong>Lesson ${lesson.lesson_number}:</strong> ${lesson.lesson_title}
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
                    container.innerHTML = '<p class="ms-3 text-muted">Error loading lessons.</p>';
                });
        }

        function toggleVisibility(id) {
            const el = document.getElementById(id);
            if (el) {
                if (el.style.display === 'none' || el.style.display === '') {
                    el.style.display = 'block';
                    // Change button text to "Hide"
                    const button = el.previousElementSibling.querySelector('button[onclick*="toggleVisibility"]');
                    if (button) {
                        button.textContent = button.textContent.replace('Show', 'Hide');
                    }
                } else {
                    el.style.display = 'none';
                    // Change button text to "Show"
                    const button = el.previousElementSibling.querySelector('button[onclick*="toggleVisibility"]');
                    if (button) {
                        button.textContent = button.textContent.replace('Hide', 'Show');
                    }
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