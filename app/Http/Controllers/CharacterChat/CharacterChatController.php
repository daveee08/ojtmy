<?php

// app/Http/Controllers/CharacterChat/CharacterChatController.php

namespace App\Http\Controllers\CharacterChat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


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

    
            $multipartData = [
                ['name' => 'grade_level', 'contents' => $validated['grade_level']],
                ['name' => 'character', 'contents' => $validated['character']],
                ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
            ];

        try{

            $response = Http::Timeout(0)
            ->asMultipart()
            ->post('http://localhost:8003/generate-characterchat', $multipartData);


            Log::info('Thank you generator response:', ['response' => $response -> body()]);

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
        catch (\Exception $e) {
            return back()->with('error', 'An error occurred while generating ideas: ' . $e->getMessage());
        }
    }
}
