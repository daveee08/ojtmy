@extends('layouts.header')
@extends('layouts.navbar')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

@section('title', 'Home - CK AI Tools')

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
            border: 1px solid #ccc;
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
            <p>AI-powered tutor will utilize a local knowledge base sourced from CK Grade 7 books in Science, English, and
                Math.</p>
        </div>

        <!-- 📌 Add Chapter/Subject Selector Here -->
        <div class="search-wrapper mb-4">
            <select id="subjectSelect" class="form-select">
                <option value="">Select Grade Level</option>
                <option value="Grade 1">Grade 1</option>
                <option value="Grade 2">Grade 2</option>
                <option value="Grade 3">Grade 3</option>
            </select>
        </div>

        <div id="bookList" class="tool-grid mt-4"></div>
    </div>
    </div>

    <script>
        // document.getElementById('toolSearch').addEventListener('input', function() {
        //     const query = this.value.toLowerCase();
        //     const cards = document.querySelectorAll('.tool-card-link');
        //     cards.forEach(link => {
        //         const text = link.innerText.toLowerCase();
        //         link.style.display = text.includes(query) ? 'block' : 'none';
        //     });
        // });

        document.addEventListener('DOMContentLoaded', function () {
    const subjectSelect = document.getElementById('subjectSelect');
    const bookList = document.getElementById('bookList');

    if (!subjectSelect) {
        console.error("Dropdown with id 'subjectSelect' not found!");
        return;
    }

    subjectSelect.addEventListener('change', function () {
        const selectedGrade = this.value;
        bookList.innerHTML = '';

        if (!selectedGrade) return;

        console.log("Fetching books for:", selectedGrade);

        fetch("http://127.0.0.1:5001/books")  // Update to real IP if needed
            .then(response => {
                console.log("Response status:", response.status);
                return response.json();
            })
            .then(data => {
                console.log("Fetched data:", data);

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
                        </div>
                    `;
                    bookList.appendChild(cardLink);

                });
            })
            .catch(err => {
                console.error("Fetch error:", err);
                bookList.innerHTML = '<p>Error loading books.</p>';
            });
    });
});
    </script>
@endsection
