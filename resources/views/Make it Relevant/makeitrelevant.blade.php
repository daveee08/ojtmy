@extends('layouts.bootstrap')
@extends('layouts.historysidenav')
@extends('layouts.header')

@section('title', 'AI Text Leveler')

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
        <h2>Make it Relevant!</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="relevanceForm" method="POST" action="/makeitrelevant" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="input_type" class="form-label">Select Input Type</label>
                <select class="form-select" id="input_type" name="input_type" required onchange="toggleInputFields()">
                    <option value="" disabled selected>Choose input method</option>
                    <option value="text">Text</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>

            <div class="mb-4" id="text_input_group" style="display: none;">
                <label for="learning_topic" class="form-label">What You're Learning</label>
                <textarea class="form-control" id="learning_topic" name="learning_topic" rows="5"
                    placeholder="Type or paste what you're currently learning (topic, standard, or description)..."></textarea>
            </div>

            <div class="mb-4" id="pdf_input_group" style="display: none;">
                <label for="pdf_file" class="form-label">Upload Learning Material (PDF)</label>
                <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept="application/pdf" />
            </div>

            <div class="mb-4">
                <label for="grade_level" class="form-label">Grade Level</label>
                <select class="form-select" id="grade_level" name="grade_level" required>
                    <option value="" disabled selected>Select grade level</option>
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
                </select>
            </div>

            <div class="mb-4">
                <label for="interests" class="form-label">Describe Your Interests</label>
                <textarea class="form-control" id="interests" name="interests" rows="4"
                    placeholder="What are your hobbies, favorite topics, or things you love doing?"></textarea>
            </div>

            <div class="mb-4 text-center">
                <button type="submit" class="btn btn-primary">Generate</button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        function toggleInputFields() {
            const mode = document.getElementById('input_type').value;
            const textGroup = document.getElementById('text_input_group');
            const pdfGroup = document.getElementById('pdf_input_group');

            if (mode === 'text') {
                textGroup.style.display = 'block';
                pdfGroup.style.display = 'none';
            } else if (mode === 'pdf') {
                textGroup.style.display = 'none';
                pdfGroup.style.display = 'block';
            } else {
                textGroup.style.display = 'none';
                pdfGroup.style.display = 'none';
            }
        }

        document.getElementById('relevanceForm').addEventListener('submit', function() {
            document.getElementById('loading-overlay').style.display = 'flex';
            this.querySelector('button[type="submit"]').disabled = true;
        });
    </script>
@endsection
