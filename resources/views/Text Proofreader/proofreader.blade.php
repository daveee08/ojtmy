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
    color: #EC298B;
    text-align: center;
    margin-bottom: 30px;
  }

  .ck-btn {
    background-color: #EC298B;
    color: white;
    border: none;
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.25s ease;
  }

  .ck-btn:hover {
    background-color: #c30074;
  }

  .form-control {
    border-radius: 8px;
    font-size: 15px;
  }

  .form-control:focus {
    border-color: #EC298B;
    box-shadow: 0 0 0 0.2rem rgba(236, 41, 139, 0.2);
  }

  .alert-success .ck-title-small {
    font-size: 1.25rem;
    font-weight: 600;
    color: #EC298B;
  }

  .alert-success ul {
    padding-left: 20px;
  }

  .alert-success ul li {
    margin-bottom: 8px;
    line-height: 1.6;
  }
</style>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
      <div class="ck-card">
        <h2 class="ck-title">🕵️ AI Agent Text Proofreader</h2>
        <p class="text-muted text-center mb-4">
          Proofread your writing with AI. This tool fixes grammar, punctuation, clarity, and more.
        </p>

        <form action="{{ route('proofreader.process') }}" method="POST" id="proofForm">
          @csrf

          <div class="mb-3">
            <label for="profile" class="form-label">Choose a profile type:</label>
            <select class="form-select" id="profile" name="profile" required>
              <option value="academic" {{ old('profile', $old['profile'] ?? '') === 'academic' ? 'selected' : '' }}>Academic</option>
              <option value="casual" {{ old('profile', $old['profile'] ?? '') === 'casual' ? 'selected' : '' }}>Casual</option>
              <option value="concise" {{ old('profile', $old['profile'] ?? '') === 'concise' ? 'selected' : '' }}>Concise</option>
            </select>
            @error('profile')
              <small class="text-danger">{{ $message }}</small>
            @enderror
          </div>

          <div class="mb-3">
            <label for="text" class="form-label">Enter your text to proofread:</label>
            <textarea class="form-control" id="text" name="text" rows="8" placeholder="Paste your content here..." required>{{ old('text', $old['text'] ?? '') }}</textarea>
            @error('text')
              <small class="text-danger">{{ $message }}</small>
            @enderror
          </div>

          <div class="text-center mt-4">
            <button type="submit" id="submitBtn" class="ck-btn">
              <span id="btnText">Submit</span>
              <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
            </button>
          </div>
        </form>

        @if(isset($response))
          <div class="alert alert-success mt-4">
            <h5 class="ck-title-small">Corrected Text</h5>
            <p>{{ $response['corrected'] }}</p>

            <h6 class="ck-title-small mt-4">Changes Made</h6>
            <ul>
              @foreach($response['changes'] as $change)
                <li>{{ ltrim($change, '* ') }}</li>
              @endforeach
            </ul>
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
    const form = document.getElementById('proofForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    const loadingOverlay = document.getElementById('loading-overlay');

    form.addEventListener('submit', function () {
      loadingOverlay.style.display = 'flex';
      submitBtn.disabled = true;
      btnText.textContent = 'Submitting...';
      btnSpinner.classList.remove('d-none');
    });

    window.addEventListener('load', function () {
      loadingOverlay.style.display = 'none';
    });
  });
</script>

@endsection
