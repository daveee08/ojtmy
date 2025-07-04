@extends('layouts.app')

@section('content')
    <div id="loading-overlay">
        <div class="spinner-border text-pink" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-center fw-bold" style="color:#EC298B;">Generating your summary...</p>
    </div>

    <style>
        body {
            background-color: #f4f7fb;
            font-family: 'Poppins', sans-serif;
        }
        .ck-card { background-color: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); padding: 40px 30px; border: 1px solid #e4e8f0; }
        .ck-btn { background-color: #EC298B; color: #fff; border: none; padding: 12px 28px; border-radius: 10px; font-weight: 600; font-size: 16px; transition: background-color 0.3s ease; }
        .ck-btn:hover { background-color: #d32078; }
        .ck-title { font-size: 2.2rem; font-weight: 700; color: #EC298B; text-align: center; margin-bottom: 25px; }
        .chat-box { max-height: 300px; overflow-y: auto; padding: 15px; background-color: #fdfdfe; border: 1px solid #e4e8f0; border-radius: 12px; margin-bottom: 20px; }
        .message { margin-bottom: 15px; }
        .message-content { background-color: #f0f4f8; padding: 10px 15px; border-radius: 10px; white-space: pre-line; font-size: 15px; }
        #loading-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255, 255, 255, 0.8); z-index: 9999; align-items: center; justify-content: center; flex-direction: column; }
    </style>

    <div class="row">
        <div class="col-md-3">
            <h5 class="fw-bold mb-3">Summary Sessions</h5>
            <ul class="list-group">
                @foreach ($threads as $thread)
                    <li class="list-group-item {{ $thread->id == $activeThread ? 'active' : '' }}">
                        <a href="{{ url('/summarize?thread_id=' . $thread->id) }}" class="text-decoration-none text-dark">
                            {{ \Illuminate\Support\Str::limit($thread->topic, 50) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="col-md-9">
            <div class="container py-5">
                <div class="ck-card">
                    <h2 class="ck-title">Smart Summarizer</h2>

                    @if (isset($history) && count($history) > 0)
                        <div class="chat-box" style="background:#f8f9fb;">
                            @foreach ($history as $entry)
                                <div class="message d-flex {{ $entry['role'] === 'human' ? 'justify-content-end' : 'justify-content-start' }}">
                                    <div class="w-75">
                                        <div class="fw-bold mb-1 {{ $entry['role'] === 'human' ? 'text-end text-primary' : 'text-start text-pink' }}">
                                            {{ $entry['role'] === 'human' ? 'You' : 'Summarizer' }}
                                        </div>
                                        <div class="message-content p-3 mb-2 {{ $entry['role'] === 'human' ? 'bg-white border border-primary' : 'bg-light border border-pink' }}">
                                            {{ $entry['content'] }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form id="summarizer-form" action="{{ url('/summarize') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        @if (!isset($history) || count($history) === 0)
                            <div class="mb-3">
                                <label class="form-label">Summary Instructions</label>
                                <input type="text" class="form-control" name="summary_instructions" placeholder="E.g., 1 paragraph, bullet points, etc." required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Text to Summarize</label>
                                <textarea class="form-control" name="input_text" rows="5" placeholder="Paste the content or upload a PDF below..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Or Upload PDF</label>
                                <input type="file" class="form-control" name="pdf" accept="application/pdf">
                            </div>

                        @else
                            <input type="hidden" name="summary_instructions" value="1 paragraph">
                            <input type="hidden" name="pdf">
                            <input type="hidden" name="summary_instructions" value="">
                            <input type="hidden" name="message_id" value="{{ $activeThread }}">
                            <div class="mb-3">
                                <label class="form-label">Follow-up</label>
                                <input type="text" class="form-control" name="input_text" placeholder="Ask something new or clarify...">
                            </div>
                        @endif

                        <div class="text-center mt-4">
                            <button type="submit" class="ck-btn">Send</button>
                        </div>
                    </form>

                    <form action="{{ url('/summarizer/clear') }}" method="POST" class="text-center mt-3">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">Reset Conversation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
function attachSummarizerFormHandler() {
    const form = document.getElementById('summarizer-form');
    if (!form) return;
    form.onsubmit = async function(event) {
        event.preventDefault();
        const loadingOverlay = document.getElementById('loading-overlay');
        loadingOverlay.style.display = 'flex';
        const formData = new FormData(form);
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            let data;
            try {
                data = await response.json();
            } catch (e) {
                alert('Server error: Invalid JSON response.');
                loadingOverlay.style.display = 'none';
                return;
            }
            // Always use data.message, like Tutor blade
            const summary = (typeof data.message === 'string' && data.message) ? data.message : 'No summary returned.';

            // Find or create chat box
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

            // Append user and assistant messages
            const userMsg = formData.get('input_text') || formData.get('summary_instructions') || '[No input]';
            const userMessage = 
                <div class=\"message d-flex justify-content-end\"><div class=\"w-75\"><div class=\"fw-bold mb-1 text-end text-primary\">You</div><div class=\"message-content p-3 mb-2 bg-white border border-primary\" style=\"border-radius:12px;\">${userMsg}</div></div></div>;
            const assistantMessage = 
                <div class=\"message d-flex justify-content-start\"><div class=\"w-75\"><div class=\"fw-bold mb-1 text-start text-pink\">Summarizer</div><div class=\"message-content p-3 mb-2 bg-light border border-pink\" style=\"border-radius:12px;\">${summary}</div></div></div>;
            chatBox.innerHTML += userMessage + assistantMessage;
            chatBox.scrollTop = chatBox.scrollHeight;

            // Replace form with follow-up version if it's the initial form
            if (form.querySelector('textarea[name="input_text"]')) {
                const csrfToken = document.querySelector('input[name="_token"]').value;
                const messageId = data.message_id;
                form.innerHTML = 
                    <input type=\"hidden\" name=\"input_text\" value=\"\">\n<input type=\"hidden\" name=\"summary_instructions\" value=\"\">\n<input type=\"hidden\" name=\"pdf\">\n<input type=\"hidden\" name=\"message_id\" value=\"${messageId}\">\n<input type=\"hidden\" name=\"_token\" value=\"${csrfToken}\">\n<div class=\"mb-3\">\n<label class=\"form-label\">Follow-up</label>\n<input type=\"text\" class=\"form-control\" name=\"input_text\" placeholder=\"Ask something new or clarify...\">\n</div>\n<div class=\"text-center mt-4\">\n<button type=\"submit\" class=\"ck-btn\">Send</button>\n</div>\n;
                // Re-attach handler after replacing form content
                attachSummarizerFormHandler();
            } else {
                // If already follow-up, just clear the input
                const followInput = form.querySelector('[name="input_text"]');
                if (followInput) followInput.value = '';
            }
        } catch (err) {
            alert('Something went wrong:\n' + err.message);
        } finally {
            loadingOverlay.style.display = 'none';
        }
    };
}

document.addEventListener('DOMContentLoaded', attachSummarizerFormHandler);
</script>
@endsection
