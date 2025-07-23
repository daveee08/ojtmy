@extends('layouts.bootstrap')
@extends('layouts.header')
@extends('layouts.navbaragent')

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
        .message-content {
            background-color: #f0f4f8;
            padding: 10px 15px;
            border-radius: 10px;
            white-space: pre-line;
            font-size: 15px;
        }
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
    </style>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="ck-card">
                    <h2 class="ck-title">Smart Summarizer</h2>

                    <form id="summarizer-form" action="{{ url('/summarize') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Summary Instructions</label>
                            <input type="text" class="form-control" name="summary_instructions" placeholder="E.g., 1 paragraph, bullet points, etc." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Text to Summarize</label>
                            <textarea class="form-control" name="input_text" rows="5" placeholder="Paste the content or upload a PDF below..."></textarea>
                        </div>

                        <!-- <div class="mb-3">
                            <label class="form-label">Or Upload PDF</label>
                            <input type="file" class="form-control" name="pdf" accept="application/pdf">
                        </div> -->

                        <div class="text-center mt-4">
                            <button type="submit" class="ck-btn">Send</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
