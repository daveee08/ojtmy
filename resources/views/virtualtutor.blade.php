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
            gap: 10px;
            margin-bottom: 30px;
        }

        .search-wrapper select {
            max-width: 400px;
            padding: 10px 16px;
            font-size: 1rem;
            border: 1px solid #ccc;
            outline: none;
            background-color: #fff;
            box-shadow: none;
            transition: 0.3s;
        }

        .search-wrapper input:focus {
            border-color: #e91e63;
            box-shadow: 0 0 0 0.1rem rgba(234, 114, 114, 0.1);
        }

        .search-wrapper button {
            height: 42px;
            width: 42px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
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

        /* Modal styles... (same as yours) */

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

        .upload-btn {
            background-color: #fff;
            border: 1px solid #ccc; 
            width: 42px;
            height: 42px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            transition: all 0.2s ease-in-out;
        }

        .upload-btn:hover {
            border-color: #e91e63;
        }

        .upload-icon {
            color: #202020ff; 
            font-size: 1rem;
        }

        .modal .form-control:focus,
        .modal .form-select:focus {
            border-color: #e91e63;
            box-shadow: 0 0 0 0.15rem rgba(233, 30, 99, 0.25);
        }

        .btn.btn-primary:focus,
        .btn.btn-primary:active,
        .btn.btn-primary:focus-visible {
            background-color: #e91e63 !important;
            border-color: #e91e63 !important;
            box-shadow: none !important;
            outline: none !important;
        }

        /* Spinner style to match pink theme */
        .spinner-border {
            color: #fff; /* Spinner dots color */
            border-color: #fff transparent #fff transparent !important;
            background-color: transparent !important;
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
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="hero">
            <h1>Welcome to CK Virtual Tutor</h1>
            <p>AI-powered tutor will utilize a local knowledge base sourced from CK Grade 7 books in Science, English, and Math.</p>
        </div>

        <div class="search-wrapper mb-4 d-flex align-items-center">
            <select id="subjectSelect" class="form-select">
                <option value="">Select Grade Level</option>
                <option value="Grade 1">Grade 1</option>
                <option value="Grade 2">Grade 2</option>
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

            <!-- Paperclip Icon Button -->
            <button class="upload-btn" 
                data-bs-toggle="modal" 
                data-bs-target="#uploadModal" 
                data-bs-placement="top" 
                title="Upload PDF">
                <i class="fas fa-paperclip upload-icon"></i>
            </button>
        </div>

        <!-- Upload Modal -->
        <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="uploadForm" action="{{ url('/upload-endpoint') }}" method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadModalLabel">Upload PDF Details</h5>
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
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="pdfFile" class="form-label">Upload PDF File</label>
                                <input type="file" class="form-control" id="pdfFile" name="pdf_file" accept="application/pdf" required>
                            </div>
                        </div>
                    
                       <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" id="uploadBtn">
                                <span id="uploadBtnText"><i class="fas fa-upload me-1"></i> Submit</span>
                                <span id="uploadSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>
                        </div>
                        <div id="uploadSuccess" class="text-success mt-2 text-center d-none">
                            <i class="fas fa-check-circle me-1"></i> File uploaded successfully!
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div id="bookList" class="tool-grid mt-4"></div>
    </div>

    <script>
        // Upload Form with Spinner and Success Message
        document.getElementById("uploadForm").addEventListener("submit", function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const uploadBtn = document.getElementById('uploadBtn');
            const uploadSpinner = document.getElementById('uploadSpinner');
            const uploadBtnText = document.getElementById('uploadBtnText');
            const uploadSuccess = document.getElementById('uploadSuccess');

            uploadSpinner.classList.remove("d-none");
            uploadBtnText.classList.add("d-none");
            uploadSuccess.classList.add("d-none");

            fetch("/upload-endpoint", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": csrfToken },
                body: formData,
            })
            .then((res) => res.json())
            .then((data) => {
                uploadSpinner.classList.add("d-none");
                uploadBtnText.classList.remove("d-none");

                uploadSuccess.classList.remove("d-none");

                setTimeout(() => {
                    const modalEl = document.getElementById('uploadModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();
                    uploadSuccess.classList.add("d-none");
                    document.getElementById("uploadForm").reset();
                }, 1500);
            })
            .catch((err) => {
                console.error(err);
                uploadSpinner.classList.add("d-none");
                uploadBtnText.classList.remove("d-none");
                alert("Upload failed.");
            });
        });


        // Book List Fetch
        document.addEventListener('DOMContentLoaded', function() {
            const subjectSelect = document.getElementById('subjectSelect');
            const bookList = document.getElementById('bookList');

            subjectSelect.addEventListener('change', function() {
                const selectedGrade = this.value;
                bookList.innerHTML = '';

                if (!selectedGrade) return;

                fetch("http://127.0.0.1:5001/books")
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
                            const cardLink = document.createElement('a');
                            cardLink.href = `/virtual_tutor_chat/${book.book_id}`;
                            cardLink.className = 'tool-card-link';
                            cardLink.innerHTML = `
                                <div class="tool-card">
                                    <h5>${book.title}</h5>
                                    <p>${book.description}</p>
                                    <small>${book.grade_level}</small>
                                </div>`;
                            bookList.appendChild(cardLink);
                        });
                    })
                    .catch(err => {
                        console.error("Fetch error:", err);
                        bookList.innerHTML = '<p>Error loading books.</p>';
                    });
            });

            // Bootstrap tooltip init
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endsection

//original