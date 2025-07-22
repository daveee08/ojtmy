<?php

namespace App\Http\Controllers\TongueTwister;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TongueTwistController extends Controller
{
    public function showForm()
    {
        return view('Tongue Twisters.TongueTwist', [
            'response' => '',
            'currentTopic' => '',
            'currentGrade' => '',
        ]);
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'topic' => 'required|string',
            'grade_level' => 'required|string',
        ]);

        $sessionId = Str::uuid()->toString(); // Generate a unique session ID

        $multipartData = [
            ['name' => 'topic', 'contents' => $validated['topic']],
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
            ['name' => 'session_id', 'contents' => $sessionId],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5000/tonguetwister', $multipartData);

        if ($response->failed()) {
            logger()->error('FastAPI TongueTwister error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        $responseData = $response->json();
        logger($responseData);

        $messageId = $responseData['message_id'] ?? null;
        $responseText = $responseData['output'] ?? 'No output (no message ID)';

        if ($messageId) {
            return redirect()->to("/chat/history/{$messageId}?session_id={$sessionId}");
        }

        return view('Tongue Twisters.TongueTwist', [
            'response' => $responseText,
            'currentTopic' => $validated['topic'],
            'currentGrade' => $validated['grade_level'],
        ]);
    }
}
