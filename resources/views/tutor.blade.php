@extends('layouts.app')

@section('content')
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
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    padding: 40px 30px;
    border: 1px solid #e4e8f0;
  }

  .ck-btn {
    background-color: #EC298B;
    color: #fff;
    border: none;
    padding: 12px 28px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 16px;
    transition: background-color 0.3s ease;
  }

  .ck-btn:hover {
    background-color: #d32078;
  }

  .ck-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: #EC298B;
    text-align: center;
    margin-bottom: 25px;
  }

  .chat-box {
    max-height: 300px;
    overflow-y: auto;
    padding: 15px;
    background-color: #fdfdfe;
    border: 1px solid #e4e8f0;
    border-radius: 12px;
    margin-bottom: 20px;
  }

  .message {
    margin-bottom: 15px;
  }

  .message .user {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 3px;
  }

  .message .assistant {
    color: #EC298B;
    font-weight: 600;
    margin-bottom: 3px;
  }

  .message-content {
    background-color: #f0f4f8;
    padding: 10px 15px;
    border-radius: 10px;
    white-space: pre-line;
    font-size: 15px;
  }

  .form-control,
  .form-select {
    border-radius: 8px;
    border: 1px solid #ccd6e0;
    box-shadow: none;
  }

  .form-control:focus,
  .form-select:focus {
    border-color: #EC298B;
    box-shadow: 0 0 0 0.2rem rgba(236, 41, 139, 0.2);
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
        <h2 class="ck-title">AI Tutor Assistant</h2>

        <!-- Chat Display -->
        @if(Session::has('chat_history'))
          <div class="chat-box">
            @foreach (Session::get('chat_history') as $entry)
              <div class="message">
                <div class="{{ $entry['role'] === 'user' ? 'user' : 'assistant' }}">
                  {{ ucfirst($entry['role']) }}:
                </div>
                <div class="message-content">{{ $entry['content'] }}</div>
              </div>
            @endforeach
          </div>
        @endif

        <!-- Tutor Form -->
        <form action="{{ url('/tutor') }}" method="POST" enctype="multipart/form-data">
          @csrf

          @if(!Session::has('chat_history'))
            <div class="mb-3">
              <label class="form-label">Grade Level</label>
              <input type="text" class="form-control" name="grade_level" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Input Type</label>
              <select class="form-select" name="input_type" id="input_type">
                <option value="topic">Topic</option>
                <option value="pdf">PDF</option>
              </select>
            </div>

            <div class="mb-3" id="topic-input">
              <label class="form-label">Topic</label>
              <input type="text" class="form-control" name="topic" placeholder="Enter your topic or question...">
            </div>

            <div class="mb-3 d-none" id="pdf-input">
              <label class="form-label">Upload PDF</label>
              <input type="file" class="form-control" name="pdf_file" accept="application/pdf">
            </div>

            <div class="mb-3">
              <label class="form-label">Additional Context (optional)</label>
              <textarea class="form-control" name="add_cont" rows="3" placeholder="Anything else the tutor should know?"></textarea>
            </div>
          @else
            <input type="hidden" name="grade_level" value="{{ Session::get('grade_level') }}">
            <input type="hidden" name="input_type" value="topic">
            <input type="hidden" name="add_cont" value="">
            <div class="mb-3">
              <label class="form-label">Your Message</label>
              <input type="text" class="form-control" name="topic" placeholder="Continue the conversation..." required>
            </div>
          @endif

          <div class="text-center mt-4">
            <button type="submit" class="ck-btn">Send</button>
          </div>
        </form>

        <!-- Reset Conversation Button -->
        <form action="{{ url('/tutor/clear') }}" method="POST" class="text-center mt-3">
          @csrf
          <button type="submit" class="btn btn-outline-danger btn-sm">Reset Conversation</button>
        </form>

        @error('error')
          <div class="alert alert-danger mt-4">{{ $message }}</div>
        @enderror
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('input_type')?.addEventListener('change', function () {
    const type = this.value;
    document.getElementById('topic-input').classList.toggle('d-none', type === 'pdf');
    document.getElementById('pdf-input').classList.toggle('d-none', type === 'topic');
  });

  document.querySelector('form[action="{{ url('/tutor') }}"]').addEventListener('submit', function () {
    document.getElementById('loading-overlay').style.display = 'flex';
  });
</script>
@endsection
