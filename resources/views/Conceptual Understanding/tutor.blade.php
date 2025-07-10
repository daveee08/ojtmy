@extends('layouts.app')

@section('content')

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

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
            background-color: rgba(255, 255, 255, 0.8);
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
                    <h2 class="ck-title">Conceptual Understanding </h2>


                    <!-- Tutor Form -->
                    <form id="tutor-form" action="{{ url('/tutor') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                            <div class="mb-3">
                                <label class="form-label">Grade Level</label>
                                <input type="text" class="form-control" name="grade_level">
                            </div>

                            <div class="mb-3" id="topic-input">
                                <label class="form-label">Topic</label>
                                <input type="text" class="form-control" name="topic"
                                    placeholder="Enter your topic or question...">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Additional Context (optional)</label>
                                <textarea class="form-control" name="add_cont" rows="3" placeholder="Anything else the tutor should know?"></textarea>
                            </div>

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

                    @if (session('status'))
                        <div class="alert alert-success mt-4">{{ session('status') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>




    <script>

        // Handle form submission asynchronously (AJAX)
        document.getElementById('tutor-form').addEventListener('submit', function(event)) {
            event.preventDefault();

            const form = this;
            const formData = new FormData(form);
            const loadingOverlay = document.getElementById('loading-overlay');

            loadingOverlay.style.display = 'flex';
        }
            
    </script>

@endsection
