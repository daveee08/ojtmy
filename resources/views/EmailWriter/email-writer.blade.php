<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Writer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7fb;
            font-family: 'Poppins', sans-serif;
            padding: 1.5rem 1rem; /* Adjusted for mobile-first */
        }

        .ck-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04); /* Lighter shadow for mobile */
            padding: 1.5rem; /* Adjusted padding for mobile */
            border: 1px solid #e4e8f0;
            margin: 1.5rem auto; /* Added margin for centering on mobile */
            max-width: 720px; /* Max width for consistency */
        }

        .ck-btn {
            background-color: #EC298B;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem; /* Adjusted padding for touch friendliness */
            border-radius: 8px; /* Consistent border-radius */
            font-weight: 600;
            font-size: 1rem; /* Adjusted font size for mobile */
            transition: background-color 0.3s ease;
            width: 100%; /* Full width on mobile */
        }

        .ck-btn:hover {
            background-color: #d32078;
        }

        .ck-title {
            font-size: 1.75rem; /* Adjusted font size for mobile */
            font-weight: 600;
            color: #EC298B;
            margin-bottom: 1.5rem; /* Adjusted margin */
            text-align: center; /* Center align title */
        }

        label {
            font-weight: 500;
            color: #2c3e50;
            font-size: 0.9rem; /* Adjusted font size for mobile */
        }

        pre {
            white-space: pre-wrap;
            background-color: #f0f4f8;
            border-radius: 8px;
            padding: 1rem; /* Adjusted padding for mobile */
            border: 1px solid #dce3ed;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem; /* Adjusted font size for mobile */
        }

        .form-control {
            border-radius: 6px;
            border: 1px solid #ccd6e0;
            box-shadow: none;
            min-height: 48px; /* Ensure touch friendliness */
            padding: 0.75rem 1rem; /* Adjusted padding */
            font-size: 1rem; /* Consistent font size */
        }

        .form-control:focus {
            border-color: #EC298B;
            box-shadow: 0 0 0 0.2rem rgba(236, 41, 139, 0.2);
        }

        .spinner-border.text-pink {
            color: #EC298B;
            margin-left: 0.5rem; /* Adjusted margin */
        }

        #loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        /* Media queries for larger screens */
        @media (min-width: 768px) {
            body {
                padding: 0; /* Remove padding if not needed on larger screens */
            }
            .ck-card {
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
                padding: 40px;
                margin: auto; /* Revert margin */
            }
            .ck-btn {
                padding: 12px 28px;
                font-size: 16px;
                width: auto; /* Revert to auto width */
            }
            .ck-title {
                font-size: 2rem;
                margin-bottom: 30px;
            }
            label {
                font-size: inherit;
            }
            pre {
                padding: 15px;
                font-size: inherit;
            }
            .form-control {
                padding: 0.375rem 0.75rem; /* Revert to original bootstrap default padding */
                min-height: auto; /* Revert min-height */
            }
            .spinner-border.text-pink {
                margin-left: 10px;
            }
        }
    </style>
</head>
<body>

<!-- loading spinner -->
<div id="loading-overlay">
  <div class="spinner-border text-pink" role="status" style="width: 3rem; height: 3rem;">
    <span class="visually-hidden">Loading...</span>
  </div>
  <p class="mt-3 text-center fw-bold" style="color:#EC298B;">Generating your email...</p>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ck-card">
                <h2 class="ck-title text-center">Email Writer</h2>

                <form method="POST" action="{{ route('email.writer.generate') }}" id="email-writer-form">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">What should the email say?</label>
                        <textarea class="form-control" name="email_input" rows="6" placeholder="Include context or purpose of your email" required>{{ old('email_input') }}</textarea>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="ck-btn">Generate Email</button>
                    </div>
                </form>

                @if(session('generated_email'))
                <hr class="my-4">
                <h5 class="fw-bold" style="color:#EC298B;">Generated Email:</h5>
                <pre>{{ session('generated_email') }}</pre>
                @endif

                @error('error')
                <div class="alert alert-danger mt-3">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('email-writer-form').addEventListener('submit', function () {
    document.getElementById('loading-overlay').style.display = 'flex';
});
</script>

</body>
</html>
