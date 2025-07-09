@extends('layouts.app')

@section('content')

<div id="loading-overlay">
  <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
    <span class="visually-hidden">Loading...</span>
  </div>
  <p class="mt-3 text-center fw-semibold" style="color:#0d6efd;">Please wait...</p>
</div>

<style>
  body {
    background-color: #f5f7fa;
    font-family: 'Inter', 'Poppins', sans-serif;
  }

  .ck-card {
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    padding: 40px;
    border: none;
  }

  .ck-title {
    font-size: 1.9rem;
    font-weight: 600;
    color: #EC298B; /* Replaces .text-highlight color */
    text-align: center;
    margin-bottom: 30px;
  }

  .ck-btn {
    background-color: #EC298B; /* Replaces .btn-primary color */
    color: white;
    border: none;
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.25s ease;
  }

  .ck-btn:hover {
    background-color: #c30074; /* Darker shade for hover, similar to original .btn-primary:hover */
  }

  .form-control {
    border-radius: 8px;
    font-size: 15px;
  }

  .form-control:focus {
    border-color: #EC298B; /* Replaces focus border color */
    box-shadow: 0 0 0 0.2rem rgba(236, 41, 139, 0.2); /* Adjusted shadow color */
  }

  /* Specific styles for this page's content, maintaining the look */
  .ck-card .text-muted {
    font-size: 0.95rem;
  }

  /* Styles for the loading overlay */
  #loading-overlay {
    display: none; /* Initially hidden */
    position: fixed;
    inset: 0;
    background-color: rgba(255, 255, 255, 0.8);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    flex-direction: column;
  }

  /* Style for generated questions, adapting the alert-success */
  .alert-success .ck-title-small {
    font-size: 1.25rem; /* Adjusted size for sub-title */
    font-weight: 600;
    color: #EC298B;
  }

  .alert-success ol {
      padding-left: 20px;
  }

  .alert-success ol li {
      margin-bottom: 8px;
      line-height: 1.6;
  }
</style>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
      <div class="ck-card">
        <h2 class="ck-title">🧠 5 Questions Agent</h2>
        <p class="text-muted text-center mb-4">
            Use AI to ask you 5 questions to push your thinking on any topic or idea.
        </p>

        <form action="{{ route('fivequestions.process') }}" method="POST" id="questionForm">
            @csrf

            <div class="mb-3">
                <label for="grade_level" class="form-label">Select Grade Level</label>
                <select class="form-select" name="grade_level" id="grade_level" required>
                    <option value="">-- Choose --</option>
                    <option value ="kindergarten" {{ old('grade_level') == 'kindergarten' ? 'selected' : '' }}>Kindergarten</option>
                    <option value="elementary" {{ old('grade_level') == 'elementary' ? 'selected' : '' }}>Elementary</option>
                    <option value="junior_high" {{ old('grade_level') == 'junior_high' ? 'selected' : '' }}>Junior High</option>
                    <option value="senior_high" {{ old('grade_level') == 'senior_high' ? 'selected' : '' }}>Senior High</option>
                    <option value="college" {{ old('grade_level') == 'college' ? 'selected' : '' }}>College</option>
                </select>
                @error('grade_level')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="mb-3">
                <label for="topic" class="form-label">Ask me questions to push my thinking about:</label>
                <textarea class="form-control" name="topic" id="topic" rows="4" placeholder="e.g. The importance of recycling..." required>{{ old('topic') }}</textarea>
                @error('topic')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="text-center mt-4">
                <button type="submit" id="submitBtn" class="ck-btn">
                    <span id="btnText">Generate</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>

        @if(isset($questions))
            <div class="alert alert-success mt-4">
                <h5 class="ck-title-small">Here are your 5 AI-generated questions:</h5>
                <ol>
                    @foreach($questions as $q)
                        <li>{{ $q }}</li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if($errors->has('error'))
            <div class="alert alert-danger mt-4">
                {{ $errors->first('error') }}
            </div>
        @endif
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('questionForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const loadingOverlay = document.getElementById('loading-overlay'); // Changed ID to match step-tutor

        form.addEventListener('submit', function () {
            loadingOverlay.style.display = 'flex'; // Show the loading overlay
            submitBtn.disabled = true;
            btnText.textContent = 'Generating...';
            btnSpinner.classList.remove('d-none');
        });

        // Optional: If you want to hide the overlay on page load if it was somehow left visible
        window.addEventListener('load', function() {
            loadingOverlay.style.display = 'none';
        });
    });
</script>

@endsection