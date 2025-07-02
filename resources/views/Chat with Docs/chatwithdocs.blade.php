<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Chat with Docs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous" />
    <style>
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

        h2 {
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        label {
            font-weight: 600;
            color: #34495e;
        }

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

        .text-center {
            margin-top: 1.8rem;
            margin-bottom: 1.8rem;
        }

        #adaptive_content {
            background-color: #ffffff !important;
            color: #2c3e50;
        }

        .spinner-border.text-pink {
            color: #EC298B;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Chat with Docs</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="chatwithdocsForm" method="POST" action="/chatwithdocs" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="input_type" class="form-label">Select Input Type to Chat With</label>
                <select class="form-select" id="input_type" name="input_type" required onchange="toggleInputFields()">
                    <option value="" disabled selected>Choose input method</option>
                    <option value="topic">Text</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>

            <div class="mb-4" id="text_input_group" style="display: none;">
                <label for="topic" class="form-label">Enter Text</label>
                <textarea class="form-control" id="topic" name="topic" rows="6"
                    placeholder="Paste or type text here if you're not uploading a PDF..."></textarea>
            </div>

            <div class="mb-4" id="pdf_input_group" style="display: none;">
                <label for="pdf" class="form-label">Upload PDF</label>
                <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept="application/pdf" />
            </div>

            <div class="mb-4">
                <label for="input_type_1" class="form-label">Select Input Type for 2nd Document (Optional)</label>
                <select class="form-select" id="input_type_1" name="input_type_1" onchange="toggleInputFields_1()">
                    <option value="" disabled selected>Choose input method</option>
                    {{-- Corrected values to match the IDs of the input groups --}}
                    <option value="topic_1">Text</option>
                    <option value="pdf_1">PDF</option>
                    <option value="cancel">Cancel</option>
                </select>
            </div>

            <div class="mb-4" id="text_input_group_1" style="display: none;">
                <label for="topic_1" class="form-label">Enter Text</label>
                <textarea class="form-control" id="topic_1" name="topic_1" rows="6"
                    placeholder="Paste or type text here if you're not uploading a PDF..."></textarea>
            </div>

            <div class="mb-4" id="pdf_input_group_1" style="display: none;">
                <label for="pdf_1" class="form-label">Upload PDF</label>
                <input type="file" class="form-control" id="pdf_file_1" name="pdf_file_1" accept="application/pdf" />
            </div>

            <div class="mb-4 text-center">
                <button type="submit" class="btn btn-primary">Generate</button>
            </div>
        </form>

        <div class="mb-4">
            <label for="generate_output" class="form-label">Generated Content</label>
            <textarea id="generate_output" class="form-control" name="generate_output" rows="10" readonly>{{ $response ?? '' }}</textarea>
        </div>
    </div>

    <div id="loading-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(255,255,255,0.8); z-index:9999; align-items:center; justify-content:center; flex-direction: column;">
        <div class="spinner-border text-pink" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-center fw-bold" style="color:#EC298B;">Generating your response...</p>
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

        function toggleInputFields_1() {
            const mode = document.getElementById('input_type_1').value;
            const textGroup = document.getElementById('text_input_group_1');
            const pdfGroup = document.getElementById('pdf_input_group_1');

            // The 'value' attribute of the options in the select should match these conditions
            if (mode === 'topic_1') { // Changed from 'topic1' to 'topic_1'
                textGroup.style.display = 'block';
                pdfGroup.style.display = 'none';
            } else if (mode === 'pdf_1') { // Changed from 'pdf1' to 'pdf_1'
                textGroup.style.display = 'none';
                pdfGroup.style.display = 'block';
            } else {
                textGroup.style.display = 'none';
                pdfGroup.style.display = 'none';
            }
        }

        // Show spinner on form submit
        document.getElementById('chatwithdocsForm').addEventListener('submit', function() {
            document.getElementById('loading-overlay').style.display = 'flex';
            this.querySelector('button[type="submit"]').disabled = true;
        });
    </script>

</body>

</html>
