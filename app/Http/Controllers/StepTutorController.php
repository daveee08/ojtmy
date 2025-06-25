<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session; 

class StepTutorController extends Controller
{
    public function showForm()
    {
        return view('step-tutor');
    }

    // Add at top

public function processForm(Request $request)
{
    set_time_limit(0);

    $validated = $request->validate([
        'grade_level' => 'required|string',
        'topic' => 'required|string',
    ]);

    if (!Session::has('grade_level')) {
    Session::put('grade_level', $validated['grade_level']);
}


    $validated = $request->validate([
        'grade_level' => 'required|string',
        'topic' => 'required|string',
    ]);

    // STEP 1: Store conversation history in session
    $chatHistory = Session::get('chat_history', []);
    $chatHistory[] = [
        'role' => 'user',
        'content' => $validated['topic']
    ];

    // STEP 2: Summarize only if total history is too long
    $priorMessages = array_slice($chatHistory, 0, -1);
    $historyText = collect($priorMessages)->pluck('content')->implode("\n");
    $wordCount = str_word_count($historyText);

    $contextSummary = $historyText; // default: use full history

    // ⚠️ Only summarize if history is over ~3000 words (~4000 tokens)
    if ($wordCount > 3000) {
        $summaryResponse = Http::timeout(10)->post('http://127.0.0.1:5001/summarize-history', [
            'history' => $historyText,
        ]);

        if ($summaryResponse->failed()) {
            return back()->withErrors(['error' => 'Summarization failed.'])->withInput();
        }

        $contextSummary = $summaryResponse->json()['summary'] ?? '';
    }

    // STEP 3: Combine context + latest input
    $finalTopic = $contextSummary . "\n" . $validated['topic'];

    // Final API call to get the step-by-step explanation
    $finalResponse = Http::timeout(0)->post('http://127.0.0.1:5001/step-tutor', [
        'grade_level' => $validated['grade_level'],
        'topic' => $finalTopic,
    ]);

    if ($finalResponse->failed()) {
        return back()->withErrors(['error' => 'Final tutor API failed.'])->withInput();
    }

    $output = $finalResponse->json()['response'] ?? 'No output';

    // STEP 4: Store AI response in session
    $chatHistory[] = [
        'role' => 'assistant',
        'content' => $output
    ];
    Session::put('chat_history', $chatHistory);

    return view('step-tutor', [
        'response' => $output,
        'history' => $chatHistory
    ]);
}

}
