@extends('layouts.app')

@section('content')

<!-- Loading Spinner -->
<div id="loading-overlay">
  <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
    <span class="visually-hidden">Loading...</span>
  </div>
  <p class="mt-3 text-center fw-semibold" style="color:#0d6efd;">Just a moment...</p>
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

  .chat-box {
    max-height: 300px;
    overflow-y: auto;
    padding: 16px;
    background: #f9fafc;
    border: 1px solid #e3e6ef;
    border-radius: 12px;
    margin-bottom: 25px;
  }

  .message {
    margin-bottom: 12px;
  }

  .message .fw-bold {
    font-size: 14px;
  }

  .message-content {
    background: #eef2f6;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 15px;
    line-height: 1.5;
    white-space: pre-line;
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
    background-color: #EC298B;
  }

  .form-control {
    border-radius: 8px;
    font-size: 15px;
  }

  .form-control:focus {
    border-color: #EC298B;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.2);
  }

  #loading-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background-color: rgba(255, 255, 255, 0.8);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    flex-direction: column;
  }

  .btn-outline-danger.btn-sm {
    padding: 6px 12px;
    font-size: 14px;
  }
</style>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
      <div class="ck-card">
        <h2 class="ck-title">Step-by-Step Tutor</h2>

        <!-- Form -->
        <form id="step-tutor-form" action="{{ url('/step-tutor') }}" method="POST" enctype="multipart/form-data">
          @csrf
            <div class="mb-3">
              <label class="form-label">Grade Level</label>
              <input type="text" class="form-control" name="grade_level" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Your Message</label>
              <input type="text" class="form-control" name="topic" placeholder="Enter your topic or question..." required>
            </div>

          <div class="text-center mt-4">
            <button type="submit" class="ck-btn">Send</button>
          </div>
        </form>

        <form action="{{ url('/step-tutor/clear') }}" method="POST" class="text-center mt-3">
          @csrf
          <button type="submit" class="btn btn-outline-danger btn-sm">Reset Conversation</button>
        </form>

        @error('error')
          <div class="alert alert-danger mt-4">{{ $message }}</div>
        @enderror

        @if(session('status'))
          <div class="alert alert-success mt-4">{{ session('status') }}</div>
        @endif
      </div>
    </div>
  </div>
</div>


<script>
@endsection
