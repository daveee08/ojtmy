<?php

// app/Http/Controllers/CharacterChat/CharacterChatController.php

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

        set_time_limit(0); // Allow script to run indefinitely
        $validated = $request->validate([
            'grade_level' => 'required|string',
            'character'   => 'required|string',
        ]);

        try {
            $multipartData = [
                ['name' => 'grade_level', 'contents' => $validated['grade_level']],
                ['name' => 'character', 'contents' => $validated['character']],
            ];

            $response = Http::timeout(0)
                            ->asMultipart()
                            ->post('http://127.0.0.1:8001/generate-characterchat', $multipartData);

            if ($response->failed()) {
                Log::error('CharacterChat API failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return back()->with('response', 'No character response received.');
            }

            $data = $response->json();

            return back()->with('response', $data['response'] ?? 'No character response received.');
        } catch (\Exception $e) {
            Log::error('CharacterChat Exception', ['message' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Server error. Please try again later.'])->withInput();
        }
    }
}
