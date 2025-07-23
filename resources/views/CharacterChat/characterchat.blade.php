{{-- resources/views/CharacterChat/characterchat.blade.php --}}

@extends('layouts.bootstrap')
@extends('layouts.header')
@extends('layouts.navbaragent')


@section('content')
<!-- Loading Spinner -->
<style>
    body {
        background-color: #f9f9f9;
        font-family: 'Poppins', sans-serif;
    }

    .form-select,
    .form-control {
        border-radius: 10px;
        font-size: 15px;
        padding: 12px 15px;
        box-shadow: none;
        border: 1px solid #d0d5dd;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #EC298B;
        box-shadow: 0 0 0 0.15rem rgba(236, 41, 139, 0.15);
    }

    .btn-pink {
        background-color: #EC298B !important;
        color: white;
        font-weight: 600;
        border-radius: 12px;
        border: none;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .btn-pink:hover {
        background-color: #d32078 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(236, 41, 139, 0.15);
    }

    .container-box {
        max-width: 650px;
        margin: 0 auto;
        padding: 2.5rem 2rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
    }

    label {
        font-weight: 600;
        color: #333;
    }

    .text-muted {
        color: #888 !important;
    }

    #loading-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background-color: rgba(255, 255, 255, 0.85);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }

    #loading-overlay.active {
        display: flex;
    }

    .response-box {
        background-color: #fdfdff;
        border: 1px solid #eee;
        padding: 1.5rem;
        border-radius: 12px;
        font-size: 1rem;
        line-height: 1.8;
        white-space: pre-wrap;
    }
</style>

<div class="container my-5">
    <div class="container-box">
        <h2 class="text-center mb-3 fw-bold" style="color: #EC298B;">Character Chatbot</h2>
        <p class="text-center text-muted mb-4">Talk to a character, author, or historic figure—tailored to your grade level.</p>

        <form id="characterchat-form" method="POST" action="{{ route('characterchat.generate') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label for="grade_level" class="form-label">Grade Level <span class="text-danger">*</span></label>
                <select name="grade_level" id="grade_level" class="form-select" required>
                    <option value="">Select a grade level</option>
                    @foreach (['Pre-K','Kindergarten','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','University','Professional Staff'] as $level)
                        <option value="{{ $level }}" {{ old('grade_level') == $level ? 'selected' : '' }}>{{ $level }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="character" class="form-label">Character, Author, or Historic Figure <span class="text-danger">*</span></label>
                <textarea name="character" id="character" class="form-control" rows="2" placeholder="e.g., Barbie, Jose Rizal, Anne Frank" required>{{ old('character') }}</textarea>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-lg btn-pink w-100">Generate</button>
            </div>
        </form>

        @if(session('response'))
            <div class="mt-5">
                <h5 class="fw-bold mb-3">Character Response</h5>
                <div class="response-box">
                    {!! nl2br(e(session('response'))) !!}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Loading Overlay (ONLY ONCE) -->
<div id="loading-overlay">
    <div>
        <div class="spinner-border text-pink" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 fw-bold" style="color: #EC298B;">Generating character response...</p>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('characterchat-form');
        const overlay = document.getElementById('loading-overlay');
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            overlay.style.display = 'flex';
            form.submit();
        });
    });
</script>

@endsection
