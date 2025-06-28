<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CK Book Suggestion Chatbot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .whitespace-pre-line {
            white-space: pre-line;
            word-wrap: break-word;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans text-gray-800 min-h-screen flex flex-col items-center justify-center px-4 py-10">

    <div class="w-full max-w-2xl bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold text-center mb-6 text-indigo-700">CK Book Suggestion Chatbot</h1>

        {{-- Bot Response Display --}}
        <div id="chat-response-container" class="hidden bg-blue-100 border border-blue-300 text-blue-800 px-4 py-3 rounded mb-6">
            <strong class="block font-medium">Bot's Suggestion:</strong>
            <p id="chat-response" class="mt-1 whitespace-pre-line"></p>
            <button id="copy-suggestion-button" class="mt-2 bg-indigo-500 text-white text-xs px-3 py-1 rounded hover:bg-indigo-600 transition">Copy Suggestion</button>
            <button id="save-suggestion-button" class="mt-2 ml-2 bg-green-500 text-white text-xs px-3 py-1 rounded hover:bg-green-600 transition">Save as Text File</button>
        </div>

        {{-- Toggle Prompt Button --}}
        <div class="text-right mb-4">
            <button id="toggle-prompt-button" class="text-indigo-600 hover:underline text-sm">Hide Prompt</button>
        </div>

        {{-- Input Form --}}
        <form id="chat-form" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="grade-level" class="block text-sm font-medium text-gray-700">Grade level:</label>
                <select name="grade_level" id="grade-level" required
                    class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="" disabled selected hidden>Select a grade level</option>
                    <option value="preschool">Preschool</option>
                    <option value="kindergarten">Kindergarten</option>
                    <option value="1st grade">1st Grade</option>
                    <option value="2nd grade">2nd Grade</option>
                    <option value="3rd grade">3rd Grade</option>
                    <option value="4th grade">4th Grade</option>
                    <option value="5th grade">5th Grade</option>
                    <option value="6th grade">6th Grade</option>
                    <option value="7th grade">7th Grade</option>
                    <option value="8th grade">8th Grade</option>
                    <option value="9th grade">9th Grade (Freshman High School)</option>
                    <option value="10th grade">10th Grade (Sophomore High School)</option>
                    <option value="11th grade">11th Grade (Junior High School)</option>
                    <option value="12th grade">12th Grade (Senior High School)</option>
                    <option value="college level">College Level</option>
                    <option value="university level">University Level</option>
                    <option value="adult reading level">Adult Reading Level</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="user-interests" class="block text-sm font-medium text-gray-700">What are your interests:</label>
                <textarea name="interests" id="user-interests" rows="4"
                    class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                    placeholder="Type your interests, e.g., 'fantasy adventures', 'mystery with detectives', 'sci-fi with robots'" required></textarea>
            </div>

            {{-- NEW: PDF Upload input within the main form --}}
            <div class="mb-4">
                <label for="pdf-file-input" class="block text-sm font-medium text-gray-700 mb-2">Upload a PDF Document to inform suggestions (optional):</label>
                <input type="file" id="pdf-file-input" name="pdf_file" accept="application/pdf"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                    file:rounded-md file:border-0 file:text-sm file:font-semibold
                    file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"/>
                <div id="pdf-upload-status" class="mt-3 text-sm hidden"></div>
            </div>

            <div class="pt-4 text-center">
                <button type="submit" id="send-button"
                    class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 transition">
                    Generate Suggestion
                </button>
            </div>
        </form>

        {{-- Thinking Indicator for main form --}}
        <div id="thinking-indicator" class="text-center mt-4 text-gray-500 hidden">
            Thinking...
        </div>

        <div class="text-center mt-8">
            <a href="{{ url('/') }}" class="text-indigo-600 hover:underline">← Back to Home</a>
        </div>
    </div>

    <script>
        const userInterests = document.getElementById('user-interests');
        const pdfFileInput = document.getElementById('pdf-file-input');
        const pdfUploadStatus = document.getElementById('pdf-upload-status');

        // Set initial required state
        userInterests.required = true;

        // Toggle required attribute based on PDF input
        pdfFileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                userInterests.required = false;
                pdfUploadStatus.classList.remove('hidden', 'text-red-800', 'text-green-800');
                pdfUploadStatus.innerText = 'PDF selected. Interests are now optional.';
            } else {
                userInterests.required = true;
                pdfUploadStatus.classList.add('hidden');
            }
        });

        document.getElementById('chat-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const interests = userInterests.value.trim();
            const gradeLevel = document.getElementById('grade-level').value;
            const pdfFile = pdfFileInput.files[0];

            // Client-side validation: if no PDF and no interests, prevent submission
            if (!pdfFile && !interests) {
                pdfUploadStatus.classList.remove('hidden', 'text-green-800');
                pdfUploadStatus.classList.add('text-red-800');
                pdfUploadStatus.innerText = 'Please enter your interests or upload a PDF file.';
                return;
            }

            const responseDiv = document.getElementById('chat-response');
            const responseContainer = document.getElementById('chat-response-container');
            const thinkingIndicator = document.getElementById('thinking-indicator');
            
            responseDiv.innerHTML = '';
            responseContainer.classList.add('hidden');
            responseContainer.classList.remove('bg-red-100', 'border-red-300', 'text-red-800');
            responseContainer.classList.add('bg-blue-100', 'border-blue-300', 'text-blue-800');
            thinkingIndicator.classList.remove('hidden');
            pdfUploadStatus.classList.add('hidden'); // Hide PDF status on new submission

            const formData = new FormData();
            formData.append('interests', interests);
            formData.append('grade_level', gradeLevel);
            if (pdfFile) {
                formData.append('pdf_file', pdfFile);
            }

            try {
                const res = await fetch("/suggest", {
                    method: "POST",
                    // No 'Content-Type' header needed for FormData
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await res.json();

                if (!res.ok) { // Check res.ok for HTTP errors
                    throw new Error(data.detail || "Something went wrong");
                }

                responseDiv.innerHTML = data.suggestion; // FastAPI returns 'suggestion'
                responseContainer.classList.remove('hidden');

            } catch (err) {
                responseDiv.innerHTML = `⚠️ ${err.message}`;
                responseContainer.classList.remove('hidden');
                responseContainer.classList.remove('bg-blue-100', 'border-blue-300', 'text-blue-800');
                responseContainer.classList.add('bg-red-100', 'border-red-300', 'text-red-800');
            } finally {
                thinkingIndicator.classList.add('hidden');
            }
        });

        // NEW: Copy to Clipboard functionality
        document.getElementById('copy-suggestion-button').addEventListener('click', async function() {
            const suggestionText = document.getElementById('chat-response').innerText;
            try {
                await navigator.clipboard.writeText(suggestionText);
                alert('Suggestion copied to clipboard!');
            } catch (err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy suggestion. Please copy manually.');
            }
        });

        // NEW: Save to Text File functionality
        document.getElementById('save-suggestion-button').addEventListener('click', async function() {
            const suggestionText = document.getElementById('chat-response').innerText;
            const defaultFilename = 'book_suggestions.txt';

            try {
                // Feature detection for showSaveFilePicker
                if ('showSaveFilePicker' in window) {
                    const options = {
                        suggestedName: defaultFilename,
                        types: [{
                            description: 'Text Files',
                            accept: {
                                'text/plain': ['.txt'],
                            },
                        }],
                    };
                    const fileHandle = await window.showSaveFilePicker(options);
                    const writableStream = await fileHandle.createWritable();
                    await writableStream.write(suggestionText);
                    await writableStream.close();
                    alert('Suggestions saved successfully!');
                } else {
                    // Fallback for browsers that do not support showSaveFilePicker
                    const blob = new Blob([suggestionText], { type: 'text/plain' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = defaultFilename; // Browser will handle (1), (2) etc.
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    alert('Suggestions downloaded as ' + defaultFilename + '. To choose location and filename, please use a modern browser.');
                }
            } catch (err) {
                if (err.name === 'AbortError') {
                    // User cancelled the save dialog
                    console.log('Save operation aborted by the user.');
                } else {
                    console.error('Failed to save file: ', err);
                    alert('Failed to save suggestion: ' + err.message + '. Please try again or copy manually.');
                }
            }
        });

        // NEW: Toggle Prompt Visibility functionality
        document.getElementById('toggle-prompt-button').addEventListener('click', function() {
            const chatForm = document.getElementById('chat-form');
            const toggleButton = document.getElementById('toggle-prompt-button');

            if (chatForm.classList.contains('hidden')) {
                chatForm.classList.remove('hidden');
                toggleButton.innerText = 'Hide Prompt';
            } else {
                chatForm.classList.add('hidden');
                toggleButton.innerText = 'Show Prompt';
            }
        });
    </script>

</body>

</html>
