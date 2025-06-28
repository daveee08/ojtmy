<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session; 
use App\Models\ConversationHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class StepTutorController extends Controller
{
    public function showForm()
    {
        $history = ConversationHistory::where('user_id', Auth::id())
            ->where('agent', 'step-tutor')
            ->orderBy('created_at')
            ->get();

        $chatHistory = $history->map(function ($item) {
            return [
                'role' => $item->sender,
                'content' => $item->message
            ];
        })->toArray();

        return view('step-tutor', [
            'history' => $chatHistory
        ]);
    }

    // Add at top

// public function processForm(Request $request)
// {
//     set_time_limit(0);

//     $validated = $request->validate([
//         'grade_level' => 'required|string',
//         'topic' => 'required|string',
//     ]);

//     if (!Session::has('grade_level')) {
//     Session::put('grade_level', $validated['grade_level']);
// }


//     $validated = $request->validate([
//         'grade_level' => 'required|string',
//         'topic' => 'required|string',
//     ]);

//     // STEP 1: Store conversation history in session
//     $chatHistory = Session::get('chat_history', []);
//     $chatHistory[] = [
//         'role' => 'user',
//         'content' => $validated['topic']
//     ];

//     // STEP 2: Summarize only if total history is too long
//     $priorMessages = array_slice($chatHistory, 0, -1);
//     $historyText = collect($priorMessages)->pluck('content')->implode("\n");
//     $wordCount = str_word_count($historyText);

//     $contextSummary = $historyText; // default: use full history

//     // ⚠️ Only summarize if history is over ~3000 words (~4000 tokens)
//     if ($wordCount > 3000) {
//         $summaryResponse = Http::timeout(10)->post('http://127.0.0.1:5001/summarize-history', [
//             'history' => $historyText,
//         ]);

//         if ($summaryResponse->failed()) {
//             return back()->withErrors(['error' => 'Summarization failed.'])->withInput();
//         }

//         $contextSummary = $summaryResponse->json()['summary'] ?? '';
//     }

//     // STEP 3: Combine context + latest input
//     $finalTopic = $contextSummary . "\n" . $validated['topic'];

//     // Final API call to get the step-by-step explanation
//     $finalResponse = Http::timeout(0)->post('http://127.0.0.1:5001/step-tutor', [
//         'grade_level' => $validated['grade_level'],
//         'topic' => $finalTopic,
//     ]);

//     if ($finalResponse->failed()) {
//         return back()->withErrors(['error' => 'Final tutor API failed.'])->withInput();
//     }

//     $output = $finalResponse->json()['response'] ?? 'No output';

//     // STEP 4: Store AI response in session
//     $chatHistory[] = [
//         'role' => 'assistant',
//         'content' => $output
//     ];
//     Session::put('chat_history', $chatHistory);

//     return view('step-tutor', [
//         'response' => $output,
//         'history' => $chatHistory
//     ]);
// }

public function processForm(Request $request)
    {
        set_time_limit(0);

        // Log headers to debug AJAX issue
        Log::info('Headers:', $request->headers->all());
        Log::info('Is AJAX: ' . ($request->ajax() ? 'yes' : 'no'));

        $validated = $request->validate([
            'grade_level' => 'required|string',
            'topic' => 'nullable|string',
        ]);

        // Store grade level in session if not yet stored
        if (!session()->has('grade_level')) {
            session(['grade_level' => $validated['grade_level']]);
        }

        // Fetch conversation history
        $history = ConversationHistory::where('user_id', Auth::id())
            ->where('agent', 'step-tutor')
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
        $newMessage = $validated['topic'];
        $chatHistory[] = ['role' => 'user', 'content' => $newMessage];

        // Store user message
        ConversationHistory::create([
            'user_id' => Auth::id(),
            'agent' => 'step-tutor',
            'message' => $newMessage,
            'sender' => 'user'
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

        // if (!empty($validated['add_cont'])) {
        //     $contextSummary .= "\n" . $validated['add_cont'];
        // }

        $finalTopic = "Prior Conversation Summary:\n" . $contextSummary . "\n\nStudent’s Follow-up:\n" . $newMessage;

        // $mode = count($chatHistory) === 1 ? 'chat' : 'manual';
        Log::info('Mode determined:', ['mode' => $mode]);

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'topic', 'contents' => $finalTopic],
            ['name' => 'mode', 'contents' => $mode],
            ['name' => 'user_id', 'contents' => Auth::id()],
            ['name' => 'history', 'contents' => json_encode($chatHistory)],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/step-tutor', $multipartData);

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
            'agent' => 'step-tutor',
            'message' => $output,
            'sender' => 'agent'
        ]);

        // Reload updated history
        $latestHistory = ConversationHistory::where('user_id', Auth::id())
            ->where('agent', 'step-tutor')
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

        return view('step-tutor', [
            'response' => $output,
            'history' => $chatHistory
        ]);
    }

    public function clearHistory(Request $request)
    {
        ConversationHistory::where('user_id', Auth::id())
            ->where('agent', 'step-tutor')
            ->delete();

        session()->forget('grade_level');

        return redirect()->back()->with('status', 'Conversation history cleared.');
    }

}
