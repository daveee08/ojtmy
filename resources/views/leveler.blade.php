<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>AI Text Leveler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous" />
    <style>
        /* Body and container */
        body {
            background: linear-gradient(135deg, #f0f2f5, #ffffff);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 3rem 1rem;
        }

        .container {
            background: white;
            max-width: 700px;
            width: 100%;
            padding: 2.5rem 3rem;
            border-radius: 12px;
        }

        /* Heading */
        h2 {
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Labels */
        label {
            font-weight: 600;
            color: #34495e;
        }

        /* Inputs & Textareas */
        .form-control,
        .form-select {
            border-radius: 8px;
            font-size: 1rem;
            border: 1.5px solid #d1d9e6;
            transition: border-color 0.25s ease;
            box-shadow: none;
            padding: 0.6rem 1rem;
            color: #34495e;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #e63946;
            box-shadow: 0 0 8px rgba(230, 57, 70, 0.3);
            outline: none;
        }

        textarea.form-control {
            resize: none;
            overflow-y: auto;
            max-height: 400px;
            background-color: #fff !important;
            color: #2c3e50;
            font-family: inherit;
            line-height: 1.5;
            padding-top: 0.8rem;
        }

        /* Submit Button */
        .btn-primary {
            background-color: #e63946;
            color: white;
            border: none;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 0.65rem 2.5rem;
            border-radius: 10px;
            cursor: pointer;
            letter-spacing: 0.08em;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #d62839;
            outline: none;
        }

        /* Center button container */
        .text-center {
            margin-top: 1.8rem;
            margin-bottom: 1.8rem;
        }

        /* Adaptive Content label margin */
        #adaptive_content {
            background-color: #ffffff !important;
            color: #2c3e50;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>AI Text Leveler</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/leveler" enctype="multipart/form-data">
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
                <textarea class="form-control" id="topic" name="topic" rows="6"
                    placeholder="Paste or type text here if you're not uploading a PDF..."></textarea>
            </div>

            <!-- PDF Upload Field -->
            <div class="mb-4" id="pdf_input_group" style="display: none;">
                <label for="pdf" class="form-label">Upload PDF</label>
                <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept="application/pdf" />
            </div>

            <!-- Grade Level Selection -->
            <div class="mb-4">
                <label for="grade_level" class="form-label">Grade Level</label>
                <select class="form-select" id="grade_level" name="grade_level" required>
                    <option value="" disabled selected>Select grade level</option>
                    <option value="kinder">Kindergarten</option>
                    <option value="elementary">Elementary</option>
                    <option value="middle">Middle School</option>
                    <option value="high">High School</option>
                    <option value="college">College</option>
                </select>
            </div>

            <!-- Learning Speed Selection -->
            <div class="mb-4">
                <label for="learning_speed" class="form-label">Learning Type</label>
                <select class="form-select" id="learning_speed" name="learning_speed" required>
                    <option value="" disabled selected>Select learning speed</option>
                    <option value="slow">Slow Learner</option>
                    <option value="average">Average Learner</option>
                    <option value="fast">Fast Learner</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="mb-4 text-center">
                <button type="submit" class="btn btn-primary">Generate</button>
            </div>
        </form>

        <!-- Adaptive Content Output -->
        <div class="mb-4">
            <label for="generate_output" class="form-label">Generated Content</label>
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

    <!-- loading spinner -->
    {{-- <script>
        document.querySelector('form[action="{{ url('/leveler') }}"]').addEventListener('submit', function() {
            document.getElementById('loading-overlay').style.display = 'flex';
        });
    </script> --}}

</body>

</html>
