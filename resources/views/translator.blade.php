<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Text Translator Agent</title>

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
            <h2 class="text-center text-highlight mb-3">🌐 Text Translator</h2>
            <p class="text-muted text-center mb-4">Translate any text into your selected language.</p>

            <form action="{{ route('translator.process') }}" method="POST" id="translateForm">
                @csrf

                <div class="mb-3">
                    <label for="language" class="form-label">Target Language:</label>
                    <input type="text" class="form-control" name="language" id="language" placeholder="e.g. Spanish, Filipino, French" required value="{{ old('language', $old['language'] ?? '') }}">
                    @error('language')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="text" class="form-label">Text to Translate:</label>
                    <textarea class="form-control" name="text" id="text" rows="4" required>{{ old('text', $old['text'] ?? '') }}</textarea>
                    @error('text')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="d-grid d-md-flex justify-content-md-end">
                    <button type="submit" id="submitBtn" class="btn btn-primary px-4">
                        <span id="btnText">Translate</span>
                        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

            @if(isset($translation))
                <div class="alert alert-success mt-4">
                    <h5 class="text-highlight">Translated Text:</h5>
                    <p>{{ $translation }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger mt-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Fullscreen loader --}}
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex d-none justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 9999;">
    <div class="text-center">
        <div class="spinner-border text-highlight mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <div class="fw-semibold text-highlight">Translating your text...</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('translateForm');
        const btn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const overlay = document.getElementById('loadingOverlay');

        form.addEventListener('submit', function () {
            btn.disabled = true;
            btnText.textContent = 'Translating...';
            btnSpinner.classList.remove('d-none');
            overlay.classList.remove('d-none');
        });
    });
</script>
</body>
</html>
