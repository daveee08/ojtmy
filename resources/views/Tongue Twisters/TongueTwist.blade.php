<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CK Tongue Twister AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous" />
    <style>
        body {
            background: linear-gradient(to right, #ffe6ec, #ffffff);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c2c2c;
            padding-top: 80px;
        }
        .navbar-custom {
            background-color: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .navbar-brand {
            font-weight: 700;
            color: #e91e63 !important;
        }
        .nav-link {
            color: #2c2c2c !important;
            font-weight: 500;
            margin-right: 1rem;
        }
        .nav-link:hover {
            color: #e91e63 !important;
        }
        .container {
            background: #ffffff;
            max-width: 900px;
            padding: 3rem 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            margin-top: 2rem;
        }
        .section-title {
            font-weight: 700;
            font-size: 1.5rem;
            color: #e91e63;
            margin-top: 2.5rem;
            margin-bottom: 1.25rem;
        }
        #clearInputsBtn {
            transition: color 0.2s;
        }
        #clearInputsBtn:hover, #clearInputsBtn:focus {
            color: #d81b60 !important;
        }
        #clearInputsBtn:hover i, #clearInputsBtn:focus i {
            color: #d81b60 !important;
        }
        #clearInputsBtn:active {
            color: #ad1457 !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top navbar-custom">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#">CK Tongue Twister AI</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Support</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="section-title text-center">CK Tongue Twister AI</div>
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="p-4 rounded shadow-sm bg-white tool-item">
                    <form id="twisterForm" method="POST" action="/tonguetwister">
                        @csrf
                        <div class="mb-3">
                            <label for="topic" class="form-label">Topic:</label>
                            <input type="text" class="form-control" id="topic" name="topic" value="{{ old('topic') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="grade_level" class="form-label">Grade Level:</label>
                            <select class="form-select" id="grade_level" name="grade_level" required>
                                <option value="Pre-K" {{ old('grade_level') == 'Pre-K' ? 'selected' : '' }}>Pre-K</option>
                                <option value="Kindergarten" {{ old('grade_level') == 'Kindergarten' ? 'selected' : '' }}>Kindergarten</option>
                                <option value="1st Grade" {{ old('grade_level') == '1st Grade' ? 'selected' : '' }}>1st Grade</option>
                                <option value="2nd Grade" {{ old('grade_level') == '2nd Grade' ? 'selected' : '' }}>2nd Grade</option>
                                <option value="3rd Grade" {{ old('grade_level') == '3rd Grade' ? 'selected' : '' }}>3rd Grade</option>
                                <option value="4th Grade" {{ old('grade_level') == '4th Grade' ? 'selected' : '' }}>4th Grade</option>
                                <option value="5th Grade" {{ old('grade_level') == '5th Grade' ? 'selected' : '' }}>5th Grade</option>
                                <option value="6th Grade" {{ old('grade_level') == '6th Grade' ? 'selected' : '' }}>6th Grade</option>
                                <option value="7th Grade" {{ old('grade_level') == '7th Grade' ? 'selected' : '' }}>7th Grade</option>
                                <option value="8th Grade" {{ old('grade_level') == '8th Grade' ? 'selected' : '' }}>8th Grade</option>
                                <option value="9th Grade" {{ old('grade_level') == '9th Grade' ? 'selected' : '' }}>9th Grade</option>
                                <option value="10th Grade" {{ old('grade_level') == '10th Grade' ? 'selected' : '' }}>10th Grade</option>
                                <option value="11th Grade" {{ old('grade_level') == '11th Grade' ? 'selected' : '' }}>11th Grade</option>
                                <option value="12th Grade" {{ old('grade_level') == '12th Grade' ? 'selected' : '' }}>12th Grade</option>
                                <option value="University" {{ old('grade_level') == 'University' ? 'selected' : '' }}>University</option>
                                <option value="1st Year College" {{ old('grade_level') == '1st Year College' ? 'selected' : '' }}>1st Year College</option>
                                <option value="2nd Year College" {{ old('grade_level') == '2nd Year College' ? 'selected' : '' }}>2nd Year College</option>
                                <option value="3rd Year College" {{ old('grade_level') == '3rd Year College' ? 'selected' : '' }}>3rd Year College</option>
                                <option value="4th Year College" {{ old('grade_level') == '4th Year College' ? 'selected' : '' }}>4th Year College</option>
                                <option value="Adult" {{ old('grade_level') == 'Adult' ? 'selected' : '' }}>Adult</option>
                                <option value="Professional Staff" {{ old('grade_level') == 'Professional Staff' ? 'selected' : '' }}>Professional Staff</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-pink">Generate Tongue Twister</button>
                        <button type="button" class="btn btn-info ms-2" id="loadExemplarBtn">Load Example</button>
                        <button type="button" class="d-flex align-items-center ms-2" id="clearInputsBtn" style="background: transparent; border: none; color: #e91e63; font-weight: 600; box-shadow: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#e91e63" class="bi bi-arrow-clockwise me-1" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 1 1 .908-.418A4 4 0 1 0 8 4V1.5a.5.5 0 0 1 1 0v3A.5.5 0 0 1 8.5 5h-3a.5.5 0 0 1 0-1H8z"/>
                            </svg>
                            <span style="color: #e91e63;">Clear Inputs</span>
                        </button>
                    </form>
                    @if (isset($response) && $response)
                        <div class="alert alert-success mt-4">{{ $response }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} CK Children's Publishing. All rights reserved.
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous" defer>
    </script>
    <script defer>
        document.getElementById('loadExemplarBtn').addEventListener('click', function() {
            document.getElementById('topic').value = 'Silly Snakes';
            document.getElementById('grade_level').value = '1st Grade';
        });
        document.getElementById('clearInputsBtn').addEventListener('click', function() {
            document.getElementById('topic').value = '';
            document.getElementById('grade_level').value = 'Pre-K';
        });
    </script>
</body>
</html>
