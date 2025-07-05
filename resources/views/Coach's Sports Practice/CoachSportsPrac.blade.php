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
        padding: 1.5rem 1rem; /* Adjusted for mobile-first */
        border-radius: 18px;
        box-shadow: 0 2px 16px rgba(80, 60, 200, 0.08);
        margin: 1.5rem auto; /* Adjusted for mobile-first */
    }
    .h2-csp {
        text-align:center;
        font-weight:600;
        margin-bottom: 0.5rem; /* Adjusted margin */
        color: #e91e63;
        font-size: 1.75rem; /* Mobile-first heading size */
    }
    .p-csp {
        text-align:center;
        color:#555;
        margin-bottom: 1.5rem; /* Adjusted margin */
        font-size: 0.9rem;
    }
    .btn-primary-csp {
        background:#e91e63;
        border:none;
        font-weight:600;
        font-size:1rem; /* Adjusted font size */
        border-radius:30px;
        width: 100%; /* Full width for mobile */
        padding: 0.75rem 1.5rem; /* Increased padding for better touch target */
        margin-bottom: 0.5rem; /* Space if buttons stack */
    }
    .form-control-csp {
        border-color: #ddd;
        min-height: 48px; /* Ensure touch friendliness */
        font-size: 1rem; /* Consistent font size */
    }
    .plan-display-csp {
        margin-top: 1.5rem; /* Adjusted margin */
        padding: 1.5rem; /* Adjusted padding */
        background: #f7f7ff;
        border-radius: 12px;
        text-align:left;
        font-size:1em;
        color:#333;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .btn-sm-outline-secondary-csp,
    .btn-sm-outline-danger-csp {
        background-color: #fff;
        border: 1px solid #6c757d;
        color: #6c757d;
        padding: 0.75rem 1rem; /* Adjusted padding for better touch target */
        font-size: 0.9rem; /* Adjusted font size */
        line-height: 1.5;
        border-radius: 0.2rem;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        width: 100%; /* Full width for mobile */
        margin-bottom: 0.5rem; /* Space if buttons stack */
    }
    .btn-sm-outline-danger-csp {
        border-color: #dc3545;
        color: #dc3545;
    }
</style>

<div class="container-csp container-md p-md-5 mt-md-4">
    <h2 class="h2-csp fs-md-4 mb-md-2">Coach's Sports Practice</h2>
    <p class="p-csp mb-md-4 fs-md-5">Generate a plan for practice for any sport that you're coaching!</p>
    <form method="POST" action="/coachsportprac">
        @csrf
        <div class="mb-3">
            <label for="grade" class="form-label fw-semibold">Grade level: <span class="text-danger">*</span></label>
            <select id="grade" name="grade" class="form-select form-control-csp" required>
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
        <div class="mb-3">
            <label for="length" class="form-label fw-semibold">Length of Practice: <span class="text-danger">*</span></label>
            <select id="length" name="length" class="form-select form-control-csp" required>
                <option value="30 mins" {{ (old('length', $length ?? '') == '30 mins') ? 'selected' : '' }}>30 mins</option>
                <option value="1 hour" {{ (old('length', $length ?? '') == '1 hour') ? 'selected' : '' }}>1 hour</option>
                <option value="1.5 hours" {{ (old('length', $length ?? '') == '1.5 hours') ? 'selected' : '' }}>1.5 hours</option>
                <option value="2 hours" {{ (old('length', $length ?? '') == '2 hours') ? 'selected' : '' }}>2 hours</option>
                <option value="More than 2 hours" {{ (old('length', $length ?? '') == 'More than 2 hours') ? 'selected' : '' }}>More than 2 hours</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="sport" class="form-label fw-semibold">Sport: <span class="text-danger">*</span></label>
            <textarea id="sport" name="sport" class="form-control form-control-csp" rows="2" placeholder="Soccer, Basketball, Cheerleading, Lacrosse, Baseball, Football, etc." required>{{ old('sport', $sport ?? '') }}</textarea>
        </div>
        <div class="mb-3">
            <label for="customization" class="form-label fw-semibold">Additional Customization (Optional):</label>
            <textarea id="customization" name="customization" class="form-control form-control-csp" rows="2" placeholder="Include a jogging warmup, include weightlifting, activities to improve agility, focus on passing, etc.">{{ old('customization', $customization ?? '') }}</textarea>
        </div>
        <div class="d-grid d-md-block gap-2 mb-4">
            <button type="submit" class="btn btn-primary-csp">Generate</button>
        </div>
    </form>
    @if(isset($practicePlan))
        <div class="plan-display-csp mt-md-4 p-md-4 fs-md-5">
            <strong class="d-block mb-2">Practice Plan:</strong>
            <pre class="mb-3">{{ $practicePlanFormatted }}</pre>
            <div class="d-grid d-md-flex gap-2 mt-3">
                <form action="{{ route('coachsportprac.download') }}" method="POST" class="download-form d-grid d-md-inline-block">
                    @csrf
                    <input type="hidden" name="content" value="{{ $practicePlan }}">
                    <input type="hidden" name="filename" value="sports_practice_plan">
                    <input type="hidden" name="format" value="txt">
                    <button type="submit" class="btn btn-sm-outline-secondary-csp">Save as Text</button>
                </form>
                <form action="{{ route('coachsportprac.download') }}" method="POST" class="download-form d-grid d-md-inline-block ms-md-2">
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
