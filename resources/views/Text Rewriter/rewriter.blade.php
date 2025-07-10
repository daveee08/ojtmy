@extends('layouts.bootstrap')
@extends('layouts.historysidenav')
@extends('layouts.header')

@section('title', 'AI Text Rewriter')

@section('styles')
    <style>
        .container {
            position: absolute;
            background: white;
            max-width: 700px;
            width: 100%;
            margin-top: 50px;
            padding: 2.5rem 3rem;
            border-radius: 12px;
        }

        h2 {
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        p.subtitle {
            text-align: center;
            color: #6c757d;
            margin-bottom: 2rem;
        }

        label {
            font-weight: 600;
            color: #34495e;
        }

        textarea.form-control {
            resize: none;
            overflow-y: auto;
            max-height: 400px;
            background-color: #fff !important;
            color: #2c3e50;
            font-family: inherit;
            line-height: 1.5;
            padding-top: 0.8rem;
        }

        .text-center {
            margin-top: 1.8rem;
            margin-bottom: 1.8rem;
        }

        .hidden {
            display: none !important;
        }
    </style>
@endsection

@section('content')
    <div class="container">
        <h2>AI Text Rewriter</h2>
        <p class="subtitle">Rewrite any text using any criteria you choose.</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="rewriterForm" method="POST" action="/rewriter" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="input_type" class="form-label">Select Input Type</label>
                <select class="form-select" id="input_type" name="input_type" required onchange="toggleInputFields()">
                    <option value="" disabled selected>Choose input method</option>
                    <option value="topic">Text</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>

            <div class="mb-4" id="text_input_group" style="display: none;">
                <label for="topic" class="form-label">Enter Text</label>
                <textarea class="form-control" id="topic" name="topic" rows="6" placeholder="Paste or type text here"></textarea>
            </div>

            <div class="mb-4" id="pdf_input_group" style="display: none;">
                <label for="pdf_file" class="form-label">Upload PDF</label>
                <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept="application/pdf" />
            </div>

            <div class="mb-4" id="custom_instruction_group">
                <label for="custom_instruction" class="form-label">Rewrite so that:</label>
                <textarea class="form-control" id="custom_instruction" name="custom_instruction" rows="2"
                    placeholder="Customize how the text should be rewritten (e.g., use simpler words, shorten text, etc.)"></textarea>
            </div>

            <div class="mb-4 text-center">
                <button type="submit" class="btn btn-primary" id="submitButton">
                    <span id="btnText">Rewrite</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm hidden" role="status"
                        aria-hidden="true"></span>
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        function toggleInputFields() {
            const mode = document.getElementById('input_type').value;
            const textGroup = document.getElementById('text_input_group');
            const pdfGroup = document.getElementById('pdf_input_group');

            textGroup.style.display = (mode === 'topic') ? 'block' : 'none';
            pdfGroup.style.display = (mode === 'pdf') ? 'block' : 'none';
        }

        document.getElementById('rewriterForm').addEventListener('submit', function() {
            document.getElementById('loading-overlay').style.display = 'flex';
            this.querySelector('button[type="submit"]').disabled = true;
        });
    </script>
@endsection
