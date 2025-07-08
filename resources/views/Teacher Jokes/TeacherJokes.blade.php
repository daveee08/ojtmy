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
        cursor: pointer;
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
    /* Infinity Loader CSS (reused from Tongue Twister) */
    .loading-tj {
        text-align: center;
        margin-top: 20px;
    }
    .infinity-loader {
        display: inline-block;
        position: relative;
        width: 80px;
        height: 80px;
        transform: rotate(45deg);
    }
    .infinity-loader div {
        box-sizing: border-box;
        display: block;
        position: absolute;
        width: 64px;
        height: 64px;
        margin: 8px;
        border: 8px solid #e91e63;
        border-radius: 50%;
        animation: infinity-loader 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        border-color: #e91e63 transparent transparent transparent;
    }
    .infinity-loader div:nth-child(1) {
        animation-delay: -0.45s;
    }
    .infinity-loader div:nth-child(2) {
        animation-delay: -0.3s;
    }
    .infinity-loader div:nth-child(3) {
        animation-delay: -0.15s;
    }
    @keyframes infinity-loader {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>

<div class="container-tj">
    <h2 class="h2-tj">Teacher Jokes</h2>
    <p class="p-tj">Generate jokes for your class based on any topic.</p>
    <form id="joke-form">
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
            <button type="submit" id="generate-button-tj" class="btn btn-primary-tj">Generate</button>
        </div>
    </form>

    <div id="loading-indicator-tj" class="loading-tj" style="display:none;">
        <div class="infinity-loader"><div></div><div></div><div></div><div></div></div>
    </div>

    @if(isset($joke) && $joke)
        <div id="joke-output-tj" class="joke-display-tj">
            <p style="font-weight: 600;">Joke for {{ $grade ?? 'N/A' }} Grade Level:</p>
            @if($customization)
                <p style="font-weight: 600; margin-top: 5px;">Customization: {{ $customization }}</p>
            @endif
            <br>
            <em>{{ $joke }}</em>
        </div>
    @else
        <div id="joke-output-tj" class="joke-display-tj" style="display:none;">
            <p style="font-weight: 600;"></p>
            <p style="font-weight: 600; margin-top: 5px;"></p>
            <br>
            <em></em>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('joke-form');
        const generateButton = document.getElementById('generate-button-tj');
        const loadingIndicator = document.getElementById('loading-indicator-tj');
        const jokeOutputDiv = document.getElementById('joke-output-tj');
        const outputHeading = jokeOutputDiv.querySelector('p:nth-child(1)'); // Select first paragraph for Grade
        const outputCustomization = jokeOutputDiv.querySelector('p:nth-child(2)'); // Select second paragraph for Customization
        const outputContent = jokeOutputDiv.querySelector('em');

        form.addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevent default form submission

            // Get form data
            const grade = document.getElementById('grade').value;
            const customization = document.getElementById('customization').value;
            const csrfToken = document.querySelector('input[name="_token"]').value;

            // Show loading indicator, hide previous output
            loadingIndicator.style.display = 'block';
            jokeOutputDiv.style.display = 'none';
            outputHeading.textContent = '';
            outputCustomization.textContent = ''; // Clear customization text
            outputContent.textContent = '';
            generateButton.disabled = true; // Disable button during loading
            generateButton.textContent = ''; // Make the text disappear

            try {
                const response = await fetch('/teacherjokes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ grade: grade, customization: customization })
                });

                const data = await response.json();

                if (response.ok) {
                    // Update output div with new data
                    outputHeading.textContent = `Joke for ${grade} Grade Level:`;
                    if (customization) {
                        outputCustomization.textContent = `Customization: ${customization}`;
                    } else {
                        outputCustomization.textContent = ''; // Clear if no customization
                    }
                    outputContent.textContent = data.joke; // assuming the key is 'joke'
                    jokeOutputDiv.style.display = 'block';
                } else {
                    // Handle server-side errors
                    let errorMessage = 'An unknown error occurred.';
                    if (data && data.message) {
                        errorMessage = data.message;
                    } else if (data && data.error) {
                        errorMessage = data.error;
                    }
                    outputHeading.textContent = 'Error:';
                    outputCustomization.textContent = ''; // Clear customization on error
                    outputContent.textContent = errorMessage;
                    jokeOutputDiv.style.display = 'block';
                }

            } catch (error) {
                // Handle network or parsing errors
                outputHeading.textContent = 'Network Error:';
                outputCustomization.textContent = ''; // Clear customization on error
                outputContent.textContent = 'Could not connect to the server or parse response. Please check your connection and try again.';
                jokeOutputDiv.style.display = 'block';
                console.error('Fetch error:', error);
            } finally {
                loadingIndicator.style.display = 'none';
                generateButton.disabled = false; // Re-enable button
                generateButton.textContent = 'Generate'; // Restore button text
            }
        });
    });
</script>

@endsection
