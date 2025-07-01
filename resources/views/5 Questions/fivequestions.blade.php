<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>5 Questions Agent</title>

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Google Fonts --}}
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
                <h2 class="text-center text-highlight mb-3">🧠 5 Questions Agent</h2>
                <p class="text-muted text-center mb-4">
                    Use AI to ask you 5 questions to push your thinking on any topic or idea.
                </p>

                <form action="{{ route('fivequestions.process') }}" method="POST" id="questionForm">
                    @csrf

                    <div class="mb-3">
                        <label for="grade_level" class="form-label">Select Grade Level:</label>
                        <select class="form-select" name="grade_level" id="grade_level" required>
                            <option value="">-- Choose --</option>
                            <option value="elementary" {{ old('grade_level') == 'elementary' ? 'selected' : '' }}>Elementary</option>
                            <option value="junior_high" {{ old('grade_level') == 'junior_high' ? 'selected' : '' }}>Junior High</option>
                            <option value="senior_high" {{ old('grade_level') == 'senior_high' ? 'selected' : '' }}>Senior High</option>
                            <option value="college" {{ old('grade_level') == 'college' ? 'selected' : '' }}>College</option>
                        </select>
                        @error('grade_level')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="prompt" class="form-label">Ask me questions to push my thinking about:</label>
                        <textarea class="form-control" name="prompt" id="prompt" rows="4" placeholder="e.g. The importance of recycling..." required>{{ old('prompt') }}</textarea>
                        @error('prompt')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="d-grid d-md-flex justify-content-md-end">
                        <button type="submit" id="submitBtn" class="btn btn-primary px-4">
                            <span id="btnText">Generate</span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>

                @if(isset($questions))
                    <div class="alert alert-success mt-4">
                        <h5 class="text-highlight">Here are your 5 AI-generated questions:</h5>
                        <ol>
                            @foreach($questions as $q)
                                <li>{{ $q }}</li>
                            @endforeach
                        </ol>
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
            <div class="fw-semibold text-highlight">Please wait...</div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('questionForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            const loadingOverlay = document.getElementById('loadingOverlay');

            form.addEventListener('submit', function () {
                loadingOverlay.classList.remove('d-none');
                submitBtn.disabled = true;
                btnText.textContent = 'Generating...';
                btnSpinner.classList.remove('d-none');
            });
        });
    </script>
</body>
</html>
