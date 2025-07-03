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

        $historyResponse = Http::get('http://192.168.50.10:8013/chat/messages', [
            'user_id' => $userId,
            'agent_id' => $agentId,
        ]);

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

        return view('Text Translator.translator', [
            'translation' => $data['translation'] ?? 'No translation returned.',
            'old' => $validated,
        ]);
    }

    public function followUp(Request $request)
{
    $request->validate([
        'original_text' => 'required|string',
        'language' => 'required|string',
        'followup' => 'required|string',
    ]);

    // You can build logic like: "$original + $followup context"
    $message = "Original: {$request->original_text}\nFollow-up: {$request->followup}";

    $translation = YourTranslationService::translate($message, $request->language); // Replace with your logic

    return view('translator_blade', [
        'translation' => $translation,
        'old' => [
            'text' => $message,
            'language' => $request->language
        ]
    ]);
}

}
