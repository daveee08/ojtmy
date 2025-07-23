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
        select, textarea, input {
            border-radius: 8px;
        }
    </style>

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
                @endif

                @if(session('error'))
                    <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                @endif

            </div>
        </div>
    </div>
</div>

@endsection
