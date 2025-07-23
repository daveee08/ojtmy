<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session; 
use App\Models\ConversationHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
use App\Models\ParameterInput;




class StepTutorController extends Controller
{


    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://127.0.0.1:8020/sessions/$userId");
        return response()->json($response->json());
    }
    public function showForm()
    {
        return view('Step by Step.step-tutor');
    }

    public function processForm(Request $request)
    {

        set_time_limit(0);
        // Only validate topic, not grade_level
        $validated = $request->validate([
            'topic' => 'nullable|string',
            'grade_level' => 'required|string', 
        ]);

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'topic', 'contents' => $validated['topic'] ?? ''],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:8020/explain_step_by_step', $multipartData);

        if ($response->failed()) {
            logger()->error('FastAPI Leveler error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }
    
        $responseData = $response->json();
        logger($responseData); // ✅ Log the response
    
        $messageId = $responseData['message_id'] ?? null;
    
        if ($messageId) {
            // ✅ External redirect
            return redirect()->to("/chat/history/{$messageId}");
        }
    
        // fallback if missing message ID
        // return view('Step by Step.step-tutor', [
        //     'response' => $responseData['explanation'] ?? 'No output (no message ID)'
        // ]);

    }
        
// public function processForm(Request $request)
// {
//     set_time_limit(0);

//     // Log headers to debug AJAX issue
//     Log::info('Headers:', $request->headers->all());
//     Log::info('Is AJAX: ' . ($request->ajax() ? 'yes' : 'no'));

//     // Only validate topic, not grade_level
//     $validated = $request->validate([
//         'topic' => 'nullable|string',
//     ]);

//     // Fetch grade level from authenticated user
//     $user = Auth::user();
//     $gradeLevel = $user->grade_level;

//     if (!$gradeLevel) {
//         return back()->withErrors(['error' => 'No grade level set for your account. Please update your profile.']);
//     }

//     // Fetch conversation history
//     $history = ConversationHistory::where('user_id', $user->id)
//         ->where('agent', 'step-tutor')
//         ->orderBy('created_at')
//         ->get();

//     $chatHistory = $history->map(function ($item) {
//         return [
//             'role' => $item->sender,
//             'content' => $item->message
//         ];
//     })->toArray();

//     $mode = count($chatHistory) >= 1 ? 'chat' : 'manual';
//     $newMessage = $validated['topic'];
//     $chatHistory[] = ['role' => 'user', 'content' => $newMessage];

//     // Store user message
//     ConversationHistory::create([
//         'user_id' => $user->id,
//         'agent' => 'step-tutor',
//         'message' => $newMessage,
//         'sender' => 'user'
//     ]);

//     // Build context from history
//     $priorMessages = array_slice($chatHistory, 0, -1);
//     $historyText = collect($priorMessages)->pluck('content')->implode("\n");

//     $contextSummary = $historyText;
//     if (str_word_count($historyText) > 24000) {
//         $summaryResponse = Http::timeout(10)->post('http://127.0.0.1:5001/summarize-history', [
//             'history' => $historyText,
//         ]);
//         if ($summaryResponse->successful()) {
//             $contextSummary = $summaryResponse->json()['summary'] ?? $historyText;
//         }
//     }

//     $finalTopic = "Prior Conversation Summary:\n" . $contextSummary . "\n\nStudent’s Follow-up:\n" . $newMessage;

//     Log::info('Mode determined:', ['mode' => $mode]);

//     $multipartData = [
//         ['name' => 'grade_level', 'contents' => $gradeLevel],
//         ['name' => 'topic', 'contents' => $finalTopic],
//         ['name' => 'mode', 'contents' => $mode],
//         ['name' => 'user_id', 'contents' => $user->id],
//         ['name' => 'history', 'contents' => json_encode($chatHistory)],
//     ];

//     $response = Http::timeout(0)
//         ->asMultipart()
//         ->post('http://127.0.0.1:5001/step-tutor', $multipartData);

//     if ($response->failed()) {
//         $errorMessage = 'Python API failed: ' . $response->body();
//         Log::error($errorMessage);

//         if ($request->ajax() || $request->wantsJson()) {
//             return response()->json([
//                 'error' => 'Python API failed',
//                 'details' => $response->body()
//             ], 500);
//         }

//         return back()->withErrors(['error' => $errorMessage]);
//     }

//     $output = $response->json()['output'] ?? 'No output';

//     ConversationHistory::create([
//         'user_id' => $user->id,
//         'agent' => 'step-tutor',
//         'message' => $output,
//         'sender' => 'agent'
//     ]);

//     $latestHistory = ConversationHistory::where('user_id', $user->id)
//         ->where('agent', 'step-tutor')
//         ->orderBy('created_at')
//         ->get();

//     $chatHistory = $latestHistory->map(function ($item) {
//         return [
//             'role' => $item->sender,
//             'content' => $item->message
//         ];
//     })->toArray();

//     if ($request->ajax() || $request->wantsJson()) {
//         return response()->json([
//             'message' => $output,
//             'history' => $chatHistory
//         ]);
//     }

//     return view('step-tutor', [
//         'response' => $output,
//         'history' => $chatHistory
//     ]);
// }

    // public function clearHistory(Request $request)
    // {
    //     ConversationHistory::where('user_id', Auth::id())
    //         ->where('agent', 'step-tutor')
    //         ->delete();

    //     session()->forget('grade_level');

    //     return redirect()->back()->with('status', 'Conversation history cleared.');
    // }

}
