@extends('layouts.app')

@section('content')
<style>
    body {
        background: linear-gradient(to right, #ffe6ec, #ffffff);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #2c2c2c;
    }
    .container-tj {
        background: #ffffff;
        max-width: 700px;
        padding: 32px;
        border-radius: 18px;
        box-shadow: 0 2px 16px rgba(80, 60, 200, 0.08);
        margin: 40px auto;
    }
    .h2-tj {
        text-align:center;
        font-weight:600;
        margin-bottom:8px;
        color: #e91e63; /* Matching Quizme's primary color */
    }
    .p-tj {
        text-align:center;
        color:#555;
        margin-bottom:32px;
    }
    .btn-primary-tj {
        background:#e91e63; /* Matching Quizme's primary button color */
        border:none;
        font-weight:600;
        font-size:1.1em;
        border-radius:30px;
        flex:3;
        padding: 10px 20px; /* Added padding for better appearance */
    }
    .form-control-tj {
        border-color: #ddd; /* Subtle border for form controls */
    }
    .joke-display-tj {
        margin-top: 32px;
        padding: 24px;
        background: #f7f7ff; /* Lighter background for the joke box */
        border-radius: 12px;
        text-align:center;
        font-size:1.2em;
        color:#333;
    }
</style>

<div class="container-tj">
    <h2 class="h2-tj">Teacher Jokes</h2>
    <p class="p-tj">Generate jokes for your class based on any topic.</p>
    <form method="POST" action="/teacherjokes">
        @csrf
        <div style="margin-bottom: 18px;">
            <label for="grade" style="font-weight:500;">Grade level: <span style="color:red">*</span></label>
            <select id="grade" name="grade" class="form-control form-control-tj" required>
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
        <div style="margin-bottom: 18px;">
            <label for="customization" style="font-weight:500;">Additional Customization (Optional):</label>
            <textarea id="customization" name="customization" class="form-control form-control-tj" rows="2" placeholder="Make it about mitosis" >{{ old('customization', $customization ?? '') }}</textarea>
        </div>
        <div style="display:flex; gap:16px; align-items:center; margin-bottom: 24px;">
            <button type="submit" class="btn btn-primary-tj">Generate</button>
        </div>
    </form>
    @if(isset($joke))
        <div class="joke-display-tj">
            <strong>Joke:</strong><br>
            <em>{{ $joke }}</em>
        </div>
    @endif
</div>
@endsection
