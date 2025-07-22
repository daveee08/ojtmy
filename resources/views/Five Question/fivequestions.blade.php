@extends('layouts.bootstrap')
@extends('layouts.header')
@extends('layouts.navbaragent')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>5 Question Agent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    {{------------------ Custom Style --------------}}
    <style>
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
            <h2 class="text-center text-highlight mb-3">🧠 5 Questions Agent</h2>
            <p class="text-muted text-center mb-4">Use AI to ask you 5 questions to push your thinking on any topic or idea.</p>

            {{-- ---------------- Form ---------------- --}}
            <form action="{{ route('sentencestarter.process') }}" method="POST" id="starterForm" onsubmit="showLoading()">
                @csrf

                <div class="mb-3">
                    <label for="grade_level" class="form-label">Select Grade Level:</label>
                    <select class="form-select" name="grade_level" id="grade_level" required>
                        <option value="">-- Choose --</option>
                        <option value="kindergarten" {{ old('grade_level', $old['grade_level'] ?? '') === 'kindergarten' ? 'selected' : '' }}>Kindergarten</option>
                        <option value="elementary" {{ old('grade_level', $old['grade_level'] ?? '') === 'elementary' ? 'selected' : '' }}>Elementary</option>
                        <option value="junior high" {{ old('grade_level', $old['grade_level'] ?? '') === 'junior high' ? 'selected' : '' }}>Junior High</option>
                        <option value="senior high" {{ old('grade_level', $old['grade_level'] ?? '') === 'senior high' ? 'selected' : '' }}>Senior High</option>
                        <option value="college" {{ old('grade_level', $old['grade_level'] ?? '') === 'college' ? 'selected' : '' }}>College</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="topic" class="form-label">Ask me questions to push my thinking about:</label>
                    <textarea class="form-control" name="topic" id="topic" rows="4" required>{{ old('topic', $old['topic'] ?? '') }}</textarea>
                </div>

                <div class="d-grid d-md-flex justify-content-md-end mb-3">
                    <button type="button" id="clearBtn" class="btn btn-clear me-2">Clear Form</button>
                    <button type="submit" id="submitBtn" class="btn btn-primary">
                        <span id="btnText">Generate Questions</span>
                        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
            {{-- ---------------- Output ---------------- --}}   
            @if(isset($sentence_starters) && is_array($sentence_starters) && count($sentence_starters))
                <div class="mt-5">
                    <h5 class="text-highlight">5 Questions:</h5>

                    {{-- @foreach($sentence_starters as $sentence)
                        <div class="d-flex justify-content-between align-items-start bg-light p-3 mb-3 rounded shadow-sm position-relative border border-1">
                            <p class="mb-0 me-3 flex-grow-1" style="word-break: break-word;">{{ $sentence }}</p>
                            <button onclick="copyToClipboard(this)" class="btn btn-sm btn-outline-secondary" title="Copy">
                                📋
                            </button>
                        </div>
                    @endforeach --}}

                    {{-- One Follow-Up Box --}}
                    {{-- <form action="{{ route('sentencestarter.followup') }}" method="POST" class="mt-4">
                        @csrf
                        <input type="hidden" name="message_id" value="{{ $message_id ?? '' }}">
                        <input type="hidden" name="grade_level" value="{{ old('grade_level', $old['grade_level'] ?? 'college') }}">

                        <label for="followup" class="form-label">Ask a follow-up question about the topic or one of the starters:</label>
                        <div class="input-group">
                            <input type="text" name="followup" id="followup" class="form-control" placeholder="Enter your follow-up question here..." required>
                            <button type="submit" class="btn btn-outline-primary">Ask</button>
                        </div>
                    </form> --}}
                </div>
            @endif

            {{-- ---------------- Error ---------------- --}}
            @if($errors->has('error'))
                <div class="alert alert-danger mt-4">
                    {{ $errors->first('error') }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ------------ Loading Overlay ------------ --}}
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex d-none justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 9999;" data-bs-theme="light">
    <div class="text-center">
        <div class="spinner-border text-highlight mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <div class="fw-semibold text-highlight">Generating Five Questions...</div>
    </div>
</div>

 {{-- ------------ Script------------ --}}
<script>
    function showLoading() {
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('btnText').textContent = 'Generating...';
        document.getElementById('btnSpinner').classList.remove('d-none');
        document.getElementById('loadingOverlay').classList.remove('d-none');
    }

    // function copyToClipboard(btn) {
    //     const sentence = btn.parentElement.querySelector('p').innerText;
    //     navigator.clipboard.writeText(sentence).then(() => {
    //         btn.innerText = '✅';
    //         setTimeout(() => btn.innerText = '📋', 1500);
    //     });
    // }

    // Clear Form functionality
    document.getElementById('clearBtn').addEventListener('click', () => {
        document.getElementById('grade_level').value = '';
        document.getElementById('topic').value = '';
    });
</script>
</body>
</html>