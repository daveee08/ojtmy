<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\ChatHistory; // Import the model for chat history

class TutorController extends Controller
{
    public function showForm()
    {
        return view('tutor');
    }

    // TutorController.php

    public function processForm(Request $request)
    {
        set_time_limit(0);

        // Validate inputs
        $validated = $request->validate([
            'grade_level' => 'required|string',
            'input_type' => 'required|in:topic,pdf',
            'topic' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
            'add_cont' => 'nullable|string',
        ]);

        // Store grade level in session if not yet stored
        if (!Session::has('grade_level')) {
            Session::put('grade_level', $validated['grade_level']);
        }

        // Initialize or retrieve chat history
        $chatHistory = Session::get('chat_history', []);

        // New user message
        $newMessage = $validated['topic'] ?? '[PDF Upload]';
        $chatHistory[] = ['role' => 'user', 'content' => $newMessage];

        // Save user message in DB
        ChatHistory::create([
            'session_id' => session()->getId(),
            'role' => 'user',
            'message' => $newMessage
        ]);

        // Extract prior messages (excluding latest)
        $priorMessages = array_slice($chatHistory, 0, -1);
        $historyText = collect($priorMessages)->pluck('content')->implode("\n");

        // Summarize history if it's long
        $contextSummary = $historyText;
        if (str_word_count($historyText) > 3000) {
            $summaryResponse = Http::timeout(10)->post('http://127.0.0.1:5001/summarize-history', [
                'history' => $historyText,
            ]);
            if ($summaryResponse->successful()) {
                $contextSummary = $summaryResponse->json()['summary'] ?? $historyText;
            }
        }

        // Append additional context
        if (!empty($validated['add_cont'])) {
            $contextSummary .= "\n" . $validated['add_cont'];
        }

        // Final input to tutor agent
        $finalTopic = "Prior Conversation Summary:\n" . $contextSummary . "\n\nStudent’s Follow-up:\n" . $newMessage;

        // Decide prompt mode
        $mode = count($chatHistory) === 1 ? 'chat' : 'manual';  // First message = use chat prompt

        // Prepare payload for Python
        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'topic', 'contents' => $finalTopic],
            ['name' => 'add_cont', 'contents' => ''],
            ['name' => 'mode', 'contents' => $mode], // ✅ Explicitly pass the mode
        ];

        // Attach file if uploaded
        if ($request->hasFile('pdf_file')) {
            $pdf = $request->file('pdf_file');
            $multipartData[] = [
                'name'     => 'pdf_file',
                'contents' => fopen($pdf->getPathname(), 'r'),
                'filename' => $pdf->getClientOriginalName(),
                'headers'  => [
                    'Content-Type' => $pdf->getMimeType()
                ],
            ];
        }

        // Call Python backend
        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/tutor', $multipartData);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        $output = $response->json()['output'] ?? 'No output';

        // Store assistant response
        $chatHistory[] = ['role' => 'assistant', 'content' => $output];
        ChatHistory::create([
            'session_id' => session()->getId(),
            'role' => 'assistant',
            'message' => $output
        ]);

        // Save chat history in session
        Session::put('chat_history', $chatHistory);

        return view('tutor', [
            'response' => $output,
            'history' => $chatHistory
        ]);
    }

}