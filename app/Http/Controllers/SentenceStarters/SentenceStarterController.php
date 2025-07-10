<?php

namespace App\Http\Controllers\SentenceStarters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SentenceStarterController extends Controller
{
   public function fetchUserSession()
    {
        $userId = Auth::id();
        $response = Http::get("http://127.0.0.1:5001/sessions/$userId");
        return response()->json($response->json());       
    }
    public function showForm()
    {
        return view('Sentence Starter.sentencestarter');
    }
    public function processForm(Request $request)
    {
        set_time_limit(0);
        $validated = $request->validate([
            'grade_level' => 'required|string',
            'topic'       => 'required|string',
        ]);

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'topic', 'contents' => $validated['topic']],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.1:5001/sentence-starters', $multipartData); // Adjusted URL to match your FastAPI endpoint
       
            if ($response->failed()) {
            logger()->error('FastAPI Sentence Starters error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()])->withInput();
        }
        $responseData = $response->json();
        logger($responseData); // ✅ Log the response

        $messageId = $responseData['message_id'] ?? null;
        if ($messageId) {
            // ✅ External redirect
            return redirect()->to("/chat/history/{$messageId}");
        }
        return view('Sentence Starter.sentencestarter', [
            'response' => $responseData['output'] ?? 'No output (no message ID)',
        ]);
    }
}