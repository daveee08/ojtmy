<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\ChatHistory;
// use App\Models\ConversationHistory;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TutorController extends Controller
{
    public function showForm()
    {

        $history = Message::where('user_id', Auth::id())
            ->where('message_id', 1) // Only top-level messages
            ->orderBy('created_at')
            ->get();

        Log::info('Loading chat history for user:', ['user_id' => Auth::id(), 'count' => $history->count()]);

        $chatHistory = $history->map(function ($item) {
            return [
                'role' => $item->sender,
                'content' => $item->topic
            ];
        })->toArray();

        Log::info('Chat history loaded:', ['count' => count($chatHistory)]);

        return view('Conceptual Understanding.tutor', [
            'history' => $chatHistory
        ]);
    }


    public function processForm(Request $request)
    {
        set_time_limit(0);

        // Log headers to debug AJAX issue
        Log::info('Headers:', $request->headers->all());
        Log::info('Is AJAX: ' . ($request->ajax() ? 'yes' : 'no'));

        $validated = $request->validate([
            'grade_level' => 'nullable|string',
            'input_type' => 'required|in:topic,pdf',
            'topic' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
            'add_cont' => 'nullable|string',
        ]);

        // Store grade level in session if not yet stored
        // if (!session()->has('grade_level')) {
        //     session(['grade_level' => $validated['grade_level']]);
        // }
        // Check the latest sess_grade_level from ConversationHistory
        $latestGradeLevel = ConversationHistory::where('user_id', Auth::id())
            ->where('agent', 'tutor')
            ->whereNotNull('sess_grade_level')
            ->orderByDesc('created_at')
            ->value('sess_grade_level');

        // Fallback to validated input or user model if none found
        $gradeLevel = $validated['grade_level'] ?? $latestGradeLevel ?? Auth::user()->grade_level;

        Log::info('Grade level selected:', ['grade_level' => $gradeLevel]);

        if (!$gradeLevel) {
            return back()->withErrors(['grade_level' => 'Grade level is missing. Please re-login or provide it.']);
        }
        // Fetch conversation history
        $history = ConversationHistory::where('user_id', Auth::id())
            ->where('agent', 'tutor')
            ->orderBy('created_at')
            ->get();

        $chatHistory = $history->map(function ($item) {
            return [
                'role' => $item->sender,
                'content' => $item->message
            ];
        })->toArray();

        $mode = count($chatHistory) >= 1 ? 'chat' : 'manual';
        // New user message
        $newMessage = $validated['topic'] ?? '[PDF Upload]';
        if (!empty($validated['add_cont'])) {
            $newMessage .= "\n\nAdditional Context:\n" . $validated['add_cont'];
        }
        $chatHistory[] = ['role' => 'user', 'content' => $newMessage];

        // Store user message
        ConversationHistory::create([
            'user_id' => Auth::id(),
            'agent' => 'tutor',
            'message' => $newMessage,
            'sender' => 'user',
            'sess_grade_level' => $gradeLevel
        ]);
        

        // Build context from history
        $priorMessages = array_slice($chatHistory, 0, -1);
        $historyText = collect($priorMessages)->pluck('content')->implode("\n");

        // Optional summary
        $contextSummary = $historyText;
        if (str_word_count($historyText) > 24000) {
            $summaryResponse = Http::timeout(10)->post('http://127.0.0.1:5001/summarize-history', [
                'history' => $historyText,
            ]);
            if ($summaryResponse->successful()) {
                $contextSummary = $summaryResponse->json()['summary'] ?? $historyText;
            }
        }

        if (!empty($validated['add_cont'])) {
            $contextSummary .= "\n" . $validated['add_cont'];
        }

        $finalTopic = "Prior Conversation Summary:\n" . $contextSummary . "\n\nStudent’s Follow-up:\n" . $newMessage;

        // $mode = count($chatHistory) === 1 ? 'chat' : 'manual';
        Log::info('Mode determined:', ['mode' => $mode]);

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $gradeLevel],
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'topic', 'contents' => $finalTopic],
            ['name' => 'add_cont', 'contents' => ''],
            ['name' => 'mode', 'contents' => $mode],
            ['name' => 'user_id', 'contents' => Auth::id()],
            ['name' => 'history', 'contents' => json_encode($chatHistory)],
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

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/tutor', $multipartData);

        // Handle failure
        if ($response->failed()) {
            $errorMessage = 'Python API failed: ' . $response->body();
            Log::error($errorMessage);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => 'Python API failed',
                    'details' => $response->body()
                ], 500);
            }

            return back()->withErrors(['error' => $errorMessage]);
        }

        $output = $response->json()['output'] ?? 'No output';

        // Store agent response
        ConversationHistory::create([
            'user_id' => Auth::id(),
            'agent' => 'tutor',
            'message' => $output,
            'sender' => 'agent',
            'sess_grade_level' => $gradeLevel
        ]);

        // Reload updated history
        $latestHistory = ConversationHistory::where('user_id', Auth::id())
            ->where('agent', 'tutor')
            ->orderBy('created_at')
            ->get();

        $chatHistory = $latestHistory->map(function ($item) {
            return [
                'role' => $item->sender,
                'content' => $item->message
            ];
        })->toArray();

        // Return JSON if requested
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => $output,
                'history' => $chatHistory
            ]);
        }

        return view('tutor', [
            'response' => $output,
            'history' => $chatHistory
        ]);
    }

    public function clearHistory(Request $request)
    {
        ConversationHistory::where('user_id', Auth::id())
            ->where('agent', 'tutor')
            ->delete();

        session()->forget('grade_level');

        return redirect()->back()->with('status', 'Conversation history cleared.');
    }
}
