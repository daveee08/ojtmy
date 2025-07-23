@extends('layouts.app') {{-- Assuming you have a layouts.app like QOTD.blade.php's structure --}}

@section('content')
<style>
    body {
        background: linear-gradient(to right, #ffe6ec, #ffffff);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #2c2c2c;
    }
    .container-tj {
        background: #ffffff;
        max-width: 700px;
        padding: 32px;
        border-radius: 18px;
        box-shadow: 0 2px 16px rgba(80, 60, 200, 0.08);
        margin: 40px auto;
    }
    .h2-tj {
        text-align:center;
        font-weight:600;
        margin-bottom:8px;
        color: #e91e63;
    }
    .p-tj {
        text-align:center;
        color:#555;
        margin-bottom:32px;
    }
    .btn-primary-tj {
        background:#e91e63;
        border:none;
        font-weight:600;
        font-size:1.1em;
        border-radius:30px;
        padding: 10px 20px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .btn-primary-tj:hover {
        background-color: #d81b60;
    }
    .form-control-tj {
        border-color: #ddd;
        padding: 10px 15px;
        border-radius: 8px;
        width: 100%;
        box-sizing: border-box;
    }
    .joke-display-tj {
        margin-top: 32px;
        padding: 24px;
        background: #f7f7ff;
        border-radius: 12px;
        text-align:center;
        font-size:1.2em;
        color:#333;
        word-wrap: break-word;
    }
    .btn-sm-outline-secondary-tj,
    .btn-sm-outline-danger-tj {
        border:1px solid #e91e63;
        color:#e91e63;
        background:transparent;
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        transition: background-color 0.3s ease, color 0.3s ease;
    }
    .btn-sm-outline-secondary-tj:hover,
    .btn-sm-outline-danger-tj:hover {
        background-color: #e91e63;
        color: #fff;
        border-color: #e91e63;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
    }
    .loading-spinner {
        display: none;
        border: 4px solid rgba(0, 0, 0, 0.1);
        border-left-color: #e91e63;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        animation: spin 1s linear infinite;
        margin-left: 10px;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .button-content {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-info {
        background-color: #6c757d;
        color: #fff;
        border: none;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 30px;
        transition: background-color 0.3s ease;
    }
    .btn-info:hover {
        background-color: #5a6268;
    }
    #clearInputsBtn {
        display: flex;
        align-items: center;
        gap: 5px;
    }
</style>

<div class="container-tj">
    <h2 class="h2-tj">CK Teacher Jokes!</h2>
    <p class="p-tj">Generate teacher-friendly jokes based on any topic and grade level.</p>

    {{-- Display validation errors and general errors --}}
    @if ($errors->any() || (isset($errorMessage) && $errorMessage))
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
                {{-- Only display custom errorMessage if it's set and not empty --}}
                @if (isset($errorMessage) && $errorMessage)
                    <li>{{ $errorMessage }}</li>
                @endif
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('teacherjokes.generate') }}" id="teacherjokes-form">
        @csrf
        <div style="margin-bottom: 18px;">
            <label for="topic" style="font-weight:500;">Topic: <span style="color:red">*</span></label>
            {{-- Use null coalesce operator (??) to provide empty string default if variables are not set --}}
            <textarea id="topic" name="topic" class="form-control form-control-tj" rows="2" placeholder="Natural sciences: Biology, chemistry, physics, astronomy, and Earth science." required>{{ old('topic', $topic ?? '') }}</textarea>
        </div>
        <div style="margin-bottom: 18px;">
            <label for="grade_level" style="font-weight:500;">Grade level: <span style="color:red">*</span></label>
            <select id="grade_level" name="grade_level" class="form-control form-control-tj" required>
                {{-- Use null coalesce operator for $grade_level --}}
                <option value="Pre-K" {{ (old('grade_level', $grade_level ?? '') == 'Pre-K') ? 'selected' : '' }}>Pre-K</option>
                <option value="Kindergarten" {{ (old('grade_level', $grade_level ?? '') == 'Kindergarten') ? 'selected' : '' }}>Kindergarten</option>
                <option value="1st Grade" {{ (old('grade_level', $grade_level ?? '') == '1st Grade') ? 'selected' : '' }}>1st Grade</option>
                <option value="2nd Grade" {{ (old('grade_level', $grade_level ?? '') == '2nd Grade') ? 'selected' : '' }}>2nd Grade</option>
                <option value="3rd Grade" {{ (old('grade_level', $grade_level ?? '') == '3rd Grade') ? 'selected' : '' }}>3rd Grade</option>
                <option value="4th Grade" {{ (old('grade_level', $grade_level ?? '') == '4th Grade') ? 'selected' : '' }}>4th Grade</option>
                <option value="5th Grade" {{ (old('grade_level', $grade_level ?? '') == '5th Grade') ? 'selected' : '' }}>5th Grade</option>
                <option value="6th Grade" {{ (old('grade_level', $grade_level ?? '') == '6th Grade') ? 'selected' : '' }}>6th Grade</option>
                <option value="7th Grade" {{ (old('grade_level', $grade_level ?? '') == '7th Grade') ? 'selected' : '' }}>7th Grade</option>
                <option value="8th Grade" {{ (old('grade_level', $grade_level ?? '') == '8th Grade') ? 'selected' : '' }}>8th Grade</option>
                <option value="9th Grade" {{ (old('grade_level', $grade_level ?? '') == '9th Grade') ? 'selected' : '' }}>9th Grade</option>
                <option value="10th Grade" {{ (old('grade_level', $grade_level ?? '') == '10th Grade') ? 'selected' : '' }}>10th Grade</option>
                <option value="11th Grade" {{ (old('grade_level', $grade_level ?? '') == '11th Grade') ? 'selected' : '' }}>11th Grade</option>
                <option value="12th Grade" {{ (old('grade_level', $grade_level ?? '') == '12th Grade') ? 'selected' : '' }}>12th Grade</option>
                <option value="University" {{ (old('grade_level', $grade_level ?? '') == 'University') ? 'selected' : '' }}>University</option>
                <option value="College" {{ (old('grade_level', $grade_level ?? '') == 'College') ? 'selected' : '' }}>College</option>
                <option value="Professional" {{ (old('grade_level', $grade_level ?? '') == 'Professional') ? 'selected' : '' }}>Professional</option>
            </select>
        </div>
        <div style="display:flex; gap:16px; align-items:center; margin-bottom: 24px;">
            <button type="submit" class="btn btn-primary-tj" id="generate-btn">
                <span class="button-content">
                    Generate Teacher Joke
                    <span class="loading-spinner" id="loading-spinner"></span>
                </span>
            </button>
            <button type="button" class="btn btn-info" id="loadExemplarBtn">Load Example</button>
            <button type="button" class="btn btn-secondary" id="clearInputsBtn" style="background: transparent; border: none; color: #e91e63; font-weight: 600; box-shadow: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#e91e63"
                    class="bi bi-arrow-clockwise me-1" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                        d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 1 1 .908-.418A4 4 0 1 0 8 4V1.5a.5.5 0 0 1 1 0v3A.5.5 0 0 1 8.5 5h-3a.5.5 0 0 1 0-1H8z" />
                </svg>
                <span style="color: #e91e63;">Clear Inputs</span>
            </button>
        </div>
    </form>

    {{-- Display the generated joke (if available) --}}
    @if(isset($joke) && $joke)
        <div class="joke-display-tj">
            <strong>Here's your Teacher Joke:</strong><br>
            <em>{{ $joke }}</em>
            <div style="margin-top: 20px;">
                <form action="{{ route('teacherjokes.download') }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="content" value="{{ $joke }}">
                    <input type="hidden" name="filename" value="teacher_joke">
                    <input type="hidden" name="format" value="txt">
                    <button type="submit" class="btn btn-sm-outline-secondary-tj">Save as Text</button>
                </form>
                <form action="{{ route('teacherjokes.download') }}" method="POST" style="display:inline; margin-left: 10px;">
                    @csrf
                    <input type="hidden" name="content" value="{{ $joke }}">
                    <input type="hidden" name="filename" value="teacher_joke">
                    <input type="hidden" name="format" value="pdf">
                    <button type="submit" class="btn btn-sm-outline-danger-tj">Save as PDF</button>
                </form>
            </div>
        </div>
    @endif
</div>

{{-- The script remains the same for button/spinner control --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const teacherjokesForm = document.getElementById('teacherjokes-form');
        const generateBtn = document.getElementById('generate-btn');
        const loadingSpinner = document.getElementById('loading-spinner');
        const topicInput = document.getElementById('topic');
        const gradeLevelSelect = document.getElementById('grade_level');

        teacherjokesForm.addEventListener('submit', function() {
            loadingSpinner.style.display = 'inline-block';
            generateBtn.setAttribute('disabled', 'disabled');
            generateBtn.style.opacity = '0.7';
        });

        document.getElementById('loadExemplarBtn').addEventListener('click', () => {
            topicInput.value = 'Natural sciences: Biology, chemistry, physics, astronomy, and Earth science.';
            gradeLevelSelect.value = 'University';
        });

        document.getElementById('clearInputsBtn').addEventListener('click', () => {
            topicInput.value = '';
            gradeLevelSelect.value = 'Pre-K';
        });

        // Ensure the button is re-enabled if there's an error on page load
        @if (isset($errorMessage) && $errorMessage || $errors->any())
            generateBtn.removeAttribute('disabled');
            generateBtn.style.opacity = '1';
            loadingSpinner.style.display = 'none';
        @endif
    });
</script>
@endsection