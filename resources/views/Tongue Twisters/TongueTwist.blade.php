@extends('layouts.app')

@section('content')
<style>
    body {
        background: linear-gradient(to right, #ffe6ec, #ffffff);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #2c2c2c;
    }
    .container-tt {
        background: #ffffff;
        max-width: 700px;
        padding: 1.5rem 1rem; /* Adjusted for mobile-first */
        border-radius: 18px;
        box-shadow: 0 2px 16px rgba(80, 60, 200, 0.08);
        margin: 1.5rem auto; /* Adjusted for mobile-first */
    }
    .h2-tt {
        text-align:center;
        font-weight:600;
        margin-bottom: 0.5rem; /* Adjusted margin */
        color: #e91e63;
        font-size: 1.75rem; /* Mobile-first heading size */
    }
    .p-tt {
        text-align:center;
        color:#555;
        margin-bottom: 1.5rem; /* Adjusted margin */
        font-size: 0.9rem;
    }
    .btn-primary-tt {
        background:#e91e63;
        border:none;
        font-weight:600;
        font-size:1rem; /* Adjusted font size */
        border-radius:30px;
        width: 100%; /* Full width for mobile */
        padding: 0.75rem 1.5rem; /* Increased padding for better touch target */
        cursor: pointer;
        margin-bottom: 0.5rem; /* Space if buttons stack */
    }
    .form-control-tt {
        border-color: #ddd;
        min-height: 48px; /* Ensure touch friendliness */
        font-size: 1rem; /* Consistent font size */
    }
    .tt-display-tt {
        margin-top: 1.5rem; /* Adjusted margin */
        padding: 1.5rem; /* Adjusted padding */
        background: #f7f7ff;
        border-radius: 12px;
        text-align:center;
        font-size:1.1em;
        color:#333;
    }
    .loading-tt {
        text-align: center;
        margin-top: 1rem;
        font-size: 1em; /* Adjusted font size */
        color: #e91e63;
        font-weight: 500;
    }
    /* Infinity Loader CSS */
    .infinity-loader {
        display: inline-block;
        position: relative;
        width: 60px; /* Smaller for mobile */
        height: 60px; /* Smaller for mobile */
        transform: rotate(45deg);
    }
    .infinity-loader div {
        box-sizing: border-box;
        display: block;
        position: absolute;
        width: 48px; /* Smaller for mobile */
        height: 48px; /* Smaller for mobile */
        margin: 6px; /* Adjusted margin */
        border: 6px solid #e91e63; /* Adjusted border */
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

    /* Media query for larger screens (e.g., tablets and desktops) */
    @media (min-width: 768px) {
        .container-tt {
            padding: 32px; /* Restore original padding */
            margin: 40px auto; /* Restore original margin */
        }
        .h2-tt {
            font-size: 2rem; /* Adjust for desktop */
            margin-bottom: 8px;
        }
        .p-tt {
            margin-bottom: 32px;
            font-size: 1em;
        }
        .btn-primary-tt {
            width: auto; /* Restore auto width */
            padding: 10px 20px; /* Restore original padding */
            margin-bottom: 0; /* Remove bottom margin */
        }
        .tt-display-tt {
            margin-top: 32px;
            padding: 24px;
            font-size: 1.2em;
        }
        .loading-tt {
            margin-top: 20px;
            font-size: 1.1em;
        }
        .infinity-loader {
            width: 80px;
            height: 80px;
        }
        .infinity-loader div {
            width: 64px;
            height: 64px;
            margin: 8px;
            border: 8px solid #e91e63;
        }
    }
</style>

<div class="container-tt">
    <h2 class="h2-tt">Tongue Twisters</h2>
    <p class="p-tt">Create challenging tongue twisters to say out loud.</p>
    <form id="tongue-twister-form">
        @csrf
        <div style="margin-bottom: 18px;">
            <label for="grade" style="font-weight:500;">Grade level: <span style="color:red">*</span></label>
            <select id="grade" name="grade" class="form-control form-control-tt" required>
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
            <label for="topic" style="font-weight:500;">Topic for Tongue Twister: <span style="color:red">*</span></label>
            <textarea id="topic" name="topic" class="form-control form-control-tt" rows="2" placeholder="jungle creatures, fairy tales, high school prom, field day, mitosis" required>{{ old('topic', $topic ?? '') }}</textarea>
        </div>
        <div style="display:flex; gap:16px; align-items:center; margin-bottom: 24px;">
            <button type="submit" id="generate-button" class="btn btn-primary-tt">Generate</button>
        </div>
    </form>

    <div id="loading-indicator" class="loading-tt" style="display:none;">
        <div class="infinity-loader"><div></div><div></div><div></div><div></div></div>
    </div>

    @if(isset($tongueTwister) && $tongueTwister)
        <div id="tongue-twister-output" class="tt-display-tt">
            <p style="font-weight: 600;">{{ ucfirst($topic) }} Tongue Twister:</p>
            <em>{{ $tongueTwister }}</em>
        </div>
    @else
        <div id="tongue-twister-output" class="tt-display-tt" style="display:none;">
            <p style="font-weight: 600;"></p>
            <em></em>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('tongue-twister-form');
        const generateButton = document.getElementById('generate-button');
        const loadingIndicator = document.getElementById('loading-indicator');
        const tongueTwisterOutputDiv = document.getElementById('tongue-twister-output');
        const outputHeading = tongueTwisterOutputDiv.querySelector('p');
        const outputContent = tongueTwisterOutputDiv.querySelector('em');

        form.addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevent default form submission

            // Get form data
            const topic = document.getElementById('topic').value;
            const grade = document.getElementById('grade').value;
            const csrfToken = document.querySelector('input[name="_token"]').value;

            // Show loading indicator, hide previous output
            loadingIndicator.style.display = 'block';
            tongueTwisterOutputDiv.style.display = 'none';
            outputHeading.textContent = '';
            outputContent.textContent = '';
            generateButton.disabled = true; // Disable button during loading
            generateButton.textContent = ''; // Make the text disappear

            try {
                const response = await fetch('/tonguetwister', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ topic: topic, grade: grade })
                });

                const data = await response.json();

                if (response.ok) {
                    // Update output div with new data
                    const topicCapitalized = topic.charAt(0).toUpperCase() + topic.slice(1);
                    outputHeading.textContent = `${topicCapitalized} Tongue Twister:`;
                    outputContent.textContent = data.tongue_twister; // assuming the key is 'tongue_twister'
                    tongueTwisterOutputDiv.style.display = 'block';
                } else {
                    // Handle server-side errors
                    let errorMessage = 'An unknown error occurred.';
                    if (data && data.message) {
                        errorMessage = data.message;
                    } else if (data && data.error) {
                        errorMessage = data.error;
                    }
                    outputHeading.textContent = 'Error:';
                    outputContent.textContent = errorMessage;
                    tongueTwisterOutputDiv.style.display = 'block';
                }

            } catch (error) {
                // Handle network or parsing errors
                outputHeading.textContent = 'Network Error:';
                outputContent.textContent = 'Could not connect to the server or parse response. Please check your connection and try again.';
                tongueTwisterOutputDiv.style.display = 'block';
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
