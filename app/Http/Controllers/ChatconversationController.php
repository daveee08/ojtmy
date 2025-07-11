<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;


class ChatconversationController extends Controller
{
    public function showForm($session_id)
    {
        $user_id = auth()->id() ?? 1; // or hardcode for testing
        return view('chat', [
            'session_id' => $session_id,
            'user_id' => $user_id
        ]);
    }
    
    public function getHistory($session_id)
    {
        $response = Http::get("http://192.168.50.10:8002/chat/history/{$session_id}");
    
        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch chat history'], 500);
        }
    
        return response()->json($response->json());
    }

    public function sendMessage(Request $request)
    {
        set_time_limit(0);
        $validated = $request->validate([
            'user_id' => 'required|numeric',
            'message_id' => 'required|numeric',
            'input' => 'required|string'
        ]);

        $formData = [
            ['name' => 'user_id', 'contents' => $validated['user_id']],
            ['name' => 'message_id', 'contents' => $validated['message_id']],
            ['name' => 'input', 'contents' => $validated['input']],
        ];

        $response = Http::asMultipart()
            ->timeout(0)
            ->post('http://192.168.50.10:8002/chat', $formData);
    
        if ($response->failed()) {
            \Log::error('FastAPI error', ['body' => $response->body()]);
            return response()->json(['error' => 'Failed to get response from AI'], 500);
        }

        return response()->json($response->json());
    }

    public function getUserSessions(Request $request)
    {
        // Laravel's 'auth' middleware will ensure Auth::id() is available.
        // If this function is reached, the user is authenticated.
        $userId = Auth::id();

        try {
            // Make the server-side request to your external session service
            $response = Http::get("http://192.168.50.10:8002/sessions/{$userId}");

            if ($response->successful()) {
                // Return the data directly as JSON
                return response()->json($response->json());
            } else {
                // Return an error response if the external service call fails
                return response()->json([
                    'error' => 'Failed to retrieve sessions from external service.',
                    'status' => $response->status(),
                    'message' => $response->body()
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Handle network or other unexpected errors
            return response()->json([
                'error' => 'An error occurred while fetching sessions.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}