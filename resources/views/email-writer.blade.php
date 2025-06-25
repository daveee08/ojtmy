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
        }

        .ck-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
            padding: 40px;
            border: 1px solid #e4e8f0;
        }

        .ck-btn {
            background-color: #4a42f4;
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .ck-btn:hover {
            background-color: #3c35cc;
        }

        .ck-title {
            font-size: 2rem;
            font-weight: 600;
            color: #4a42f4;
            margin-bottom: 30px;
        }

        pre {
            white-space: pre-wrap;
            background-color: #f0f4f8;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #dce3ed;
            font-family: 'Courier New', monospace;
        }

        .form-control {
            border-radius: 6px;
            border: 1px solid #ccd6e0;
        }

        .form-control:focus {
            border-color: #4a42f4;
            box-shadow: 0 0 0 0.2rem rgba(74, 66, 244, 0.2);
        }

        #loading-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .spinner-border.text-purple {
            color: #4a42f4;
        }
    </style>
</head>
<body>

<!-- Loading Spinner -->
<div id="loading-overlay">
    <div class="spinner-border text-purple" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-3 text-center fw-bold" style="color:#4a42f4;">Generating your email...</p>
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
                    <h5 class="fw-bold" style="color:#4a42f4;">Generated Email:</h5>
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
