<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Content Creator</title>
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
        select,
        textarea,
        input {
            border-radius: 8px; /* Consistent border-radius */
            min-height: 48px; /* Ensure touch friendliness for form elements */
            padding: 0.75rem 1rem; /* Adjusted padding */
            font-size: 1rem; /* Consistent font size */
        }

        .text-center.fw-bold {
            font-size: 1.75rem; /* Adjusted font size for mobile */
            margin-bottom: 0.5rem; /* Adjusted margin */
            color: #EC298B;
        }

        .text-center.text-muted.mb-4 {
            font-size: 0.9rem; /* Adjusted font size for mobile */
            margin-bottom: 1.5rem; /* Adjusted margin */
        }

        .form-label.fw-bold {
            font-size: 0.9rem; /* Adjusted font size for mobile */
        }

        .text-center.mt-4 {
            margin-top: 1.5rem !important; /* Adjusted margin for mobile */
        }

        .my-4 {
            margin-top: 1.5rem !important;
            margin-bottom: 1.5rem !important; /* Adjusted margins for hr */
        }

        .fw-bold.text-success {
            font-size: 1.1rem; /* Adjusted font size for mobile */
            margin-bottom: 0.5rem; /* Adjusted margin */
        }

        pre {
            font-size: 0.9rem; /* Adjusted font size for mobile */
            padding: 1rem; /* Adjusted padding for mobile */
        }

        .fw-bold.mt-4 {
            font-size: 1rem; /* Adjusted font size for mobile */
            margin-top: 1.5rem !important; /* Adjusted margin */
        }

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
            select,
            textarea,
            input {
                min-height: auto;
                padding: 0.375rem 0.75rem; /* Revert to original bootstrap default padding */
                font-size: inherit;
            }
            .text-center.fw-bold {
                font-size: 2rem;
                margin-bottom: 0.5rem;
            }
            .text-center.text-muted.mb-4 {
                font-size: 1rem;
                margin-bottom: 2rem;
            }
            .form-label.fw-bold {
                font-size: inherit;
            }
            .text-center.mt-4 {
                margin-top: 1.5rem !important;
            }
            .my-4 {
                margin-top: 1.5rem !important;
                margin-bottom: 1.5rem !important;
            }
            .fw-bold.text-success {
                font-size: inherit;
                margin-bottom: 0.5rem;
            }
            pre {
                font-size: inherit;
                padding: 15px;
            }
            .fw-bold.mt-4 {
                font-size: inherit;
                margin-top: 1.5rem !important;
            }
            .alert.alert-danger.mt-3 {
                margin-top: 1rem !important;
                font-size: inherit;
            }
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ck-card">
                <h2 class="text-center fw-bold">Content Creator</h2>
                <p class="text-center text-muted mb-4">Generate content with an optional caption.</p>

                <form method="POST" action="{{ route('contentcreator.generate') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Grade level</label>
                        <select class="form-select" name="grade_level" required>
                            <option disabled selected>Select grade level</option>
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
                        <label class="form-label fw-bold">Length</label>
                        <select class="form-select" name="length" required>
                            @foreach(['1 paragraph', '2 paragraphs', '3 paragraphs', '1 page', '2 pages'] as $opt)
                                <option value="{{ $opt }}" {{ old('length') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">What should it be about?</label>
                        <textarea class="form-control" name="prompt" rows="3" required>{{ old('prompt') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Additional instructions (optional)</label>
                        <input type="text" name="extra" class="form-control" value="{{ old('extra') }}">
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="ck-btn w-100">Generate</button>
                    </div>
                </form>

                @if(session('content'))
                    <hr class="my-4">
                    <h5 class="fw-bold text-success">Generated Content:</h5>
                    <pre class="mt-3">{{ session('content') }}</pre>

                    @if(session('caption'))
                        <h6 class="fw-bold mt-4" style="color:#EC298B;">Suggested Caption:</h6>
                        <p>{{ session('caption') }}</p>
                    @endif
                @endif

                @if(session('error'))
                    <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
</body>
</html>
