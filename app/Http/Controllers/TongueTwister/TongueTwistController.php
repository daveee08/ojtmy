<?php

namespace App\Http\Controllers\TongueTwister;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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

        $multipartData = [
            ['name' => 'topic', 'contents' => $validated['topic']],
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:8025/tonguetwister', $multipartData);

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
            'response' => $responseData['output'] ?? 'No output (no message ID)',
            'currentTopic' => $validated['topic'],
            'currentGrade' => $validated['grade_level'],
        ]);
    }
}
