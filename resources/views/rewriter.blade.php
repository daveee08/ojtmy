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
            color: #2c2c2c;
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
            color: #e91e63;
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
            color: #2c2c2c;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            font-size: 1rem;
            color: #2c2c2c;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #e91e63;
            box-shadow: 0 0 0 0.2rem rgba(233, 30, 99, 0.25);
        }

        .btn-pink {
            background-color: #e91e63;
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 0.6rem 2rem;
            border-radius: 8px;
            border: none;
            transition: 0.3s;
        }

        .btn-pink:hover {
            background-color: #d81b60;
        }

        textarea[readonly] {
            background-color: #ffffff;
            color: #2c2c2c;

        }
    </style>
</head>

<body>
    <div class="container">
        <h2>AI Text Rewriter</h2>
        <p class="subtitle">Rewrite any content to match your learning pace</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/rewriter" enctype="multipart/form-data">
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

            <!-- Custom Instruction Field -->
            <div class="mb-4">
                <label for="custom_instruction" class="form-label">Rewrite so that: </label>
                <textarea class="form-control" id="custom_instruction" name="custom_instruction" rows="4"
                    placeholder="Customize How the Text Should Be Rewritten e.g., different words, half as long, it includes the following details"></textarea>
            </div>


            <!-- Input Text Field -->
            <div class="mb-4" id="text_input_group" style="display: none;">
                <label for="topic" class="form-label">Enter Text</label>
                <textarea class="form-control" id="topic" name="topic" rows="6" placeholder="Paste or type text here"></textarea>
            </div>

            <!-- Submit Button -->
            <div class="mb-4 text-center">
                <button type="submit" class="btn btn-pink">Rewrite</button>
            </div>
        </form>

        <!-- Adaptive Content Output -->
        <div class="mb-4">
            <label for="generate_output" class="form-label">Rewritten Output</label>
            {{-- <textarea class="form-control" id="adaptive_content" name="adaptive_content" rows="10" readonly></textarea> --}}
            <textarea id="generate_output" class="form-control" name="generate_output" rows="10" readonly>{{ $response ?? '' }}</textarea>
        </div>
    </div>

    <script>
        function toggleInputFields() {
            const mode = document.getElementById('input_type').value;
            const textGroup = document.getElementById('text_input_group');
            const pdfGroup = document.getElementById('pdf_input_group');

            if (mode === 'topic') {
                textGroup.style.display = 'block';
                pdfGroup.style.display = 'none';
            } else if (mode === 'pdf') {
                textGroup.style.display = 'none';
                pdfGroup.style.display = 'block';
            } else {
                textGroup.style.display = 'none';
                pdfGroup.style.display = 'none';
            }
        }
    </script>

</body>

</html>
