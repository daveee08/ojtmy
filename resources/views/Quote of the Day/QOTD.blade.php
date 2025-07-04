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
        padding: 1.5rem 1rem; /* Adjusted for mobile-first */
        border-radius: 18px;
        box-shadow: 0 2px 16px rgba(80, 60, 200, 0.08);
        margin: 1.5rem auto; /* Adjusted for mobile-first */
    }
    .h2-qotd {
        text-align:center;
        font-weight:600;
        margin-bottom: 0.5rem; /* Adjusted margin */
        color: #e91e63;
        font-size: 1.75rem; /* Mobile-first heading size */
    }
    .p-qotd {
        text-align:center;
        color:#555;
        margin-bottom: 1.5rem; /* Adjusted margin */
        font-size: 0.9rem;
    }
    .btn-primary-qotd {
        background:#e91e63;
        border:none;
        font-weight:600;
        font-size:1rem; /* Adjusted font size */
        border-radius:30px;
        width: 100%; /* Full width for mobile */
        padding: 0.75rem 1.5rem; /* Increased padding for better touch target */
        margin-bottom: 0.5rem; /* Space if buttons stack */
    }
    .form-control-qotd {
        border-color: #ddd;
        min-height: 48px; /* Ensure touch friendliness */
        font-size: 1rem; /* Consistent font size */
    }
    .quote-display-qotd {
        margin-top: 1.5rem; /* Adjusted margin */
        padding: 1.5rem; /* Adjusted padding */
        background: #f7f7ff;
        border-radius: 12px;
        text-align:center;
        font-size:1.1em;
        color:#333;
    }
    .btn-sm-outline-secondary-qotd,
    .btn-sm-outline-danger-qotd {
        border:1px solid #e91e63;
        color:#e91e63;
        background:transparent;
        width: 100%; /* Full width for mobile */
        margin-bottom: 0.5rem; /* Space if buttons stack */
        padding: 0.5rem 1rem; /* Adjusted padding for better touch target */
        font-size: 0.9rem; /* Adjusted font size */
    }
    .btn-sm-outline-secondary-qotd:hover,
    .btn-sm-outline-danger-qotd:hover {
        background-color: #e91e63;
        color: #fff;
        border-color: #e91e63;
    }

    /* Media query for larger screens (e.g., tablets and desktops) */
    @media (min-width: 768px) {
        .container-qotd {
            padding: 32px; /* Restore original padding */
            margin: 40px auto; /* Restore original margin */
        }
        .h2-qotd {
            font-size: 2rem; /* Adjust for desktop */
            margin-bottom: 8px;
        }
        .p-qotd {
            margin-bottom: 32px;
            font-size: 1em;
        }
        .btn-primary-qotd {
            width: auto; /* Restore auto width */
            padding: 10px 20px; /* Restore original padding */
            margin-bottom: 0; /* Remove bottom margin */
        }
        .quote-display-qotd {
            margin-top: 32px;
            padding: 24px;
            font-size: 1.2em;
        }
        .btn-sm-outline-secondary-qotd,
        .btn-sm-outline-danger-qotd {
            width: auto; /* Restore auto width */
            margin-bottom: 0;
            display: inline-block; /* Make them inline */
        }
        .quote-display-qotd form:first-of-type + form {
            margin-left: 10px; /* Add margin between download buttons */
        }
    }
</style>

<div class="container-qotd">
    <h2 class="h2-qotd">Quote of the Day</h2>
    <p class="p-qotd">Generate quote of the day suggestions based on any topic.</p>
    <form method="POST" action="/qotd">
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
            <button type="submit" class="btn btn-primary-qotd">Generate</button>
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
@endsection
