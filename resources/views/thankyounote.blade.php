<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thank You Note Generator</title>
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
        pre {
            white-space: pre-wrap;
            background-color: #f0f4f8;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #dce3ed;
            font-family: 'Courier New', monospace;
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
                <h2 class="ck-title text-center">Thank You Note Generator</h2>

                <form id="thankyou-form" method="POST" action="{{ route('thankyou.generate') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">What are you thankful for?</label>
                        <textarea class="form-control" name="reason" rows="5" placeholder="e.g. Thank you for supporting me during my internship" required>{{ old('reason') }}</textarea>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="ck-btn">Generate Note</button>
                    </div>
                </form>

                @if(session('thankyou_note'))
                <hr class="my-4">
                <h5 class="fw-bold" style="color:#EC298B;">Generated Note:</h5>
                <pre>{{ session('thankyou_note') }}</pre>
                @endif

                @if(session('error'))
                 <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                @endif

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
