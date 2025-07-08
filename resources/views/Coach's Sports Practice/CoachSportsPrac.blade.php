@extends('layouts.app')

@section('content')
<style>
    body {
        background: linear-gradient(to right, #ffe6ec, #ffffff);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #2c2c2c;
    }
    .container-csp {
        background: #ffffff;
        max-width: 700px;
        padding: 32px;
        border-radius: 18px;
        box-shadow: 0 2px 16px rgba(80, 60, 200, 0.08);
        margin: 40px auto;
    }
    .h2-csp {
        text-align:center;
        font-weight:600;
        margin-bottom:8px;
        color: #e91e63; /* Matching Quizme's primary color */
    }
    .p-csp {
        text-align:center;
        color:#555;
        margin-bottom:32px;
    }
    .btn-primary-csp {
        background:#e91e63; /* Matching Quizme's primary button color */
        border:none;
        font-weight:600;
        font-size:1.1em;
        border-radius:30px;
        flex:3;
        padding: 10px 20px; /* Added padding for better appearance */
    }
    .form-control-csp {
        border-color: #ddd; /* Subtle border for form controls */
    }
    .plan-display-csp {
        margin-top: 32px;
        padding: 24px;
        background: #f7f7ff; /* Lighter background for the plan box */
        border-radius: 12px;
        text-align:left; /* Align text to left for better readability of a plan */
        font-size:1.0em;
        color:#333;
        white-space: pre-wrap; /* Preserve whitespace and line breaks */
        word-wrap: break-word; /* Break long words */
    }
    .btn-sm-outline-secondary-csp {
        background-color: #fff;
        border: 1px solid #6c757d;
        color: #6c757d;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .btn-sm-outline-danger-csp {
        background-color: #fff;
        border: 1px solid #dc3545;
        color: #dc3545;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
</style>

<div class="container-csp">
    <h2 class="h2-csp">Coach's Sports Practice</h2>
    <p class="p-csp">Generate a plan for practice for any sport that you're coaching!</p>
    <form method="POST" action="/coachsportprac">
        @csrf
        <div style="margin-bottom: 18px;">
            <label for="grade" style="font-weight:500;">Grade level: <span style="color:red">*</span></label>
            <select id="grade" name="grade" class="form-control form-control-csp" required>
                <option value="Pre-K" {{ (old('grade', $grade ?? '') == 'Pre-K') ? 'selected' : '' }}>Pre-K</option>
                <option value="Kindergarten" {{ (old('grade', $grade ?? '') == 'Kindergarten') ? 'selected' : '' }}>Kindergarten</option>
                <option value="1st Grade" {{ (old('grade', $grade ?? '') == '1st Grade') ? 'selected' : '' }}>1st Grade</option>
                <option value="2nd Grade" {{ (old('grade', $grade ?? '') == '2nd Grade') ? 'selected' : '' }}>2nd Grade</option>
                <option value="3rd Grade" {{ (old('grade', $grade ?? '') == '3rd Grade') ? 'selected' : '' }}>3rd Grade</option>
                <option value="4th Grade" {{ (old('grade', $grade ?? '') == '4th Grade') ? 'selected' : '' }}>4th Grade</option>
                <option value="5th Grade" {{ (old('grade', $grade ?? '') == '5th Grade') ? 'selected' : '' }}>5th Grade</option>
                <option value="6th Grade" {{ (old('grade', $grade ?? '') == '6th Grade') ? 'selected' : '' }}>6th Grade</option>
                <option value="7th Grade" {{ (old('grade', $grade ?? '') == '7th Grade') ? 'selected' : '' }}>7th Grade</option>
                <option value="8th Grade" {{ (old('grade', $grade ?? '') == '8th Grade') ? 'selected' : '' }}>8th Grade</option>
                <option value="9th Grade" {{ (old('grade', $grade ?? '') == '9th Grade') ? 'selected' : '' }}>9th Grade</option>
                <option value="10th Grade" {{ (old('grade', $grade ?? '') == '10th Grade') ? 'selected' : '' }}>10th Grade</option>
                <option value="11th Grade" {{ (old('grade', $grade ?? '') == '11th Grade') ? 'selected' : '' }}>11th Grade</option>
                <option value="12th Grade" {{ (old('grade', $grade ?? '') == '12th Grade') ? 'selected' : '' }}>12th Grade</option>
                <option value="University" {{ (old('grade', $grade ?? '') == 'University') ? 'selected' : '' }}>University</option>
                <option value="1st Year College" {{ (old('grade', $grade ?? '') == '1st Year College') ? 'selected' : '' }}>1st Year College</option>
                <option value="2nd Year College" {{ (old('grade', $grade ?? '') == '2nd Year College') ? 'selected' : '' }}>2nd Year College</option>
                <option value="3rd Year College" {{ (old('grade', $grade ?? '') == '3rd Year College') ? 'selected' : '' }}>3rd Year College</option>
                <option value="4th Year College" {{ (old('grade', $grade ?? '') == '4th Year College') ? 'selected' : '' }}>4th Year College</option>
                <option value="Adult" {{ (old('grade', $grade ?? '') == 'Adult') ? 'selected' : '' }}>Adult</option>
                <option value="Professional Staff" {{ (old('grade', $grade ?? '') == 'Professional Staff') ? 'selected' : '' }}>Professional Staff</option>
            </select>
        </div>
        <div style="margin-bottom: 18px;">
            <label for="length" style="font-weight:500;">Length of Practice: <span style="color:red">*</span></label>
            <select id="length" name="length" class="form-control form-control-csp" required>
                <option value="30 mins" {{ (old('length', $length ?? '') == '30 mins') ? 'selected' : '' }}>30 mins</option>
                <option value="1 hour" {{ (old('length', $length ?? '') == '1 hour') ? 'selected' : '' }}>1 hour</option>
                <option value="1.5 hours" {{ (old('length', $length ?? '') == '1.5 hours') ? 'selected' : '' }}>1.5 hours</option>
                <option value="2 hours" {{ (old('length', $length ?? '') == '2 hours') ? 'selected' : '' }}>2 hours</option>
                <option value="More than 2 hours" {{ (old('length', $length ?? '') == 'More than 2 hours') ? 'selected' : '' }}>More than 2 hours</option>
            </select>
        </div>
        <div style="margin-bottom: 18px;">
            <label for="sport" style="font-weight:500;">Sport: <span style="color:red">*</span></label>
            <textarea id="sport" name="sport" class="form-control form-control-csp" rows="2" placeholder="Soccer, Basketball, Cheerleading, Lacrosse, Baseball, Football, etc." required>{{ old('sport', $sport ?? '') }}</textarea>
        </div>
        <div style="margin-bottom: 18px;">
            <label for="customization" style="font-weight:500;">Additional Customization (Optional):</label>
            <textarea id="customization" name="customization" class="form-control form-control-csp" rows="2" placeholder="Include a jogging warmup, include weightlifting, activities to improve agility, focus on passing, etc.">{{ old('customization', $customization ?? '') }}</textarea>
        </div>
        <div style="display:flex; gap:16px; align-items:center; margin-bottom: 24px;">
            <button type="submit" class="btn btn-primary-csp">Generate</button>
        </div>
    </form>
    @if(isset($practicePlan))
        <div class="plan-display-csp">
            <strong>Practice Plan:</strong><br>
            <pre style="white-space: pre-wrap; word-wrap: break-word;">{{ $practicePlanFormatted }}</pre>
            <div style="margin-top: 20px;">
                <form action="{{ route('coachsportprac.download') }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="content" value="{{ $practicePlan }}">
                    <input type="hidden" name="filename" value="sports_practice_plan">
                    <input type="hidden" name="format" value="txt">
                    <button type="submit" class="btn btn-sm-outline-secondary-csp">Save as Text</button>
                </form>
                <form action="{{ route('coachsportprac.download') }}" method="POST" style="display:inline; margin-left: 10px;">
                    @csrf
                    <input type="hidden" name="content" value="{{ $practicePlan }}">
                    <input type="hidden" name="filename" value="sports_practice_plan">
                    <input type="hidden" name="format" value="pdf">
                    <button type="submit" class="btn btn-sm-outline-danger-csp">Save as PDF</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
