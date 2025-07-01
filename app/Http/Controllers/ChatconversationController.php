<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatconversationController extends Controller
{
    public function showForm()
    {
        return view('chat');
    }

    public function getHistory($session_id)
    {
        $response = Http::get("http://192.168.50.144:5001/chat/history/{$session_id}");

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch chat history'], 500);
        }

        return response()->json($response->json());
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'topic' => 'required|string',
            'session_id' => 'required|string'
        ]);

        $formData = [
            ['name' => 'topic', 'contents' => $validated['topic']],
            ['name' => 'session_id', 'contents' => $validated['session_id']],
        ];

        $response = Http::asMultipart()
            ->timeout(0)
            ->post('http://192.168.50.144:5001/chat', $formData);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to get response from AI'], 500);
        }

        return response()->json($response->json());
    }
}
