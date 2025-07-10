@extends('layouts.app')

@section('content')
    <style>
        body {
            background-color: #f9f9f9;
            font-family: 'Poppins', sans-serif;
        }

        .form-select,
        .form-control {
            border-radius: 8px;
        }

        .btn-pink {
            background-color: #FF2D84 !important;
            color: white;
            font-weight: bold;
            border-radius: 8px;
            border: none;
        }

        .btn-pink:hover {
            background-color: #e02675 !important;
        }

        .container-box {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        label {
            font-weight: 600;
        }
    </style>

    <div class="container my-5">
        <div class="container-box">
            <h2 class="text-center mb-3 fw-bold" style="color: #111;">Character Chatbot</h2>
            <p class="text-center text-muted mb-4">Chat with any historic figure, author, or recognizable character from a story.</p>

           <form method="POST" action="{{ route('characterchat.generate') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="grade_level" class="form-label">Grade level: <span class="text-danger">*</span></label>
                    <select name="grade_level" id="grade_level" class="form-select" required>
                        <option value="">Select a grade level</option>
                        @foreach (['Pre-K','Kindergarten','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','University','Professional Staff'] as $level)
                            <option value="{{ $level }}" {{ old('grade_level') == $level ? 'selected' : '' }}>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="character" class="form-label">Character, Author, or Historic Figure: <span class="text-danger">*</span></label>
                    <textarea name="character" id="character" class="form-control" rows="2" placeholder="e.g. Romeo from Romeo & Juliet, Frederick Douglass, Benjamin Franklin, Toni Morrison" required>{{ old('character') }}</textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-lg btn-pink w-100">Generate</button>
                </div>
            </form>

            @if(session('response'))
                <div class="mt-5">
                    <h5 class="fw-bold">Character Response:</h5>
                    <div class="border rounded p-3 bg-light mt-2" style="white-space: pre-wrap;">
                        {!! nl2br(e(session('response'))) !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
