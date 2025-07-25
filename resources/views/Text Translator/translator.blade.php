@extends('layouts.bootstrap')
@extends('layouts.historysidenav')
@extends('layouts.header')
    
@section('content')
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .text-highlight {
            color: #ec008c;
            font-weight: 700;
        }
        .form-label {
            font-weight: 600;
        }
        .btn-primary {
            background-color: #ec008c;
            border-color: #ec008c;
        }
        .btn-primary:hover {
            background-color: #c30074;
            border-color: #c30074;
        }
        textarea[readonly] {
            background-color: #f1f3f5;
            border-radius: 10px;
            padding: 10px;
        }
        textarea[name="followup"] {
            border-color: #ec008c;
        }
    </style>



<div class="container my-5">
    <div class="card shadow-sm">
            

        <div class="card-body">
            {{-- 🌐 AI Text Translator Heading --}}
            <h2 class="text-center text-highlight mb-3">🌐 AI Text Translator</h2>
            <p class="text-muted text-center mb-4">Translate any text into your selected language.</p>

            <!-- {{-- ✅ Conversation History Display --}}
            @php
            $seenSessions = [];
            @endphp

            @if (!empty($messages))
                <div class="mt-4">
                    <h5 class="fw-bold">Conversation History</h5>
                    <ul class="list-group mb-3">
                        @foreach ($messages as $msg)
                            @if (!in_array($msg['message_id'], $seenSessions))
                                @php $seenSessions[] = $msg['message_id']; @endphp
                                <li class="list-group-item">
                                    <a href="{{ route('translator.specific', ['message_id' => $msg['message_id']]) }}" class="text-decoration-none">
                                        <div>{{ $msg['topic'] ?? '[No message]' }}</div>
                                        <small class="text-muted">
                                            {{ $msg['created_at'] ? \Carbon\Carbon::parse($msg['created_at'])->diffForHumans() : 'just now' }}
                                        </small>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif -->

            {{-- ✅ Translation Input Form --}}
            <form action="{{ route('translator.process') }}" method="POST" id="translateForm">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="language" class="form-label">Translate into:</label>
                        <select class="form-select" name="language" id="language" required>
                            <option value="">-- Choose a language --</option>
                            @php
                                $languages = ['Afrikaans', 'Albanian', 'Arabic', 'Armenian', 'Azerbaijani', 'Basque', 'Belarusian', 'Bengali', 'Bosnian', 'Bulgarian', 'Catalan', 'Cebuano', 'Chichewa', 'Chinese (Simplified)', 'Chinese (Traditional)', 'Corsican', 'Croatian', 'Czech', 'Danish', 'Dutch', 'English', 'Esperanto', 'Estonian', 'Filipino', 'Finnish', 'French', 'Frisian', 'Galician', 'Georgian', 'German', 'Greek', 'Gujarati', 'Haitian Creole', 'Hausa', 'Hawaiian', 'Hebrew', 'Hindi', 'Hmong', 'Hungarian', 'Icelandic', 'Igbo', 'Indonesian', 'Irish', 'Italian', 'Japanese', 'Javanese', 'Kannada', 'Kazakh', 'Khmer', 'Korean', 'Kurdish (Kurmanji)', 'Kyrgyz', 'Lao', 'Latin', 'Latvian', 'Lithuanian', 'Luxembourgish', 'Macedonian', 'Malagasy', 'Malay', 'Malayalam', 'Maltese', 'Maori', 'Marathi', 'Mongolian', 'Myanmar (Burmese)', 'Nepali', 'Norwegian', 'Pashto', 'Persian', 'Polish', 'Portuguese', 'Punjabi', 'Romanian', 'Russian', 'Samoan', 'Scots Gaelic', 'Serbian', 'Sesotho', 'Shona', 'Sindhi', 'Sinhala', 'Slovak', 'Slovenian', 'Somali', 'Spanish', 'Sundanese', 'Swahili', 'Swedish', 'Tajik', 'Tamil', 'Telugu', 'Thai', 'Turkish', 'Ukrainian', 'Urdu', 'Uzbek', 'Vietnamese', 'Welsh', 'Xhosa', 'Yiddish', 'Yoruba', 'Zulu'];
                            @endphp
                            @foreach($languages as $lang)
                                <option value="{{ $lang }}" {{ old('language', $old['language'] ?? '') === $lang ? 'selected' : '' }}>{{ $lang }}</option>
                            @endforeach
                        </select>
                        @error('language')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    {{-- Input Text --}}
                    <div class="col-md-6 mb-3">
                        <label for="text" class="form-label">Enter text to translate:</label>
                        <textarea class="form-control" name="text" id="text" rows="7" required placeholder="Enter text to translate...">{{ old('text', $old['text'] ?? '') }}</textarea>
                        @error('text')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Translated Output --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Translation will appear here:</label>
                        <textarea class="form-control bg-light" rows="7" readonly>{{ $translation ?? '' }}</textarea>
                    </div>
                </div>

                <div class="d-grid d-md-flex justify-content-md-end">
                    <button type="submit" id="submitBtn" class="btn btn-primary px-4">
                        <span id="btnText">Translate</span>
                        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>


        </div>
    </div>
</div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex d-none justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 9999;">
    <div class="text-center">
        <div class="spinner-border text-highlight mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <div class="fw-semibold text-highlight">Translating your text...</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('translateForm');
        const btn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const overlay = document.getElementById('loadingOverlay');

        form.addEventListener('submit', function () {
            btn.disabled = true;
            btnText.textContent = 'Translating...';
            btnSpinner.classList.remove('d-none');
            overlay.classList.remove('d-none');
        });
    });
</script>
@endsection
