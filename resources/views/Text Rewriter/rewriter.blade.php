<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>AI Text Rewriter</title>
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
        <h2>AI Text Rewriter</h2>
        <p class="subtitle">Rewrite any text using any criteria you choose.</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/rewriter" enctype="multipart/form-data" onsubmit="handleRewriteSubmit()">
            @csrf

            <!-- Input Type Selection -->
            <div class="mb-4">
                <label for="input_type" class="form-label">Select Input Type</label>
                <select class="form-select" id="input_type" name="input_type" required onchange="toggleInputFields()">
                    <option value="" disabled selected>Choose input method</option>
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

            <!-- Custom Instruction Field (Hidden at first) -->
            <div class="mb-4" id="custom_instruction_group" style="display: none;">
                <label for="custom_instruction" class="form-label">Rewrite so that: </label>
                <textarea class="form-control" id="custom_instruction" name="custom_instruction" rows="2"
                    placeholder="Customize how the text should be rewritten e.g., different words, half as long, etc."></textarea>
            </div>

            <!-- Submit Button -->
            <div class="mb-4 text-center">
                <button type="submit" class="btn btn-pink" id="submitButton">
                    <span id="btnText">Rewrite</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm btn-spinner hidden" role="status"
                        aria-hidden="true"></span>
                </button>
            </div>

            <!-- Loading Message -->
            <div id="loadingMessage" class="text-center hidden">
                <p class="mt-2">Rewriting in progress, please wait...</p>
            </div>
        </form>

        <!-- Adaptive Content Output -->
        <div class="mb-4">
            <label for="generate_output" class="form-label">Rewritten Output</label>
            <textarea id="generate_output" class="form-control" name="generate_output" rows="10" readonly>{{ $response ?? '' }}</textarea>
        </div>
    </div>

    <script>
        function toggleInputFields() {
            const mode = document.getElementById('input_type').value;
            const textGroup = document.getElementById('text_input_group');
            const pdfGroup = document.getElementById('pdf_input_group');
            const instructionGroup = document.getElementById('custom_instruction_group');

            textGroup.style.display = mode === 'topic' ? 'block' : 'none';
            pdfGroup.style.display = mode === 'pdf' ? 'block' : 'none';
            instructionGroup.style.display = mode ? 'block' : 'none';
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
