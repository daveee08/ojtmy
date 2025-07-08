<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Math Review Generator</title>
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

        #generate_output {
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
        <h2>Math Review</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="mathreviewForm" method="POST" action="/mathreview" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="grade_level" class="form-label">Grade Level</label>
                <select class="form-select" id="grade_level" name="grade_level" required>
                    <option value="" disabled selected>Select Grade Level</option>
                    <option value="kinder">Kindergarten</option>
                    <option value="grade 1">Grade 1</option>
                    <option value="grade 2">Grade 2</option>
                    <option value="grade 3">Grade 3</option>
                    <option value="grade 4">Grade 4</option>
                    <option value="grade 5">Grade 5</option>
                    <option value="grade 6">Grade 6</option>
                    <option value="grade 7">Grade 7</option>
                    <option value="grade 8">Grade 8</option>
                    <option value="grade 9">Grade 9</option>
                    <option value="grade 10">Grade 10</option>
                    <option value="grade 11">Grade 11</option>
                    <option value="grade 12">Grade 12</option>
                    <option value="college">College</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="number_of_problems" class="form-label">Number of Problems</label>
                <input type="number" class="form-control" id="number_of_problems" name="number_of_problems"
                    min="1" max="15" required placeholder="" />
            </div>

            <div class="mb-4">
                <label for="math_content" class="form-label">Math Content</label>
                <input type="text" class="form-control" id="math_content" name="math_content" required
                    placeholder="Make them word problems, make a mix of some easy and some difficult, etc."></textarea>
            </div>

            <div class="mb-4">
                <label for="additional_criteria" class="form-label">Additional Criteria (Optional)</label>
                <textarea class="form-control" id="additional_criteria" name="additional_criteria" rows="3"
                    placeholder="Make them word problems, make a mix of some easy and some difficult, etc."></textarea>
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

    <!-- Spinner Overlay -->
    <div id="loading-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(255,255,255,0.8); z-index:9999; align-items:center; justify-content:center; flex-direction: column;">
        <div class="spinner-border text-pink" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-center fw-bold" style="color:#EC298B;">Generating your math problems...</p>
    </div>

    <script>
        document.getElementById('mathReviewForm').addEventListener('submit', function() {
            document.getElementById('loading-overlay').style.display = 'flex';
            this.querySelector('button[type="submit"]').disabled = true;
        });
    </script>
</body>

</html>
