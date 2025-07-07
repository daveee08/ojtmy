<?php

namespace App\Http\Controllers\Translator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
class TranslatorController extends Controller
{
    public function showForm()
    {
        // return view('Text Translator.translator');

        // for displaying the messages of that agent
        $userId = auth()->id() ?? 1;       // Default for testing
        $agentId = 16;                      // Example: Translator agent ID is 3

        $multipartData = [
            ['name' => 'user_id', 'contents' => $userId], // Initial empty text
            ['name' => 'agent_id', 'contents' => $agentId], // Initial empty text
        ];

        $historyResponse = Http::timeout(0)->asMultipart()
            ->post('http://192.168.50.10:8013/chat/messages', $multipartData);
        // $messages = $historyResponse->json()['messages'] ?? [];
        Log::info('Message payload dump', ['payload' => $historyResponse->json()]);

        $decoded = $historyResponse->json(); // <-- get the full decoded array

        $messages = $decoded['messages'] ?? []; // ✅ This must isolate the inner messages array

        Log::info('Fetched messages for translator', [
            'user_id' => $userId,
            'agent_id' => $agentId,
            'messages_count' => count($messages),
            'response_status' => $historyResponse->status(),
        ]);

        return view('Text Translator.translator', [
            'messages' => $messages, // ⚠️ not 'payload', not 'data', only the array of messages
        ]);
    }
    

    public function processForm(Request $request)
    {

        Log::info('🔁 processForm called');
         set_time_limit(0);
         
        $validated = $request->validate([
            'text'     => 'required|string',
            'language' => 'required|string',
        ]);


        $multipartData = [
            ['name' => 'text', 'contents' => $validated['text']],
            ['name' => 'target_language', 'contents' => $validated['language']],
            ['name' => 'mode', 'contents' => 'manual'],
            ['name' => 'user_id', 'contents' => auth()->id() ?: 1], // Use authenticated user ID or default to 1
        ];
        
        $response = Http::timeout(0)->asMultipart()
            ->post('http://127.0.0.1:8013/translate', $multipartData);

        
        Log::info('Translation request sent', [
            'text' => $validated['text'],
            'language' => $validated['language'],
            'response_status' => $response->status(),
            'response_body' => $response->body(), // <-- Add this
        ]);

        if ($response->failed() || !$response->json() || !isset($response->json()['translation'])) {
        return back()->withErrors(['error' => 'Translation failed.'])->withInput();
    }

        $data = $response->json();
        Log::info('Initial translation result', ['translation' => $data['translation']]);
        Log::info('Initial translation result', ['translation' => $data['translation']]);



        return view('Text Translator.translator', [
            'translation' => $data['translation'] ?? 'No translation returned.',
            'old' => $validated,
            'message_id' => $data['message_id'], // Pass message ID if available
            'language' => $validated['language'],

        ]);
    }

    public function followUp(Request $request)
{
    Log::info('🔁 followUp called ------------------');

    set_time_limit(0);

    $validated = $request->validate([
        'followup' => 'required|string',
        'message_id' => 'required|int',
        'target_language' => 'nullable|string', // Optional, can be used if needed
    ]);

    Log::info('Follow-up request validation passed', [
        'followup' => $validated['followup'],
        'message_id' => $validated['message_id'],
        'target_language' => $validated['target_language'] ?? 'not provided',
    ]);

    $multipartData = [
        ['name' => 'text', 'contents' => $validated['followup']],
        ['name' => 'message_id', 'contents' => $validated['message_id']],
        ['name' => 'user_id', 'contents' => auth()->id() ?: 1],
        ['name' => 'target_language', 'contents' => $validated['target_language'] ?? 'not provided'], // Assuming 'manual' mode for follow-up
        // Use authenticated user ID or default to 1
        // ['name' => 'target_language', 'contents' => $validated['language']],
    ];

    Log::info('Preparing multipart data for follow-up', [
        'multipart_data' => $multipartData,
    ]);

    $response = Http::timeout(0)->asMultipart()
        ->post('http://192.168.50.10:8013/translate/followup', $multipartData);

    Log::info('Follow-up request sent', [
        'followup' => $validated['followup'],  
        'response_status' => $response->status(),
        'response_body' => $response->body(),
    ]);

    return redirect()->back()->with('success', 'Follow-up sent!')->withInput([
        'message_id' => $validated['message_id'],
    ]);
}


}
