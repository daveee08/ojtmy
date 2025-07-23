
@extends('layouts.bootstrap')
@extends('layouts.header')
@extends('layouts.navbaragent')

@section('content')
    <style>
        body {
            background-color: #f4f7fb;
            font-family: 'Poppins', sans-serif;
        }

        .ck-card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.06);
            padding: 50px 40px;
            border: none;
        }

        .ck-title {
            font-size: 2rem;
            font-weight: 700;
            color: #EC298B;
            text-align: center;
            margin-bottom: 10px;
        }

        .ck-sub {
            text-align: center;
            color: #666;
            font-size: 1rem;
            margin-bottom: 30px;
        }

        .ck-btn {
            background-color: #EC298B;
            color: #fff;
            border: none;
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .ck-btn:hover {
            background-color: #d32078;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(236, 41, 139, 0.15);
        }

        select,
        textarea {
            border-radius: 10px !important;
            font-size: 15px;
            padding: 12px 15px;
        }

        textarea:focus,
        select:focus {
            border-color: #EC298B;
            box-shadow: 0 0 0 0.2rem rgba(236, 41, 139, 0.1);
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
            font-weight: 600;
            color: #EC298B;
        }

        pre {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            white-space: pre-wrap;
            font-size: 15px;
            line-height: 1.7;
        }

        .alert {
            border-radius: 10px;
        }
    </style>

<!-- Loading Spinner -->
<!-- Loading Overlay -->
<div id="loading-overlay">
  <div class="spinner-border text-pink" role="status" style="width: 2.8rem; height: 2.8rem;">
    <span class="visually-hidden">Loading...</span>
  </div>
  <p class="mt-3 fw-semibold" style="color:#EC298B;">Just a moment...</p>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ck-card">
                <h2 class="ck-title">Social Story Generator</h2>
                <p class="ck-sub">Help students or professionals understand social situations with AI-generated stories.</p>

                <form method="POST" action="{{ route('socialstory.generate') }}" onsubmit="showLoading()">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Grade Level <span class="text-danger">*</span></label>
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

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Describe the Situation <span class="text-danger">*</span></label>
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
                    <div class="alert alert-danger mt-4">{{ session('error') }}</div>
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

@endsection
