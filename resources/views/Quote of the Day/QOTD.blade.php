@extends('layouts.app')

@section('content')
<style>
    body {
        background: linear-gradient(to right, #ffe6ec, #ffffff);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #2c2c2c;
    }
    .container-qotd {
        background: #ffffff;
        max-width: 700px;
        padding: 32px;
        border-radius: 18px;
        box-shadow: 0 2px 16px rgba(80, 60, 200, 0.08);
        margin: 40px auto;
    }
    .h2-qotd {
        text-align:center;
        font-weight:600;
        margin-bottom:8px;
        color: #e91e63; /* Matching Quizme's primary color */
    }
    .p-qotd {
        text-align:center;
        color:#555;
        margin-bottom:32px;
    }
    .btn-primary-qotd {
        background:#e91e63; /* Matching Quizme's primary button color */
        border:none;
        font-weight:600;
        font-size:1.1em;
        border-radius:30px;
        flex:3;
        padding: 10px 20px; /* Added padding for better appearance */
        cursor: pointer; /* Indicate it's clickable */
        transition: background-color 0.3s ease;
    }
    .btn-primary-qotd:hover {
        background-color: #d81b60; /* Darker shade on hover */
    }
    .form-control-qotd {
        border-color: #ddd; /* Subtle border for form controls */
        padding: 10px 15px; /* Added padding for better input feel */
        border-radius: 8px; /* Slightly rounded corners for inputs */
        width: 100%; /* Ensure inputs take full width */
        box-sizing: border-box; /* Include padding and border in the element's total width and height */
    }
    .quote-display-qotd {
        margin-top: 32px;
        padding: 24px;
        background: #f7f7ff; /* Lighter background for the quote box */
        border-radius: 12px;
        text-align:center;
        font-size:1.2em;
        color:#333;
        word-wrap: break-word; /* Ensure long quotes wrap */
    }
    .btn-sm-outline-secondary-qotd,
    .btn-sm-outline-danger-qotd {
        border:1px solid #e91e63; /* Outline color matching Quizme */
        color:#e91e63;
        background:transparent;
        padding: 8px 16px; /* Adjusted padding for smaller buttons */
        border-radius: 20px; /* Rounded corners for download buttons */
        cursor: pointer;
        transition: background-color 0.3s ease, color 0.3s ease;
    }
    .btn-sm-outline-secondary-qotd:hover,
    .btn-sm-outline-danger-qotd:hover {
        background-color: #e91e63; /* Hover background matching Quizme */
        color: #fff;
        border-color: #e91e63;
    }
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
    }
    .loading-spinner {
        display: none; /* Hidden by default */
        border: 4px solid rgba(0, 0, 0, 0.1);
        border-left-color: #e91e63;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        animation: spin 1s linear infinite;
        margin-left: 10px;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .button-content {
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<div class="container-qotd">
    <h2 class="h2-qotd">Quote of the Day</h2>
    <p class="p-qotd">Generate quote of the day suggestions based on any topic.</p>

    {{-- Display validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/qotd" id="qotd-form">
        @csrf
        <div style="margin-bottom: 18px;">
            <label for="topic" style="font-weight:500;">Quotes about… : <span style="color:red">*</span></label>
            <textarea id="topic" name="topic" class="form-control form-control-qotd" rows="2" placeholder="getting through challenging times, heartbreak, hope, enjoying life" required>{{ old('topic', $topic ?? '') }}</textarea>
        </div>
        <div style="margin-bottom: 18px;">
            <label for="grade" style="font-weight:500;">Grade level: <span style="color:red">*</span></label>
            <select id="grade" name="grade" class="form-control form-control-qotd" required>
                <option value="Pre-K" {{ (old('grade', $grade ?? '') == 'Pre-K') ? 'selected' : '' }}>Pre-K</option>
                <option value="Kindergarten" {{ (old('grade', $grade ?? '') == 'Kindergarten') ? 'selected' : '' }}>Kindergarten</option>
                <option value="1st Grade" {{ (old('grade', $grade ?? '') == '1st Grade') ? 'selected' : '' }}>1st Grade</option>
                <option value="2nd Grade" {{ (old('grade', $grade ?? '') == '2nd Grade') ? 'selected' : '' }}>2nd Grade</option>
                <option value="3rd Grade" {{ (old('grade', $grade ?? '') == '3rd Grade') ? 'selected' : '' }}>3rd Grade</option>
                <option value="4th Grade" {{ (old('grade', $grade ?? '') == '4th Grade') ? 'selected' : '' }}>4th Grade</option>
                <option value="5th Grade" {{ (old('grade', $grade ?? '') == '5th Grade') ? 'selected' : '' }}>5th Grade</option>
                <option value="6th grade" {{ (old('grade', $grade ?? '') == '6th grade') ? 'selected' : '' }}>6th grade</option>
                <option value="7th grade" {{ (old('grade', $grade ?? '') == '7th grade') ? 'selected' : '' }}>7th grade</option>
                <option value="8th grade" {{ (old('grade', $grade ?? '') == '8th grade') ? 'selected' : '' }}>8th grade</option>
                <option value="9th grade" {{ (old('grade', $grade ?? '') == '9th grade') ? 'selected' : '' }}>9th grade</option>
                <option value="10th grade" {{ (old('grade', $grade ?? '') == '10th grade') ? 'selected' : '' }}>10th grade</option>
                <option value="11th grade" {{ (old('grade', $grade ?? '') == '11th grade') ? 'selected' : '' }}>11th grade</option>
                <option value="12th grade" {{ (old('grade', $grade ?? '') == '12th grade') ? 'selected' : '' }}>12th grade</option>
                <option value="University" {{ (old('grade', $grade ?? '') == 'University') ? 'selected' : '' }}>University</option>
                <option value="1st Year College" {{ (old('grade', $grade ?? '') == '1st Year College') ? 'selected' : '' }}>1st Year College</option>
                <option value="2nd Year College" {{ (old('grade', $grade ?? '') == '2nd Year College') ? 'selected' : '' }}>2nd Year College</option>
                <option value="3rd Year College" {{ (old('grade', $grade ?? '') == '3rd Year College') ? 'selected' : '' }}>3rd Year College</option>
                <option value="4th Year College" {{ (old('grade', $grade ?? '') == '4th Year College') ? 'selected' : '' }}>4th Year College</option>
                <option value="Adult" {{ (old('grade', $grade ?? '') == 'Adult') ? 'selected' : '' }}>Adult</option>
                <option value="Professional Staff" {{ (old('grade', $grade ?? '') == 'Professional Staff') ? 'selected' : '' }}>Professional Staff</option>
            </select>
        </div>
        <div style="display:flex; gap:16px; align-items:center; margin-bottom: 24px;">
            <button type="submit" class="btn btn-primary-qotd" id="generate-btn">
                <span class="button-content">
                    Generate
                    <span class="loading-spinner" id="loading-spinner"></span>
                </span>
            </button>
        </div>
    </form>

    @if(isset($quote))
        <div class="quote-display-qotd">
            <strong>Quote:</strong><br>
            <em>{{ $quote }}</em>
            <div style="margin-top: 20px;">
                <form action="{{ route('qotd.download') }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="content" value="{{ $quote }}">
                    <input type="hidden" name="filename" value="quote_of_the_day">
                    <input type="hidden" name="format" value="txt">
                    <button type="submit" class="btn btn-sm-outline-secondary-qotd">Save as Text</button>
                </form>
                <form action="{{ route('qotd.download') }}" method="POST" style="display:inline; margin-left: 10px;">
                    @csrf
                    <input type="hidden" name="content" value="{{ $quote }}">
                    <input type="hidden" name="filename" value="quote_of_the_day">
                    <input type="hidden" name="format" value="pdf">
                    <button type="submit" class="btn btn-sm-outline-danger-qotd">Save as PDF</button>
                </form>
            </div>
        </div>
    @endif
</div>

<script>
    document.getElementById('qotd-form').addEventListener('submit', function() {
        // Show loading spinner and disable button
        document.getElementById('loading-spinner').style.display = 'inline-block';
        document.getElementById('generate-btn').setAttribute('disabled', 'disabled');
        document.getElementById('generate-btn').style.opacity = '0.7';
    });
</script>
@endsection
