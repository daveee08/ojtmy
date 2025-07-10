<?php

namespace App\Http\Controllers\CharacterChat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CharacterChatController extends Controller
{
    public function showForm()
    {
        return view('CharacterChat.characterchat');
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'grade_level' => 'required|string',
            'character' => 'required|string',
        ]);

        try {
            $response = Http::timeout(60)->post('http://127.0.0.1:8001/generate-characterchat', [
                'grade_level' => $validated['grade_level'],
                'character' => $validated['character'],
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Character generation failed. Please try again.',
                    'details' => $response->body(),
                ], 500);
            }

            return response()->json([
                'response' => $response->json()['response'] ?? 'No response received.',
            ]);
        } catch (\Exception $e) {
            Log::error('CharacterChat Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Server error. Please try again later.',
                'exception' => $e->getMessage(),
            ], 500);
        }
    }
}
