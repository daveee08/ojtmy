<?php

namespace App\Http\Controllers\Tongue Twister;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TongueTwistController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://localhost:5002/sessions/$userId");
        return response()->json($response->json());
    }

    public function showForm()
    {
        return view('Tongue Twisters.TongueTwist');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'input_type' => 'required|in:topic',
            'topic' => 'required|string',
            'grade_level' => 'required|string',
        ]);

        $multipartData = [
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'topic', 'contents' => $validated['topic']],
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://localhost:5002/tongue_twister', $multipartData);

        if ($response->failed()) {
            logger()->error('FastAPI TongueTwister error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        $responseData = $response->json();
        logger($responseData);

        $messageId = $responseData['message_id'] ?? null;

        if ($messageId) {
            return redirect()->to("/chat/history/{$messageId}");
        }

        return view('Tongue Twisters.TongueTwist', [
            'response' => $responseData['output'] ?? 'No output (no message ID)'
        ]);
    }
}
