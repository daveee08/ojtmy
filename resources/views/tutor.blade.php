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
        @if(isset($history) && count($history) > 0)
          <div class="chat-box" style="background:#f8f9fb;">
            @foreach ($history as $entry)
              <div class="message d-flex {{ $entry['role'] === 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                <div class="w-75">
                  <div class="fw-bold mb-1 {{ $entry['role'] === 'user' ? 'text-end text-primary' : 'text-start text-pink' }}">
                    {{ $entry['role'] === 'user' ? 'You' : 'Tutor' }}
                  </div>
                  <div class="message-content p-3 mb-2 {{ $entry['role'] === 'user' ? 'bg-white border border-primary' : 'bg-light border border-pink' }}" style="border-radius:12px;">
                    {{ $entry['content'] }}
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif

        <!-- Tutor Form -->
        <form id="tutor-form" action="{{ url('/tutor') }}" method="POST" enctype="multipart/form-data">
          @csrf

          @if(!isset($history) || count($history) === 0)
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
            <input type="hidden" name="grade_level" value="{{ isset($history) && count($history) > 0 ? $history[0]['grade_level'] ?? '' : '' }}">
            <input type="hidden" name="input_type" value="topic">
            <input type="hidden" name="add_cont" value="">
            <div class="mb-3">
              <label class="form-label">Follow Up Message</label>
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

        @if(session('status'))
          <div class="alert alert-success mt-4">{{ session('status') }}</div>
        @endif
      </div>
    </div>
  </div>
</div>

<script>
  // Toggle topic and PDF inputs based on selected input type
  document.getElementById('input_type')?.addEventListener('change', function () {
    const type = this.value;
    document.getElementById('topic-input').classList.toggle('d-none', type === 'pdf');
    document.getElementById('pdf-input').classList.toggle('d-none', type === 'topic');
  });

  // Handle form submission asynchronously (AJAX)
  document.getElementById('tutor-form').addEventListener('submit', function (event) {
    event.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const loadingOverlay = document.getElementById('loading-overlay');

    loadingOverlay.style.display = 'flex';

    fetch(form.action, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    .then(response => {
      if (!response.ok) throw new Error('Network response was not OK');
      return response.json();
    })
    .then(data => {
      loadingOverlay.style.display = 'none';

      // Inject chat-box if not present
      let chatBox = document.querySelector('.chat-box');
      if (!chatBox) {
        const newBox = document.createElement('div');
        newBox.className = 'chat-box';
        newBox.style.background = '#f8f9fb';
        newBox.style.maxHeight = '300px';
        newBox.style.overflowY = 'auto';
        newBox.style.padding = '15px';
        newBox.style.border = '1px solid #e4e8f0';
        newBox.style.borderRadius = '12px';
        newBox.style.marginBottom = '20px';

        const formCard = document.querySelector('.ck-card');
        formCard.insertBefore(newBox, formCard.querySelector('form'));
        chatBox = newBox;
      }

      // Append messages
      const userMessage = `
        <div class="message d-flex justify-content-end">
          <div class="w-75">
            <div class="fw-bold mb-1 text-end text-primary">You</div>
            <div class="message-content p-3 mb-2 bg-white border border-primary" style="border-radius:12px;">
              ${formData.get('topic')}
            </div>
          </div>
        </div>
      `;

      const assistantMessage = `
        <div class="message d-flex justify-content-start">
          <div class="w-75">
            <div class="fw-bold mb-1 text-start text-pink">Tutor</div>
            <div class="message-content p-3 mb-2 bg-light border border-pink" style="border-radius:12px;">
              ${data.message}
            </div>
          </div>
        </div>
      `;

      chatBox.innerHTML += userMessage + assistantMessage;
      chatBox.scrollTop = chatBox.scrollHeight;

      // 🔄 Replace form with follow-up version if it's still initial
      if (form.querySelector('select[name="input_type"]')) {
        const gradeLevel = formData.get('grade_level') || 'Not set';

        const csrfToken = document.querySelector('input[name="_token"]')?.value || '';

        form.innerHTML = `
          <input type="hidden" name="_token" value="${csrfToken}">
          <input type="hidden" name="grade_level" value="${gradeLevel}">
          <input type="hidden" name="input_type" value="topic">
          <input type="hidden" name="add_cont" value="">
          <div class="mb-3">
            <label class="form-label">Follow Up Message</label>
            <input type="text" class="form-control" name="topic" placeholder="Continue the conversation..." required>
          </div>
          <div class="text-center mt-4">
            <button type="submit" class="ck-btn">Send</button>
          </div>
        `;

      } else {
        // Just reset follow-up input
        const topicInput = form.querySelector('[name="topic"]');
        if (topicInput) topicInput.value = '';
      }
    })
    .catch(async (error) => {
      loadingOverlay.style.display = 'none';
      try {
        const errorText = await error?.response?.text?.();
        console.error('Server error:', errorText || error.message);
        alert('Server error:\n' + (errorText || error.message));
      } catch (e) {
        console.error('Unhandled Error:', error);
        alert('Something went wrong. Check console.');
      }
    });
  });
</script>

@endsection