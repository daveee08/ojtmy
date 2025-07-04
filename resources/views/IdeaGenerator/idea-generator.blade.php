<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Idea Generator</title>
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
            width: 100%; /* Full width on mobile */
        }

        .ck-btn:hover {
            background-color: #d32078;
        }

        .ck-title {
            font-size: 1.75rem; /* Adjusted font size for mobile */
            font-weight: 600;
            text-align: center;
            margin-bottom: 0.5rem; /* Adjusted margin */
        }

        .ck-sub {
            text-align: center;
            font-size: 0.9rem; /* Adjusted font size for mobile */
            margin-bottom: 1.5rem; /* Adjusted margin */
            color: #666;
        }

        select,
        textarea,
        input {
            border-radius: 8px; /* Consistent border-radius */
            min-height: 48px; /* Ensure touch friendliness for form elements */
            padding: 0.75rem 1rem; /* Adjusted padding */
            font-size: 1rem; /* Consistent font size */
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
            width: 2.5rem; /* Slightly smaller for mobile */
            height: 2.5rem; /* Slightly smaller for mobile */
            color: #EC298B;
        }

        .loading-text {
            margin-top: 0.75rem; /* Adjusted margin */
            font-weight: bold;
            color: #EC298B;
            font-size: 0.9rem; /* Adjusted font size for mobile */
        }

        /* Labels in form */
        .form-label.fw-bold {
            font-size: 0.9rem; /* Adjusted font size for mobile */
            margin-bottom: 0.5rem; /* Adjusted margin */
        }

        /* Generated ideas output */
        pre.mt-3 {
            margin-top: 1.5rem !important; /* Adjusted margin */
            font-size: 0.9rem; /* Adjusted font size for mobile */
            padding: 1rem; /* Adjusted padding for mobile */
        }

        /* Section title for generated ideas */
        h5.fw-bold {
            font-size: 1.1rem; /* Adjusted font size for mobile */
            margin-bottom: 0.5rem; /* Adjusted margin */
        }

        /* Error alert */
        .alert.alert-danger.mt-3 {
            margin-top: 1rem !important; /* Adjusted margin */
            font-size: 0.9rem; /* Adjusted font size for mobile */
        }

        /* Media queries for larger screens */
        @media (min-width: 768px) {
            body {
                padding: 0;
            }
            .ck-card {
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
                padding: 40px;
                margin: auto; /* Revert margin */
            }
            .ck-btn {
                padding: 12px 28px;
                font-size: 16px;
                width: auto;
            }
            .ck-title {
                font-size: 1.8rem;
                margin-bottom: 10px;
            }
            .ck-sub {
                font-size: inherit;
                margin-bottom: 25px;
            }
            select,
            textarea,
            input {
                min-height: auto;
                padding: 0.375rem 0.75rem; /* Revert to original bootstrap default padding */
                font-size: inherit;
            }
            .spinner-border {
                width: 3rem;
                height: 3rem;
            }
            .loading-text {
                margin-top: 1rem;
                font-size: inherit;
            }
            .form-label.fw-bold {
                font-size: inherit;
                margin-bottom: 0.5rem;
            }
            pre.mt-3 {
                margin-top: 1.5rem !important;
                font-size: inherit;
                padding: 15px;
            }
            h5.fw-bold {
                font-size: inherit;
                margin-bottom: 0.5rem;
            }
            .alert.alert-danger.mt-3 {
                margin-top: 1rem !important;
                font-size: inherit;
            }
        }
    </style>
</head>
<body>

<!-- Loading Spinner -->
<div id="loading-overlay">
    <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <div class="loading-text">Generating your ideas...</div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ck-card">
                <h2 class="ck-title">Idea Generator</h2>
                <p class="ck-sub">Use AI as a thought partner to generate ideas on any topic.</p>

                <form method="POST" action="{{ route('idea.generate') }}" onsubmit="showLoading()">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold">Grade level: <span class="text-danger">*</span></label>
                        <select class="form-select" name="grade_level" required>
                            <option disabled selected>Select a grade level</option>
                            @foreach([
                                'Pre-K', 'Kindergarten',
                                'Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6',
                                'Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12',
                                'Year 1','Year 2','Year 3','Year 4','Year 5','Year 6','Year 7',
                                'Year 8','Year 9','Year 10','Year 11','Year 12','Year 13',
                                'University','Professional Staff'
                            ] as $level)
                                <option value="{{ $level }}" {{ old('grade_level') == $level ? 'selected' : '' }}>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Help me come up with ideas for… (be specific): <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="prompt" rows="4" placeholder="A science fair project, ways to explain mitosis, campaign slogans for class president…" required>{{ old('prompt') }}</textarea>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="ck-btn w-100">Generate</button>
                    </div>
                </form>

                @if(session('ideas'))
                <hr class="my-4">
                <h5 class="fw-bold" style="color:#EC298B;">Generated Ideas:</h5>
                <pre class="mt-3">{{ session('ideas') }}</pre>
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
