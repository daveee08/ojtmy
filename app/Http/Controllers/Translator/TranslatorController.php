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

        // if ($request->has('message_id')) {
        //     $multipartData[] = ['name' => 'message_id', 'contents' => $request->input('message_id')];
        // } 
        // else {
        //     // If no message_id is provided, we can set a default or handle it accordingly
        // //     $multipartData[] = ['name' => 'message_id', 'contents' => '']; // Default to 1 or handle as needed
        // }
        
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


        return view('Text Translator.translator', [
            'translation' => $data['translation'] ?? 'No translation returned.',
            'old' => $validated,
        ]);
    }

    public function followUp(Request $request)
{
    set_time_limit(0);

    $validated = $request->validate([
        'followup'      => 'required|string',
    ]);

    $userId = auth()->id() ?? 1;


    $multipartData = [
        ['name' => 'text', 'contents' => $validated['followup']],
        ['name' => 'mode', 'contents' => 'chat'],
        ['name' => 'user_id', 'contents' => $userId],
        ['name' => 'db_message_id', 'contents' => 9], // hardcoded translator agent_id
    ];

    $response = Http::timeout(0)->asMultipart()
        ->post('http://127.0.0.1:8013/translate', $multipartData);

    // Log::info('Follow-up translation sent', [
    //     'chatContext' => $chatContext,
    //     'response_status' => $response->status(),
    //     'response_body' => $response->body(),
    // ]);

    if ($response->failed() || !$response->json() || !isset($response->json()['translation'])) {
        return back()->withErrors(['error' => 'Follow-up translation failed.'])->withInput();
    }

    // Fetch updated conversation history
    $historyResponse = Http::get('http://192.168.50.10:8013/chat/messages', [
        'user_id' => $userId,
        'agent_id' => 16, // hardcoded translator agent_id
    ]);

    $messages = $historyResponse->json()['messages'] ?? [];

    return view('Text Translator.translator', [
        // 'translation' => $response->json()['translation'],
        // 'old' => [
        //     'text' => $chatContext,
        //     'language' => $validated['language']
        // ],
        'messages' => $messages
    ]);
}


}
