@extends('layouts.bootstrap')
@extends('layouts.historysidenav')
@extends('layouts.header')

@section('title', 'Math Reiew Generator')

@section('styles')
    <style>
        .container {
            position: absolute;
            background: white;
            max-width: 700px;
            width: 100%;
            margin-top: 50px;
            padding: 2.5rem 3rem;
            border-radius: 12px;
        }

        h2 {
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        label {
            font-weight: 600;
            color: #34495e;
        }

        textarea.form-control {
            resize: none;
            overflow-y: auto;
            max-height: 400px;
            background-color: #fff !important;
            color: #2c3e50;
            font-family: inherit;
            line-height: 1.5;
            padding-top: 0.8rem;
        }

        .text-center {
            margin-top: 1.8rem;
            margin-bottom: 1.8rem;
        }

        #adaptive_content {
            background-color: #ffffff !important;
            color: #2c3e50;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <h2>Math Review</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="mathReviewForm" method="POST" action="/mathreview" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="grade_level" class="form-label">Grade Level</label>
                <select class="form-select" id="grade_level" name="grade_level" required>
                    <option value="" disabled selected>Select Grade Level</option>
                    <option value="kinder">Kindergarten</option>
                    <option value="grade 1">Grade 1</option>
                    <option value="grade 2">Grade 2</option>
                    <option value="grade 3">Grade 3</option>
                    <option value="grade 4">Grade 4</option>
                    <option value="grade 5">Grade 5</option>
                    <option value="grade 6">Grade 6</option>
                    <option value="grade 7">Grade 7</option>
                    <option value="grade 8">Grade 8</option>
                    <option value="grade 9">Grade 9</option>
                    <option value="grade 10">Grade 10</option>
                    <option value="grade 11">Grade 11</option>
                    <option value="grade 12">Grade 12</option>
                    <option value="college">College</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="number_of_problems" class="form-label">Number of Problems</label>
                <input type="number" class="form-control" id="number_of_problems" name="number_of_problems" min="1"
                    max="15" required placeholder="" />
            </div>

            <div class="mb-4">
                <label for="math_content" class="form-label">Math Content</label>
                <input type="text" class="form-control" id="math_content" name="math_content" required
                    placeholder="Make them word problems, make a mix of some easy and some difficult, etc."></textarea>
            </div>

            <div class="mb-4">
                <label for="additional_criteria" class="form-label">Additional Criteria (Optional)</label>
                <textarea class="form-control" id="additional_criteria" name="additional_criteria" rows="3"
                    placeholder="Make them word problems, make a mix of some easy and some difficult, etc."></textarea>
            </div>

            <div class="mb-4 text-center">
                <button type="submit" class="btn btn-primary">Generate</button>
            </div>
        </form>
    </div>
@endsection

<!-- Spinner Overlay -->
@section('scripts')
    <script>
        document.getElementById('mathReviewForm').addEventListener('submit', function() {
            document.getElementById('loading-overlay').style.display = 'flex';
            this.querySelector('button[type="submit"]').disabled = true;
        });
    </script>
@endsection
