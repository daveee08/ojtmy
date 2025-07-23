@extends('layouts.bootstrap')
@extends('layouts.header')
@extends('layouts.navbaragent')

@section('content')

@if ($errors->any())
    <div class="alert alert-danger shadow-sm rounded-3 px-4 py-3">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li class="mb-1">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger shadow-sm rounded-3 px-4 py-3">
        {{ session('error') }}
    </div>
@endif

<!-- Loading Spinner -->
<div id="loading-overlay">
    <div class="spinner-border text-pink" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-3 text-center fw-bold" style="color:#EC298B;">Generating your response...</p>
</div>

<style>
    body {
        background-color: #f4f7fb;
        font-family: 'Poppins', sans-serif;
    }

    .ck-card {
        background-color: #fff;
        border-radius: 20px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.07);
        padding: 45px 35px;
        border: 1px solid #e4e8f0;
        transition: all 0.3s ease-in-out;
    }

    .ck-btn {
        background-color: #EC298B;
        color: #fff;
        border: none;
        padding: 12px 28px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .ck-btn:hover {
        background-color: #d32078;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(236, 41, 139, 0.2);
    }

    .ck-title {
        font-size: 2.4rem;
        font-weight: 700;
        color: #EC298B;
        text-align: center;
        margin-bottom: 30px;
    }

    .form-control,
    .form-select {
        border-radius: 10px;
        border: 1px solid #ccd6e0;
        padding: 12px 16px;
        box-shadow: none;
        font-size: 15px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #EC298B;
        box-shadow: 0 0 0 0.2rem rgba(236, 41, 139, 0.15);
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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ck-card">
                <h2 class="ck-title">Conceptual Understanding</h2>

                <form id="tutor-form" action="{{ url('/tutor') }}" method="POST" enctype="multipart/form-data">
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

                    <div class="mb-4" id="topic-input">
                        <label class="form-label fw-semibold">Topic</label>
                        <input type="text" class="form-control" name="topic"
                            placeholder="Enter your topic or question...">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Additional Context <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" name="add_cont" rows="3" placeholder="Anything else the tutor should know?"></textarea>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="ck-btn">Send</button>
                    </div>
                </form>

                @error('error')
                    <div class="alert alert-danger mt-4">{{ $message }}</div>
                @enderror

                @if (session('status'))
                    <div class="alert alert-success mt-4">{{ session('status') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('tutor-form').addEventListener('submit', function (event) {
        event.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const loadingOverlay = document.getElementById('loading-overlay');
        loadingOverlay.style.display = 'flex';
        form.submit();
    });
</script>

@endsection
