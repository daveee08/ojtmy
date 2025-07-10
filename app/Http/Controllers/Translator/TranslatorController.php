<?php

namespace App\Http\Controllers\Translator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\View; // Import View facade

class TranslatorController extends Controller
{
    
    // public function getMessages()
    // {
    //     // for displaying the messages of that agent
    //     $userId = auth()->id() ?? 1;       // Default for testing
    //     $agentId = 16;                      // Example: Translator agent ID is 3

    //     $multipartData = [
    //         ['name' => 'user_id', 'contents' => $userId], // Initial empty text
    //         ['name' => 'agent_id', 'contents' => $agentId], // Initial empty text
    //     ];

    //     $historyResponse = Http::timeout(0)->asMultipart()
    //         ->post('http://192.168.50.10:8013/chat/messages', $multipartData);
    //     // $messages = $historyResponse->json()['messages'] ?? [];
    //     Log::info('Message payload dump', ['payload' => $historyResponse->json()]);

    //     $decoded = $historyResponse->json(); // <-- get the full decoded array

    //     $messages = $decoded['messages'] ?? []; // ✅ This must isolate the inner messages array
    //     Log::info('Messages', ['messages' => $messages]); // Debugging line to check messages

    //     Log::info('Fetched messages for translator', [
    //         'user_id' => $userId,
    //         'agent_id' => $agentId,
    //         'messages_count' => count($messages),
    //         'response_status' => $historyResponse->status(),
    //     ]);

    //     return $messages; // Return the messages array directly
    // }
    public function showForm()
    {
        // return view('Text Translator.translator');

        // for displaying the messages of that agent
        // $userId = auth()->id() ?? 1;       // Default for testing
        // $agentId = 16;                      // Example: Translator agent ID is 3

        // $multipartData = [
        //     ['name' => 'user_id', 'contents' => $userId], // Initial empty text
        //     ['name' => 'agent_id', 'contents' => $agentId], // Initial empty text
        // ];

        // $historyResponse = Http::timeout(0)->asMultipart()
        //     ->post('http://192.168.50.10:8013/chat/messages', $multipartData);
        // // $messages = $historyResponse->json()['messages'] ?? [];
        // Log::info('Message payload dump', ['payload' => $historyResponse->json()]);

        // $decoded = $historyResponse->json(); // <-- get the full decoded array

        // $messages = $decoded['messages'] ?? []; // ✅ This must isolate the inner messages array
        // Log::info('Messages', ['messages' => $messages]); // Debugging line to check messages

        // Log::info('Fetched messages for translator', [
        //     'user_id' => $userId,
        //     'agent_id' => $agentId,
        //     'messages_count' => count($messages),
        //     'response_status' => $historyResponse->status(),
        // ]);

    //     View::composer('layouts.history', function ($view) {
    //     $view->with([
    //         'agentId' => 16,
    //         // 'title' => 'Text Translator',
    //         // 'description' => 'Translate text to different languages using AI.',
    //     ]);
    // });

    //     $messages = $this->getMessages(); // Call the getMessages method to fetch messages

        // return view('Text Translator.translator', [
        //     'messages' => $messages, // ⚠️ not 'payload', not 'data', only the array of messages
        // ]);

        return view('Text Translator.translator');

    }


    // public function showSpecificMessages($message_id)
    // {
    //     $userId = auth()->id() ?? 1;
    //     $agentId = 16;

    //     $multipartData = [
    //         ['name' => 'user_id', 'contents' => $userId],
    //         ['name' => 'agent_id', 'contents' => $agentId],
    //         ['name' => 'session_id', 'contents' => $message_id], // API expects 'session_id'
    //     ];

    //     Log::info('Fetching specific messages for translator', [
    //         'user_id' => $userId,
    //         'agent_id' => $agentId,
    //         'session_id' => $message_id,
    //     ]);

    //     $response = \Illuminate\Support\Facades\Http::timeout(0)->asMultipart()
    //         ->post('http://192.168.50.10:8013/chat/specific_messages', $multipartData); // <-- use POST

    //     Log::info('Specific messages response', [
    //         'status' => $response->status(),
    //         'body' => $response->body(),
    //     ]);

    //     $data = $response->json();
    //     $messages = $data['messages'] ?? [];

    //     return view('Text Translator.specific_messages', [
    //         'messages' => $messages,
    //         'message_id' => $message_id,
    //     ]);
    // }
        

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
            ['name' => 'user_id', 'contents' => auth()->id() ?: 1], // Use authenticated user ID or default to 1
        ];
        
        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5002/translate', $multipartData);

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
    }

//     public function followUp(Request $request)
// {
//     Log::info('🔁 followUp called ------------------');

//     set_time_limit(0);

//     $validated = $request->validate([
//         'followup' => 'required|string',
//         'message_id' => 'required|int',
//         'target_language' => 'nullable|string', // Optional, can be used if needed
//     ]);

//     Log::info('Follow-up request validation passed', [
//         'followup' => $validated['followup'],
//         'message_id' => $validated['message_id'],
//         'target_language' => $validated['target_language'] ?? 'not provided',
//     ]);

//     $multipartData = [
//         ['name' => 'text', 'contents' => $validated['followup']],
//         ['name' => 'message_id', 'contents' => $validated['message_id']],
//         ['name' => 'user_id', 'contents' => auth()->id() ?: 1],
//         ['name' => 'target_language', 'contents' => $validated['target_language'] ?? 'not provided'], // Assuming 'manual' mode for follow-up
//         // Use authenticated user ID or default to 1
//         // ['name' => 'target_language', 'contents' => $validated['language']],
//     ];

//     Log::info('Preparing multipart data for follow-up', [
//         'multipart_data' => $multipartData,
//     ]);

//     $response = Http::timeout(0)->asMultipart()
//         ->post('http://192.168.50.10:8013/translate/followup', $multipartData);

//     Log::info('Follow-up request sent', [
//         'followup' => $validated['followup'],  
//         'response_status' => $response->status(),
//         'response_body' => $response->body(),
//     ]);

//     // Always call showSpecificMessages and return its response
//     return $this->showSpecificMessages($validated['message_id']);
// }


}
