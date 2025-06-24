<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class TutorController extends Controller
{
    public function showForm()
    {
        return view('tutor');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'required|string',
            'input_type' => 'required|in:topic,pdf',
            'topic' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
            'add_cont' => 'nullable|string',
        ]);

        // Store grade level once
        if (!Session::has('grade_level')) {
            Session::put('grade_level', $validated['grade_level']);
        }

        // Store user message in chat history
        $chatHistory = Session::get('chat_history', []);
        $newMessage = $validated['topic'] ?? '[PDF Upload]';
        $chatHistory[] = ['role' => 'user', 'content' => $newMessage];

        // Build full history string
        $priorMessages = array_slice($chatHistory, 0, -1);
        $historyText = collect($priorMessages)->pluck('content')->implode("\n");
        $wordCount = str_word_count($historyText);
        $contextSummary = $historyText;

        // Summarize only if past messages are long
        if ($wordCount > 3000) {
            $summaryResponse = Http::timeout(10)->post('http://127.0.0.1:5001/summarize-history', [
                'history' => $historyText,
            ]);
            if ($summaryResponse->successful()) {
                $contextSummary = $summaryResponse->json()['summary'] ?? $historyText;
            }
        }

        // Add additional context if provided
        if (!empty($validated['add_cont'])) {
            $contextSummary .= "\n" . $validated['add_cont'];
        }
        
        $finalTopic = "Prior Conversation Summary:\n" . $contextSummary . "\n\nStudent’s Follow-up:\n" . $newMessage; 

        // Build multipart payload
        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'input_type', 'contents' => $validated['input_type']],
           
            ['name' => 'topic', 'contents' => $finalTopic],

            ['name' => 'add_cont', 'contents' => ''], // cleared
        ];

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

        // Call Python API
        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/tutor', $multipartData);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        $output = $response->json()['output'] ?? 'No output';

        // Add assistant response to session history
        $chatHistory[] = ['role' => 'assistant', 'content' => $output];
        Session::put('chat_history', $chatHistory);

        return view('tutor', [
            'response' => $output,
            'history' => $chatHistory
        ]);
    }
}
