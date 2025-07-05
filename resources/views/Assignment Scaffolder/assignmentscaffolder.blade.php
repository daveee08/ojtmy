<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>AI Assignment Scaffolder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous" />
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
            position: relative;
            display: center;
            align-items: center;
            justify-content: center;
            min-width: 130px;
        }

        .btn-pink:hover {
            background-color: #d81b60;
        }

        .spinner-border.text-pink {
            color: #fff;
            display: center;
            align-items: center;
        }

        .btn-spinner {
            margin-left: 10px;
            vertical-align: middle;
        }

        .hidden {
            display: none !important;
        }

        textarea[readonly] {
            background-color: #ffffff;
            color: #191919;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>AI Assignment Scaffolder</h2>
        <p class="subtitle">Break assignments down into manageable steps for students.</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/assignmentscaffolder" enctype="multipart/form-data"
            onsubmit="handleRewriteSubmit()">
            @csrf

            <!-- Input Type Selection -->
            <div class="mb-4">
                <label for="input_type" class="form-label">Select Input Type</label>
                <select class="form-select" id="input_type" name="input_type" required onchange="toggleInputFields()">
                    <option value="" disabled>Choose input method</option>
                    <option value="topic">Text</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>

            <!-- Input Text Field -->
            <div class="mb-4" id="text_input_group" style="display: none;">
                <label for="topic" class="form-label">Enter Text</label>
                <textarea class="form-control" id="topic" name="topic" rows="6" placeholder="Paste or type text here"></textarea>
            </div>

            <!-- PDF Upload Field -->
            <div class="mb-4" id="pdf_input_group" style="display: none;">
                <label for="pdf" class="form-label">Upload PDF</label>
                <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept="application/pdf" />
            </div>

            <!-- Grade Level -->
            <div class="mb-4" id="grade_level_group" style="display: none;">
                <label for="grade_level" class="form-label">Grade Level</label>
                <select class="form-select" id="grade_level" name="grade_level">
                    <option value="" disabled selected>Select grade level</option>
                    <option value="Kindergarten">Kindergarten</option>
                    <option value="Grade 1">Grade 1</option>
                    <option value="Grade 2">Grade 2</option>
                    <option value="Grade 3">Grade 3</option>
                    <option value="Grade 4">Grade 4</option>
                    <option value="Grade 5">Grade 5</option>
                    <option value="Grade 6">Grade 6</option>
                    <option value="Grade 7">Grade 7</option>
                    <option value="Grade 8">Grade 8</option>
                    <option value="Grade 9">Grade 9</option>
                    <option value="Grade 10">Grade 10</option>
                    <option value="Grade 11">Grade 11</option>
                    <option value="Grade 12">Grade 12</option>
                    <option value="University">University</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="mb-4 text-center">
                <button type="submit" class="btn btn-pink" id="submitButton">
                    <span id="btnText">Generate</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm btn-spinner hidden" role="status"
                        aria-hidden="true"></span>
                </button>
            </div>

            <!-- Loading Message -->
            <div id="loadingMessage" class="text-center hidden">
                <p class="mt-2">Scaffolding in progress, please wait...</p>
            </div>
        </form>

        <!-- Adaptive Content Output -->
        <div class="mb-4">
            <label for="generate_output" class="form-label">Scaffolded Output</label>
            <textarea id="generate_output" class="form-control" name="generate_output" rows="10" readonly>{{ $response ?? '' }}</textarea>
        </div>
    </div>

    <script>
        function toggleInputFields() {
            const mode = document.getElementById('input_type').value;
            document.getElementById('text_input_group').style.display = mode === 'topic' ? 'block' : 'none';
            document.getElementById('pdf_input_group').style.display = mode === 'pdf' ? 'block' : 'none';

            // Always show these when an input type is selected
            const showExtras = mode !== '';
            document.getElementById('grade_level_group').style.display = showExtras ? 'block' : 'none';
            document.getElementById('literal_questions_group').style.display = showExtras ? 'block' : 'none';
            document.getElementById('vocab_words_group').style.display = showExtras ? 'block' : 'none';
        }

        function handleRewriteSubmit() {
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

{{-- original --}}
