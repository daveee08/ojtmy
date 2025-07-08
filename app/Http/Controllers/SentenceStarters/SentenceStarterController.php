<?php

namespace App\Http\Controllers\SentenceStarters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentenceStarterController extends Controller
{
    /** Show the form and any previous messages for this agent. */
    public function showForm()
    {
        $userId  = auth()->id() ?? 1;   // default to 1 for local testing
        $agentId = 14;                  // sentence‑starter agent ID

        // Fetch chat history from the Python service

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
        return view('Sentence Starters.sentence_starters', [
            'messages' => $messages, // ⚠️ not 'payload', not 'data', only the array of messages
        ]);

    }
    
    public function processForm(Request $request)
    {
        Log::info('🔁 processForm called');
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'required|string',
            'text'        => 'required|string',
            'mode'        => 'required|string',
        ]);

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'text', 'contents' => $validated['text']],
            ['name' => 'mode', 'contents' => $validated['mode']],
            ['name' => 'user_id', 'contents' => auth()->id() ?? 1], // Default for testing
            ['name' => 'agent_id', 'contents' => 14], // Example: Sentence Starter agent ID is 14
        ];

        $response = Http::timeout(0)->asMultipart()
            ->post('http://127.0.0.1:5001/sentence-starters', $multipartData);

        Log::info('Response from sentence starters', [
            'text' => $validated['text'],
            'language' => $validated['language'],
            'response_status' => $response->status(),
            'response_body' => $response->body(), // <-- Add this
        ]);

       if ($response->failed() || !$response->json() || !isset($response->json()['sentence_starters'])) {
       return back()->withErrors(['Sentence starter failed', ])->withInput();
                
       $data = $response->json();
        Log::info('Initial sentence starter result', ['sentence_starters' => $data['sentence_starters']]);
        Log::info('Initial sentence starter result', ['sentence_starters' => $data['sentence_starters']]);

        return view('Sentence Starters.sentence_starters', [
            'sentence_starters' => $data['sentence_starters'] ?? 'No sentenced returned.',
            'old' => $validated,
            'message_id' => $data['message_id'],
            'language' => $validated['language'],
        ]);
    }          
}}