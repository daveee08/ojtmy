@extends('layouts.bootstrap')
@extends('layouts.header')
@extends('layouts.navbaragent')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> Study Habits Planner</title>

    {{-- Bootstrap + Fonts --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

   <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .text-highlight {
            color: #ec008c;
            font-weight: 700;
        }
        .form-label {
            color: #333;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #ec008c;
            border-color: #ec008c;
        }
        .btn-primary:hover {
            background-color: #c30074;
            border-color: #c30074;
        }
    </style>
</head>
<body>
<div class="container my-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="text-center text-highlight mb-3">📚 Study Habits Planner</h2>
            <p class="text-muted text-center mb-4">Get a plan and study tips to prepare for any test, assignment, or project.</p>

            <form action="{{ route('studyhabits.process') }}" method="POST" id="studyForm">
                @csrf

                <div class="mb-3">
                    <label for="grade_level" class="form-label">Grade level:</label>
                    <select class="form-select" name="grade_level" id="grade_level" required>
                        <option value="">-- Select --</option>
                        <option value="kindergarten" {{ old('grade_level') == 'kindergarten' ? 'selected' : '' }}>Kindergarten</option>
                        <option value="elementary" {{ old('grade_level') == 'elementary' ? 'selected' : '' }}>Elementary</option>
                        <option value="junior high" {{ old('grade_level') == 'junior high' ? 'selected' : '' }}>Junior High</option>
                        <option value="senior high" {{ old('grade_level') == 'senior high' ? 'selected' : '' }}>Senior High</option>
                        <option value="college" {{ old('grade_level') == 'college' ? 'selected' : '' }}>College</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="goal" class="form-label">Describe what you're preparing for:</label>
                    <textarea class="form-control" name="goal" id="goal" rows="4" placeholder="e.g. I need to study for the SAT in 2 months..." required>{{ old('goal') }}</textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <span id="btnText">Generate Plan</span>
                        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

            @if(isset($plan))
                <div class="alert alert-info mt-4">
                    <h5 class="text-highlight">Your Study Plan:</h5>
                    <pre class="mb-0">{{ $plan }}</pre>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger mt-4">
                    <ul class="mb-0">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Fullscreen Loader --}}
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex d-none justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 9999;">
    <div class="text-center">
        <div class="spinner-border text-highlight mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <div class="fw-semibold text-highlight">Generating your study plan...</div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('studyForm');
        const btn = form.querySelector('button');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const overlay = document.getElementById('loadingOverlay');

        form.addEventListener('submit', function () {
            btn.disabled = true;
            btnText.textContent = 'Generating...';
            btnSpinner.classList.remove('d-none');
            overlay.classList.remove('d-none');
        });
    });
</script>
</body>
</html>
