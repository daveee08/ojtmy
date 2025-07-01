<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Multiple Explanations Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(to right, #ffe6ec, #ffffff);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #191919;
            padding: 4rem 1rem;
        }

        .container {
            background: #ffffff;
            max-width: 720px;
            padding: 3rem 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        h2 {
            font-weight: 800;
            font-size: 2rem;
            text-align: center;
            color: #EC298B;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            text-align: center;
            font-size: 1rem;
            margin-bottom: 2rem;
            color: #555;
        }

        label {
            font-weight: 600;
            color: #191919;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            font-size: 1rem;
            color: #191919;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #555;
            box-shadow: 0 0 0 0.1rem rgba(48, 48, 48, 0.25);
        }

        .btn-pink {
            background-color: #EC298B;
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 0.6rem 2rem;
            border-radius: 8px;
            border: none;
            transition: 0.3s;
            min-width: 130px;
        }

        .btn-pink:hover {
            background-color: #d81b60;
        }

        .spinner-border.text-pink {
            color: #fff;
        }

        .hidden {
            display: none !important;
        }

        textarea[readonly] {
            background-color: #ffffff;
            color: #191919;
        }

        #output b {
            display: block;
            margin-top: 1rem;
            font-size: 1.1rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Multiple Explanations Generator</h2>
        <p class="subtitle">Generate several versions of an explanation for different learning styles or levels.</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/explanations" onsubmit="handleGenerateSubmit()" enctype="multipart/form-data">
            @csrf

            <!-- Grade Level -->
            <div class="mb-4">
                <label for="grade_level" class="form-label">Select Grade Level</label>
                <select class="form-select" id="grade_level" name="grade_level" required>
                    <option value="" disabled selected>Select grade</option>
                    @foreach (range(1, 12) as $grade)
                        <option value="Grade {{ $grade }}">Grade {{ $grade }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Input Type Selection -->
            <div class="mb-4">
                <label for="input_type" class="form-label">Select Input Type</label>
                <select class="form-select" id="input_type" name="input_type" required onchange="toggleInputFields()">
                    <option value="" disabled selected>Choose input method</option>
                    <option value="topic">Text</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>

            <!-- Concept Text Field -->
            <div class="mb-4" id="text_input_group" style="display: none;">
                <label for="concept" class="form-label">Concept Being Taught</label>
                <textarea class="form-control" id="concept" name="concept" rows="5"
                    placeholder="E.g., Photosynthesis, Division with Remainders..."></textarea>
            </div>

            <!-- PDF Upload Field -->
            <div class="mb-4" id="pdf_input_group" style="display: none;">
                <label for="pdf_file" class="form-label">Upload PDF</label>
                <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept="application/pdf" />
            </div>

            <!-- Submit Button -->
            <div class="mb-4 text-center">
                <button type="submit" class="btn btn-pink" id="submitButton">
                    <span id="btnText">Generate</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm text-pink hidden" role="status"
                        aria-hidden="true"></span>
                </button>
            </div>

            <!-- Loading Message -->
            <div id="loadingMessage" class="text-center hidden">
                <p class="mt-2">Generating explanations, please wait...</p>
            </div>
        </form>

        <!-- Output -->
        <div class="mb-4">
            <label for="output" class="form-label">Generated Explanation</label>
            <div id="output" class="form-control" style="min-height: 300px; white-space: pre-wrap;">
                {!! $response ?? '' !!}
            </div>
        </div>


        <script>
            function toggleInputFields() {
                const mode = document.getElementById('input_type').value;
                const textGroup = document.getElementById('text_input_group');
                const pdfGroup = document.getElementById('pdf_input_group');

                textGroup.style.display = mode === 'topic' ? 'block' : 'none';
                pdfGroup.style.display = mode === 'pdf' ? 'block' : 'none';
            }

            function handleGenerateSubmit() {
                const btn = document.getElementById("submitButton");
                const text = document.getElementById("btnText");
                const spinner = document.getElementById("btnSpinner");
                const message = document.getElementById("loadingMessage");

                btn.disabled = true;
                text.classList.add("hidden");
                spinner.classList.remove("hidden");
                message.classList.remove("hidden");
            }
        </script>
</body>

</html>
