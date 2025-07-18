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
        <div class="tool-grid" id="bookList">
            <div class="tool-card">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h5 class="mb-0">Math</h5>
                    <small class="text-muted mb-0">Grade 1</small>
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
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".toggle-units").forEach(button => {
                button.addEventListener("click", function() {
                    const unitList = this.closest(".tool-card").querySelector(".unit-list");
                    unitList.classList.toggle("d-none");
                    this.textContent = unitList.classList.contains("d-none") ? "Show Units" :
                        "Hide Units";
                });
            });

            document.querySelectorAll(".unit-item").forEach(unitItem => {
                unitItem.addEventListener("click", function(e) {
                    e.stopPropagation();
                    const chapterList = this.querySelector(".chapter-list");
                    if (chapterList) {
                        chapterList.classList.toggle("d-none");
                        this.classList.toggle("expanded");
                    }
                });
            });

            document.querySelectorAll(".chapter-item").forEach(chapterItem => {
                chapterItem.addEventListener("click", function(e) {
                    e.stopPropagation();
                    const lessonList = this.querySelector(".lesson-list");
                    if (lessonList) {
                        lessonList.classList.toggle("d-none");
                        this.classList.toggle("expanded");
                    }
                });
            });
        });
    </script>
@endsection
