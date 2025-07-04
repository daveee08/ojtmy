<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Email Responder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(to right, #ffe6ec, #ffffff);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #191919;
            padding: 1.5rem 1rem;
        }

        .container {
            background: #ffffff;
            max-width: 720px;
            padding: 1.5rem 1rem;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            margin: 1.5rem auto;
        }

        h2 {
            font-weight: 800;
            font-size: 1.75rem;
            text-align: center;
            color: #EC298B;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            text-align: center;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            color: #555;
        }

        label {
            font-weight: 600;
            color: #191919;
            font-size: 0.9rem;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            font-size: 1rem;
            color: #191919;
            min-height: 48px;
            padding: 0.75rem 1rem;
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
            font-size: 1rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            transition: 0.3s;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-pink:hover {
            background-color: #d81b60;
        }

        .spinner-border.text-pink {
            color: #fff;
            margin-left: 0.5rem;
        }

        .hidden {
            display: none !important;
        }

        textarea[readonly] {
            background-color: #ffffff;
            color: #191919;
            min-height: 150px;
        }

        @media (min-width: 768px) {
            body {
                padding: 4rem 1rem;
            }
            .container {
                padding: 3rem 2rem;
                margin: auto;
            }
            h2 {
                font-size: 2rem;
            }
            .subtitle {
                font-size: 1rem;
                margin-bottom: 2rem;
            }
            label {
                font-size: inherit;
            }
            .form-control,
            .form-select {
                padding: 0.75rem;
                min-height: auto;
            }
            .btn-pink {
                font-size: 1.1rem;
                padding: 0.6rem 2rem;
                width: auto;
                min-width: 130px;
            }
            .spinner-border.text-pink {
                margin-left: 10px;
            }
            textarea[readonly] {
                min-height: auto;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Email Responder</h2>
        <p class="subtitle">Generate a professional response based on a received email and your intended message.</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/responder" onsubmit="handleGenerateSubmit()" enctype="multipart/form-data">
            @csrf

            <!-- Author Name -->
            <div class="mb-4">
                <label for="author" class="form-label">Author Name</label>
                <input type="text" class="form-control" id="author" name="author" placeholder="John Doe" required
                    value="{{ old('author') }}">
            </div>

            <!-- Email Being Responded To -->
            <div class="mb-4">
                <label for="email" class="form-label">Email You're Responding To</label>
                <textarea class="form-control" id="email" name="email" rows="6"
                    placeholder="Paste the original email here..." required>{{ old('email') }}</textarea>
            </div>

            <!-- Communication Intent -->
            <div class="mb-4">
                <label for="intent" class="form-label">What You Want to Communicate in Response</label>
                <textarea class="form-control" id="intent" name="intent" rows="4"
                    placeholder="E.g., I want to thank them and confirm the meeting on Friday..." required>{{ old('intent') }}</textarea>
            </div>

            <!-- Tone Selection -->
            <div class="mb-4">
                <label for="tone" class="form-label">Select Tone</label>
                <select class="form-select" id="tone" name="tone" required>
                    <option value="" disabled selected>Choose tone</option>
                    <option value="Formal">Formal</option>
                    <option value="Friendly">Friendly</option>
                    <option value="Concise">Concise</option>
                    <option value="Apologetic">Apologetic</option>
                    <option value="Assertive">Assertive</option>
                </select>
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
                <p class="mt-2">Generating your email response, please wait...</p>
            </div>
        </form>

        <!-- Output -->
        <div class="mb-4">
            <label for="output" class="form-label">Generated Email Response</label>
            @if (isset($response))
                <div class="border rounded p-3 bg-light" style="white-space: pre-wrap;">
                    {{ json_decode($response, true)['output'] ?? $response }}
                </div>
            @endif
        </div>
    </div>

    <script>
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
