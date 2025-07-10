<?php

namespace App\Http\Controllers\FiveQuestion;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FiveQuestionsController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://127.0.0.1:5001/sessions/$userId"); // Adjusted URL to match your FastAPI endpoint
        return response()->json($response->json());
    }
    public function showForm()
    {
        return view('Five Question.fivequestions');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);
        // Only validate topic, not grade_level
        $validated = $request->validate([
            'topic' => 'nullable|string',
            'grade_level' => 'required|string', 
        ]);

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'topic', 'contents' => $validated['topic'] ?? ''],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/five_questions', $multipartData);  // Adjusted URL to match your FastAPI endpoint

        if ($response->failed()) {
            logger()->error('FastAPI Five Question error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

                $responseData = $response->json();
        logger($responseData); // ✅ Log the response
    
        $messageId = $responseData['message_id'] ?? null;
    
        if ($messageId) {
            // ✅ External redirect
            return redirect()->to("/chat/history/{$messageId}");
        }
    
        // fallback if missing message ID
        return view('Five Question.fivequestions', [
            'response' => $responseData['output'] ?? 'No output (no message ID)'
        ]);
    }
}



            

