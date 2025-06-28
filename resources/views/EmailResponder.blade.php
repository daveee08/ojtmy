<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Email Responder</title>
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

        .hero {
            text-align: center;
            margin-bottom: 2rem;
        }

        .hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #e91e63;
        }

        .hero p {
            font-size: 1rem;
            color: #555;
            margin-top: 1rem;
        }

        .section-title {
            font-weight: 700;
            font-size: 1.5rem;
            color: #e91e63;
            margin-top: 2.5rem;
            margin-bottom: 1.25rem;
        }

        .tool-item h5 {
            font-weight: 700;
            color: #2c2c2c;
        }

        .tool-item p {
            color: #555;
        }

        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 0.9rem;
            color: #777;
        }

        .btn-pink {
            background-color: #e91e63;
            color: #fff;
            font-weight: 600;
            padding: 0.5rem 2rem;
            border-radius: 8px;
            border: none;
            transition: 0.3s ease;
        }

        .btn-pink:hover {
            background-color: #d81b60;
        }

        .text-pink {
            color: #e91e63 !important;
        }

        .text-highlight {
            color: #e91e63 !important; /* Specific color for fullscreen spinner */
        }

        ul.list-group li {
            border: none;
            padding-left: 0;
            background: transparent;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top navbar-custom">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#">CK AI Tools</a>
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

    {{-- Fullscreen loading overlay --}}
    <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 9999;">
        <div class="text-center">
            <div class="spinner-border text-highlight mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
            <div class="fw-semibold text-highlight">Generating Response..</div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="section-title text-center">Email Responder</div>
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="p-4 rounded shadow-sm bg-white tool-item">
                    <form id="emailResponderForm">
                        @csrf
                        <div class="mb-3">
                            <label for="authorName" class="form-label">Author Name:</label>
                            <input type="text" class="form-control" id="authorName" name="authorName" placeholder="Jane Smith" required>
                        </div>
                        <div class="mb-3">
                            <label for="emailRespondingTo" class="form-label">Email you're responding to: *</label>
                            <textarea class="form-control" id="emailRespondingTo" name="emailRespondingTo" rows="6" required placeholder="I'm a PhD student at the University of Central Florida and I was wondering if we could partner. I would like to do a feasibility study using MagicSchool AI for my dissertation. Would you be interested in this partner?"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="communicationIntent" class="form-label">What you want to communicate in response: *</label>
                            <textarea class="form-control" id="communicationIntent" name="communicationIntent" rows="4" required placeholder="That sounds great lets set up time"></textarea>
                        </div>
                        <button type="submit" class="btn btn-pink" id="generateEmailBtn">Generate Email</button>
                        <button type="button" class="btn btn-info ms-2" id="loadExemplarBtn">Load Example</button>
                        <button type="button" class="btn btn-secondary ms-2" id="clearInputsBtn">Clear Inputs</button>
                        <div id="loadingSpinner" class="spinner-border text-pink mt-3" role="status" style="display:none;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span id="loadingText" class="text-pink ms-2" style="display:none;">Generating email...</span>
                    </form>

                    <div id="generatedEmailOutput" class="mt-4" style="display:none;">
                        <h5 class="section-title">Generated Email</h5>
                        <pre style="white-space: pre-wrap; word-wrap: break-word;" id="emailContent"></pre>
                    </div>

                    <div id="errorMessage" class="alert alert-danger mt-4" style="display:none;"></div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const emailResponderForm = document.getElementById('emailResponderForm');
            const generateEmailBtn = document.getElementById('generateEmailBtn');
            const loadExemplarBtn = document.getElementById('loadExemplarBtn');
            const clearInputsBtn = document.getElementById('clearInputsBtn');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const generatedEmailOutputDiv = document.getElementById('generatedEmailOutput');
            const emailContentPre = document.getElementById('emailContent');
            const errorMessageDiv = document.getElementById('errorMessage');

            emailResponderForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                
                // Reset UI
                errorMessageDiv.style.display = 'none';
                generatedEmailOutputDiv.style.display = 'none';
                emailContentPre.textContent = '';
                
                // Show fullscreen loading overlay
                loadingOverlay.classList.remove('d-none');
                loadingOverlay.classList.add('d-flex');
                
                const authorName = document.getElementById('authorName').value;
                const emailRespondingTo = document.getElementById('emailRespondingTo').value;
                const communicationIntent = document.getElementById('communicationIntent').value;
                const csrfToken = document.querySelector('input[name="_token"]').value;

                try {
                    const response = await fetch('/email-responder', { // This route will be defined next
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ author_name: authorName, email_responding_to: emailRespondingTo, communication_intent: communicationIntent })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        emailContentPre.textContent = data.generated_email;
                        generatedEmailOutputDiv.style.display = 'block';
                    } else {
                        errorMessageDiv.textContent = data.error || 'An unknown error occurred.';
                        errorMessageDiv.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    errorMessageDiv.textContent = 'Network error or service unavailable. Please check console for details.';
                    errorMessageDiv.style.display = 'block';
                } finally {
                    // Hide fullscreen loading overlay
                    loadingOverlay.classList.add('d-none');
                    loadingOverlay.classList.remove('d-flex');
                }
            });

            loadExemplarBtn.addEventListener('click', function() {
                document.getElementById('authorName').value = 'Jane Smith';
                document.getElementById('emailRespondingTo').value = "I\'m a PhD student at the University of Central Florida and I was wondering if we could partner. I would like to do a feasibility study using MagicSchool AI for my dissertation. Would you be interested in this partner?";
                document.getElementById('communicationIntent').value = "That sounds great lets set up time";
            });

            clearInputsBtn.addEventListener('click', function() {
                document.getElementById('authorName').value = '';
                document.getElementById('emailRespondingTo').value = '';
                document.getElementById('communicationIntent').value = '';
                generatedEmailOutputDiv.style.display = 'none';
                errorMessageDiv.style.display = 'none';
                emailContentPre.textContent = '';
                loadingOverlay.classList.add('d-none');
                loadingOverlay.classList.remove('d-flex');
            });
        });
    </script>

</body>

</html>
