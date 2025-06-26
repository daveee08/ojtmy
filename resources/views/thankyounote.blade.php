<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thank You Note</title>
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
            background-color: #EC298B;
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .ck-btn:hover {
            background-color: #d32078;
        }

        .ck-title {
            font-size: 2rem;
            font-weight: 600;
            color: #EC298B;
            margin-bottom: 30px;
        }

        label {
            font-weight: 500;
            color: #2c3e50;
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
            box-shadow: none;
        }

        .form-control:focus {
            border-color: #EC298B;
            box-shadow: 0 0 0 0.2rem rgba(236, 41, 139, 0.2);
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

        .spinner-border.text-pink {
            color: #EC298B;
        }
    </style>
</head>
<body>

<div id="loading-overlay">
    <div class="spinner-border text-pink" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-3 text-center fw-bold" style="color:#EC298B;">Generating your note...</p>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ck-card">
                <h2 class="ck-title text-center">Thank You Note</h2>
                <p class="text-center text-muted">Generate a draft thank you note to show your appreciation!</p>

                <form method="POST" action="{{ route('thankyou.generate') }}" id="thankyou-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Describe what you're thanking the recipient for: <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="5" placeholder="e.g. Being a good friend, helping me understand math, etc." required>{{ old('reason') }}</textarea>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="ck-btn">Generate</button>
                    </div>
                </form>

                @if(session('thank_you_note'))
                    <hr class="my-4">
                    <h5 class="fw-bold" style="color:#EC298B;">Your Thank You Note:</h5>
                    <pre>{{ session('thank_you_note') }}</pre>
                @endif

                @error('error')
                    <div class="alert alert-danger mt-3">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('thankyou-form').addEventListener('submit', function () {
        document.getElementById('loading-overlay').style.display = 'flex';
    });
</script>

</body>
</html>
