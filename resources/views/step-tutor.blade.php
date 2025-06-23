@extends('layouts.app')

@section('content')
<!-- loading spinner -->
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
    transition: background-color 0.3s ease;
  }

  .ck-btn:hover {
    background-color: #d32078;
  }

  .ck-title {
    font-size: 2rem;
    font-weight: 600;
    color: #EC298B;
    margin-bottom: 30px;
  }

  label {
    font-weight: 500;
    color: #2c3e50;
  }

  pre {
    white-space: pre-wrap;
    background-color: #f0f4f8;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #dce3ed;
    font-family: 'Courier New', monospace;
  }

  .form-control {
    border-radius: 6px;
    border: 1px solid #ccd6e0;
    box-shadow: none;
  }

  .form-control:focus {
    border-color: #EC298B;
    box-shadow: 0 0 0 0.2rem rgba(236, 41, 139, 0.2);
  }

  .spinner-border.text-pink { 
    color: #EC298B;
  }

  #loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255,255,255,0.8);
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
        <h2 class="ck-title text-center">Step-by-Step Tutor</h2>

        <!-- Conversation display -->
        @if(Session::has('chat_history'))
          <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
            @foreach (Session::get('chat_history') as $entry)
              <div class="mb-2">
                <strong style="color: {{ $entry['role'] === 'user' ? '#2c3e50' : '#EC298B' }}">
                  {{ ucfirst($entry['role']) }}:
                </strong>
                <div style="white-space: pre-line;">{{ $entry['content'] }}</div>
              </div>
            @endforeach
          </div>
        @endif

        <!-- Follow-up message input -->
        <form action="{{ url('/step-tutor') }}" method="POST">
          @csrf

          <!-- Grade Level only on first interaction -->
          @if(!Session::has('chat_history'))
            <div class="mb-3">
              <label class="form-label">Grade Level</label>
              <input type="text" class="form-control" name="grade_level" required>
            </div>
          @else
            <!-- Hidden input so grade level persists -->
            <input type="hidden" name="grade_level" value="{{ Session::get('grade_level') }}">
          @endif

          <div class="mb-3">
            <label class="form-label">Your Message</label>
            <input type="text" class="form-control" name="topic" required>
          </div>

          <div class="text-center mt-4">
            <button type="submit" class="ck-btn">Send</button>
          </div>
        </form>

        <!-- Reset Button -->
        <form action="{{ url('/step-tutor/clear') }}" method="POST" class="text-center mt-3">
          @csrf
          <button type="submit" class="btn btn-outline-danger btn-sm">Reset Conversation</button>
        </form>

        @error('error')
          <div class="alert alert-danger mt-3">{{ $message }}</div>
        @enderror
      </div>
    </div>
  </div>
</div>

@if(isset($history))
  <hr>
  <h5 class="fw-bold" style="color:#EC298B;">Conversation History:</h5>
  <div class="mb-3">
    @foreach ($history as $entry)
      <p><strong>{{ ucfirst($entry['role']) }}:</strong> {{ $entry['content'] }}</p>
    @endforeach
  </div>
@endif
<script>
  document.querySelector('form[action="{{ url('/step-tutor') }}"]').addEventListener('submit', function () {
    document.getElementById('loading-overlay').style.display = 'flex';
  });
</script>
@endsection
