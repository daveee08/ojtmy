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
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form method="POST" action="/coachsportprac">
        @csrf
        <input type="hidden" name="input_type" value="sport">
        <div style="margin-bottom: 18px;">
            <label for="grade" style="font-weight:500;">Grade level: <span style="color:red">*</span></label>
            <select name="grade_level" id="grade_level" class="form-control form-control-csp" required>
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
            <select name="length_of_practice" id="length_of_practice" class="form-control form-control-csp" required>
                <option value="30 mins" {{ (old('length', $length ?? '') == '30 mins') ? 'selected' : '' }}>30 mins</option>
                <option value="1 hour" {{ (old('length', $length ?? '') == '1 hour') ? 'selected' : '' }}>1 hour</option>
                <option value="1.5 hours" {{ (old('length', $length ?? '') == '1.5 hours') ? 'selected' : '' }}>1.5 hours</option>
                <option value="2 hours" {{ (old('length', $length ?? '') == '2 hours') ? 'selected' : '' }}>2 hours</option>
                <option value="More than 2 hours" {{ (old('length', $length ?? '') == 'More than 2 hours') ? 'selected' : '' }}>More than 2 hours</option>
            </select>
        </div>
        <div style="margin-bottom: 18px;">
            <label for="sport" style="font-weight:500;">Sport: <span style="color:red">*</span></label>
            <input type="text" name="sport" id="sport" class="form-control form-control-csp" value="{{ old('sport', $sport ?? '') }}" required>
        </div>
        <div style="margin-bottom: 18px;">
            <label for="customization" style="font-weight:500;">Additional Customization (Optional):</label>
            <input type="text" name="additional_customization" id="additional_customization" class="form-control form-control-csp" value="{{ old('customization', $customization ?? '') }}" placeholder="Include a jogging warmup, include weightlifting, activities to improve agility, focus on passing, etc.">
        </div>
        <div style="display:flex; gap:16px; align-items:center; margin-bottom: 24px;">
            <button type="submit" class="btn btn-primary-csp">Generate</button>
        </div>
    </form>
    @if(isset($example))
        <button type="button" class="btn btn-info" onclick="
            document.getElementById('grade_level').value = '{{ $example['grade_level'] }}';
            document.getElementById('length_of_practice').value = '{{ $example['length_of_practice'] }}';
            document.getElementById('sport').value = '{{ $example['sport'] }}';
            document.getElementById('additional_customization').value = '{{ $example['additional_customization'] }}';
        ">Fill Example</button>
    @endif
    @if (!empty($cleanContent))
        <div class="practice-plan-output" style="margin-top: 24px;">
            {!! nl2br(e($cleanContent)) !!}
        </div>
        <div class="btn-group mt-3" role="group">
            <button type="button" class="btn btn-secondary" onclick="downloadTxtInteractive()">Download as TXT</button>
            <button type="button" class="btn btn-primary" onclick="downloadPdfInteractive()">Download as PDF</button>
        </div>
    @endif
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
    <div style="font-size: 0.9em; color: #888; margin-top: 8px;">
        Tip: To choose the file name and location, enable "Ask where to save each file before downloading" in your browser's settings.
    </div>
</div>
@endsection

<script>
document.querySelectorAll('form[action="/coachsportprac/download"]').forEach(function(form) {
    form.addEventListener('submit', function() {
        document.getElementById('download_sport').value = document.getElementById('sport').value;
        document.getElementById('download_grade_level').value = document.getElementById('grade_level').value;
    });
});

function downloadTxt() {
    const content = {!! json_encode($cleanContent) !!};
    let filename = prompt("Enter file name:", "Practice Plan.txt");
    if (!filename) return;
    if (!filename.endsWith('.txt')) filename += '.txt';
    const blob = new Blob([content], {type: "text/plain"});
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
}

function downloadTxtInteractive() {
    const content = @json($cleanContent);
    const sport = @json($currentSport ?? 'Practice');
    const grade = @json($currentGrade ?? 'All Levels');
    let filename = prompt("Enter file name:", `${sport} Practice Plan for ${grade} Level.txt`);
    if (!filename) return;
    if (!filename.toLowerCase().endsWith('.txt')) filename += '.txt';
    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
}

function downloadPdfInteractive() {
    const content = @json($cleanContent);
    const sport = @json($currentSport ?? 'Practice');
    const grade = @json($currentGrade ?? 'All Levels');
    let filename = prompt("Enter file name:", `${sport} Practice Plan for ${grade} Level.pdf`);
    if (!filename) return;
    if (!filename.toLowerCase().endsWith('.pdf')) filename += '.pdf';

    fetch('/coachsportprac/download-pdf', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ content, sport, grade_level: grade })
    })
    .then(response => {
        if (!response.ok) throw new Error('Failed to generate PDF');
        return response.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    })
    .catch(err => alert(err.message));
}
</script>
