<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> AI Agent Text Proofreader</title>

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Google Font: Poppins --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    {{-- Custom Style --}}
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
                <h1 class="card-title text-center mb-3">
                 <span class="text-highlight">🕵️ AI Agent Text Proofreader</span>
                </h1>
                <h5 class="text-muted text-center mb-4">
                    This tool helps you proofread your text using AI—fixing grammar, spelling, punctuation, and clarity.
                </h5>

                <form action="{{ route('proofreader.process') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Profile selection --}}
                    <div class="mb-3">
                        <label for="profile" class="form-label">Choose a profile type:</label>
                        <select class="form-select" id="profile" name="profile">
                            <option value="academic" {{ old('profile', $old['profile'] ?? '') === 'academic' ? 'selected' : '' }}>Academic</option>
                            <option value="casual" {{ old('profile', $old['profile'] ?? '') === 'casual' ? 'selected' : '' }}>Casual</option>
                            <option value="concise" {{ old('profile', $old['profile'] ?? '') === 'concise' ? 'selected' : '' }}>Concise</option>
                        </select>
                        @error('profile')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Input type toggle --}}
                    <div class="mb-3">
                        <label for="input_type" class="form-label">Choose input type:</label>
                        <select class="form-select" id="input_type" onchange="toggleInputType()">
                            <option value="text">Text Input</option>
                            {{-- <option value="pdf">PDF Upload</option> --}}
                        </select>
                    </div>

                    {{-- Text input --}}
                    <div class="mb-3" id="text-input-group">
                        <label for="text" class="form-label">Enter your text:</label>
                        <textarea class="form-control" id="text" name="text" rows="10" placeholder="Paste your text here...">{{ old('text', $old['text'] ?? '') }}</textarea>
                        @error('text')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- PDF upload --}}
                    {{-- <div class="mb-3 d-none" id="pdf-input-group">
                        <label for="pdf" class="form-label">Upload a PDF to proofread:</label>
                        <input class="form-control" type="file" id="pdf" name="pdf" accept="application/pdf">
                        @error('pdf')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div> --}}

                    {{-- Submit button with spinner --}}
                    <div class="d-grid d-md-flex justify-content-md-end">
                        <button type="submit" id="submitBtn" class="btn btn-primary px-4" disabled>
                            <span id="btnText">Submit</span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>

                {{-- Response --}}
                @if(isset($response))
                    <div class="alert alert-success mt-4">
                        <h5 class="text-highlight">Corrected Text</h5>
                        <p>{{ $response['corrected'] }}</p>

                        <h6 class="text-highlight">Changes Made</h6>
                        <ul>
                            @foreach($response['changes'] as $change)
                                <li>{{ ltrim($change, '* ') }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Error --}}
                @if($errors->has('error'))
                    <div class="alert alert-danger mt-4">
                        {{ $errors->first('error') }}
                    </div>
                @endif
            </div>
        </div>
       {{-- Fullscreen loading overlay --}}
    <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 justify-content-center align-items-center bg-white bg-opacity-75 d-none" style="z-index: 9999; display: flex;">
        <div class="text-center">
            <div class="spinner-border text-highlight mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
            <div class="fw-semibold text-highlight">Please wait...</div>
        </div>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Toggle Input + Loading Spinner --}}
    <script>
        function toggleInputType() {
            const inputType = document.getElementById('input_type').value;
            const textGroup = document.getElementById('text-input-group');
            // const pdfGroup = document.getElementById('pdf-input-group');

            if (inputType === 'pdf') {
                textGroup.classList.add('d-none');
                // pdfGroup.classList.remove('d-none');
            } else {
                textGroup.classList.remove('d-none');
                // pdfGroup.classList.add('d-none');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            toggleInputType();

            const form = document.querySelector('form');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            const loadingOverlay = document.getElementById('loadingOverlay');

            submitBtn.disabled = false;

            form.addEventListener('submit', function () {
                loadingOverlay.classList.remove('d-none');
                submitBtn.disabled = true;
                btnText.textContent = 'Submitting...';
                btnSpinner.classList.remove('d-none');
            });
        });
    </script>
</body>
</html>


