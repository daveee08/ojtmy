@extends('layouts.bootstrap')
@extends('layouts.historysidenav')
@extends('layouts.header')

@section('title', 'AI Text Rewriter')

@section('styles')
    <style>
        .container {
            position: absolute;
            margin-top: 200px;
            background: white;
            max-width: 700px;
            width: 100%;
            padding: 2.5rem 3rem;
            border-radius: 12px;
        }

        h2 {
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        p.subtitle {
            text-align: center;
            color: #6c757d;
            margin-bottom: 2rem;
        }

        label {
            font-weight: 600;
            color: #34495e;
        }

        textarea.form-control {
            resize: none;
            overflow-y: auto;
            max-height: 400px;
            background-color: #fff !important;
            color: #2c3e50;
            font-family: inherit;
            line-height: 1.5;
            padding-top: 0.8rem;
        }

        .text-center {
            margin-top: 1.8rem;
            margin-bottom: 1.8rem;
        }

        .hidden {
            display: none !important;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <h2>Email Responder</h2>
        <p class="subtitle">Generate a professional response based on a received email and your intended message.</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="responderForm" method="POST" action="/responder"enctype="multipart/form-data">
            @csrf

            <!-- Author Name -->
            <div class="mb-4">
                <label for="author" class="form-label">Author Name</label>
                <input type="text" class="form-control" id="author" name="author" placeholder="John Doe" required
                    value="{{ old('author') }}">
            </div>

            <!-- Email Being Responded To -->
            <div class="mb-4">
                <label for="email" class="form-label">Email You're Responding To</label>
                <textarea class="form-control" id="email" name="email" rows="6"
                    placeholder="Paste the original email here..." required>{{ old('email') }}</textarea>
            </div>

            <!-- Communication Intent -->
            <div class="mb-4">
                <label for="intent" class="form-label">What You Want to Communicate in Response</label>
                <textarea class="form-control" id="intent" name="intent" rows="4"
                    placeholder="E.g., I want to thank them and confirm the meeting on Friday..." required>{{ old('intent') }}</textarea>
            </div>

            <!-- Tone Selection -->
            <div class="mb-4">
                <label for="tone" class="form-label">Select Tone</label>
                <select class="form-select" id="tone" name="tone" required>
                    <option value="" disabled selected>Choose tone</option>
                    <option value="Formal">Formal</option>
                    <option value="Friendly">Friendly</option>
                    <option value="Concise">Concise</option>
                    <option value="Apologetic">Apologetic</option>
                    <option value="Assertive">Assertive</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="mb-4 text-center">
                <button type="submit" class="btn btn-primary" id="submitButton">
                    <span id="btnText">Generate</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm text-pink hidden" role="status"
                        aria-hidden="true"></span>
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        document.getElementById('responderForm').addEventListener('submit', function() {
            document.getElementById('loading-overlay').style.display = 'flex';
            this.querySelector('button[type="submit"]').disabled = true;
        });
    </script>
@endsection
