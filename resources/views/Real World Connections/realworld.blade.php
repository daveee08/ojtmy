@extends('layouts.bootstrap')
@extends('layouts.header')
@extends('layouts.navbaragent')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title> Real World Connections</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
       <style>
        :root {
            --pink: #e91e63;
            --white: #ffffff;
            --dark: #191919;
            --light-grey: #f5f5f5;
        }

        [data-bs-theme="dark"] {
            --pink: #f06292;
            --white: #333333; /* Lightened from #1e1e1e to #333333 for better visibility */
            --dark: #d0d0d0; /* Lightened from #e0e0e0 to #d0d0d0 for contrast */
            --light-grey: #444444; /* Adjusted to a slightly lighter gray */
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--white);
            color: var(--dark);
        }

        .text-highlight {
            color: var(--pink);
            font-weight: 700;
        }

        .form-label {
            color: var(--dark);
            font-weight: 600;
        }

        .btn-primary {
            background-color: var(--pink);
            border-color: var(--pink);
        }

        .btn-primary:hover {
            background-color: #c30074;
            border-color: #c30074;
        }

        .card {
            background-color: var(--white);
            border: 1px solid var(--light-grey);
        }

        .bg-light {
            background-color: var(--light-grey) !important;
        }

        .btn-outline-secondary {
            color: var(--dark);
            border-color: var(--light-grey);
        }

        .btn-outline-secondary:hover {
            background-color: var(--light-grey);
            color: var(--pink);
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        [data-bs-theme="dark"] .alert-danger {
            background-color: #2c2c2c;
            color: #f06292;
        }

        /* Style for new Clear Form button */
        .btn-clear {
            background-color: #6c757d;
            border-color: #6c757d;
            color: var(--white);
        }

        .btn-clear:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }
    </style>
</head>
<body>
<div class="container my-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="text-center text-highlight mb-3">🌍 Real World Connections</h2>
            <p class="text-muted text-center mb-4">
                Generate real-world examples for what you're learning about!
            </p>

            <form action="{{ route('realworld.process') }}" method="POST" id="realworldForm" onsubmit="showLoading()">
                @csrf

                <div class="mb-3">
                    <label for="grade_level" class="form-label">Grade Level:</label>
                    <select class="form-select" name="grade_level" id="grade_level" required>
                        <option value="">-- Select Grade --</option>
                        <option value="kindergarten" {{ old('grade_level', $old['grade_level'] ?? '') === 'kindergarten' ? 'selected' : '' }}>Kindergarten</option>
                        <option value="elementary" {{ old('grade_level', $old['grade_level'] ?? '') === 'elementary' ? 'selected' : '' }}>Elementary</option>
                        <option value="junior high" {{ old('grade_level', $old['grade_level'] ?? '') === 'junior high' ? 'selected' : '' }}>Junior High</option>
                        <option value="senior high" {{ old('grade_level', $old['grade_level'] ?? '') === 'senior high' ? 'selected' : '' }}>Senior High</option>
                        <option value="college" {{ old('grade_level', $old['grade_level'] ?? '') === 'college' ? 'selected' : '' }}>College</option>
                    </select>
                    @error('grade_level')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="topic" class="form-label">Topic you're learning about (be specific):</label>
                    <textarea class="form-control" name="topic" id="topic" rows="4" placeholder="e.g. Greenhouse gases and their effects..." required>{{ old('topic', $old['topic'] ?? '') }}</textarea>
                    @error('topic')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="d-grid d-md-flex justify-content-md-end">
                    <button type="submit" id="submitBtn" class="btn btn-primary px-4">
                        <span id="btnText">Generate</span>
                        <button type="button" id="clearBtn" class="btn btn-clear me-2">Clear Form</button>

                        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

            @if(isset($output) && count($output))
                <div class="alert alert-info mt-4">
                    <h5 class="text-highlight">🌱 Real-World Examples</h5>
                    <ul class="mb-0">
                        @foreach($output as $example)
                            <li>{!! $example !!}</li> {{-- This will allow HTML tags like <strong> to render --}}
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($errors->has('error'))
                <div class="alert alert-danger mt-4">
                    {{ $errors->first('error') }}
                </div>
                
            @endif
        </div>
    </div>
</div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex d-none justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 9999;">
    <div class="text-center">
        <div class="spinner-border text-highlight mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <div class="fw-semibold text-highlight">Generating real-world examples...</div>
    </div>
</div>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showLoading() {
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('btnText').textContent = 'Generating...';
        document.getElementById('btnSpinner').classList.remove('d-none');
        document.getElementById('loadingOverlay').classList.remove('d-none');
    }
     // Clear Form functionality
    document.getElementById('clearBtn').addEventListener('click', () => {
        document.getElementById('grade_level').value = '';
        document.getElementById('topic').value = '';
    });
</script>
</body>
</html>
