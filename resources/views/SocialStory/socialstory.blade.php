<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Social Story Generator</title>
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
            font-size: 1.8rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 10px;
        }

        .ck-sub {
            text-align: center;
            margin-bottom: 25px;
            color: #666;
        }

        select,
        textarea {
            border-radius: 8px;
        }

        /* Spinner Overlay */
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(255, 255, 255, 0.85);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: #EC298B;
        }

        .loading-text {
            margin-top: 1rem;
            font-weight: bold;
            color: #EC298B;
        }

        pre {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>

<!-- Loading Spinner -->
<div id="loading-overlay">
    <div class="spinner-border" role="status"></div>
    <div class="loading-text">Generating your story...</div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ck-card">
                <h2 class="ck-title">Social Story Generator</h2>
                <p class="ck-sub">Help students or professionals understand social situations with AI-generated stories.</p>

                <form method="POST" action="{{ route('socialstory.generate') }}" onsubmit="showLoading()">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold">Grade level: <span class="text-danger">*</span></label>
                        <select class="form-select" name="grade_level" required>
                            <option disabled selected>Select a grade level</option>
                            @foreach([
                                'Pre-K', 'Kindergarten',
                                'Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6',
                                'Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12',
                                'University','Professional Staff'
                            ] as $level)
                                <option value="{{ $level }}" {{ old('grade_level') == $level ? 'selected' : '' }}>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Describe the situation: <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="situation" rows="4" placeholder="e.g. First day at a new school, giving a speech, office orientation…" required>{{ old('situation') }}</textarea>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="ck-btn w-100">Generate Story</button>
                    </div>
                </form>

                @if(session('story'))
                <hr class="my-4">
                <h5 class="fw-bold" style="color:#EC298B;">Generated Story:</h5>
                <pre class="mt-3">{{ session('story') }}</pre>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    function showLoading() {
        document.getElementById('loading-overlay').style.display = 'flex';
    }
</script>
</body>
</html>
