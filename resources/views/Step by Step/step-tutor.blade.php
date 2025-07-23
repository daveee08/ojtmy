@extends('layouts.bootstrap')
@extends('layouts.header')
@extends('layouts.navbaragent')

@section('content')

<!-- Loading Overlay -->
<div id="loading-overlay">
  <div class="spinner-border text-pink" role="status" style="width: 2.8rem; height: 2.8rem;">
    <span class="visually-hidden">Loading...</span>
  </div>
  <p class="mt-3 fw-semibold" style="color:#EC298B;">Just a moment...</p>
</div>

<style>
  body {
    background-color: #f5f7fa;
    font-family: 'Poppins', sans-serif;
  }

  .ck-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    padding: 40px;
    border: none;
    transition: all 0.3s ease-in-out;
  }

  .ck-title {
    font-size: 2rem;
    font-weight: 700;
    color: #EC298B;
    text-align: center;
    margin-bottom: 30px;
  }

  .form-control {
    border-radius: 10px;
    font-size: 15px;
    padding: 12px 15px;
    border: 1px solid #ced4da;
  }

  .form-control:focus {
    border-color: #EC298B;
    box-shadow: 0 0 0 0.2rem rgba(236, 41, 139, 0.15);
  }

  .ck-btn {
    background-color: #EC298B;
    color: #fff;
    font-weight: 600;
    font-size: 15px;
    border: none;
    border-radius: 10px;
    padding: 12px 28px;
    transition: background-color 0.3s ease, transform 0.2s ease;
  }

  .ck-btn:hover {
    background-color: #d32078;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(236, 41, 139, 0.15);
  }

  .btn-outline-danger.btn-sm {
    padding: 6px 14px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s ease;
  }

  .btn-outline-danger.btn-sm:hover {
    background-color: #dc3545;
    color: white;
  }

  #loading-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background-color: rgba(255, 255, 255, 0.85);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    flex-direction: column;
  }

  #loading-overlay.active {
    display: flex;
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

          <div class="mb-3">
            <label class="form-label fw-semibold">Your Message</label>
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
    document.getElementById('step-tutor-form').addEventListener('submit', function (event) {
        event.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const loadingOverlay = document.getElementById('loading-overlay');
        loadingOverlay.style.display = 'flex';
        form.submit();
    });
</script>

@endsection
