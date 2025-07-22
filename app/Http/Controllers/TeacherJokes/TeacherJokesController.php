<?php

namespace App\Http\Controllers\TeacherJokes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\TeacherJokeHistory;

class TeacherJokesController extends Controller
{
    public function showForm()
    {
        return view('Teacher Jokes.TeacherJokes', [
            'response' => '',
            'currentTopic' => '',
            'currentGrade' => '',
        ]);
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);
        $sessionId = Str::uuid()->toString();
        $userId = Auth::id() ?? 1;

        $validated = $request->validate([
            'topic' => 'required|string',
            'grade_level' => 'required|string',
        ]);

        $multipartData = [
            ['name' => 'topic', 'contents' => $validated['topic']],
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
            ['name' => 'session_id', 'contents' => session('session_id') ?? ''],
        ];

        try {
            $response = Http::timeout(0)
                ->asMultipart()
                ->post('http://127.0.0.1:5000/generate-joke', $multipartData);

            if ($response->failed()) {
                Log::error('FastAPI TeacherJokes error', ['body' => $response->body()]);
                return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
            }

            $responseData = $response->json();

            $joke = $responseData['joke'] ?? null;
            $messageId = $responseData['message_id'] ?? null;
            $returnedSessionId = $responseData['session_id'] ?? $sessionId;

            if (!$joke || !$messageId) {
                return back()->withErrors(['error' => 'Incomplete response from joke generator.']);
            }

            // Save to DB
            TeacherJokeHistory::create([
                'user_id' => $userId,
                'topic' => $validated['topic'],
                'grade_level' => $validated['grade_level'],
                'joke' => $joke,
                'message_id' => $messageId,
                'session_id' => $returnedSessionId,
            ]);

            return back()->with([
                'response' => $joke,
                'message_id' => $messageId,
                'session_id' => $returnedSessionId,
            ])->withInput();

        } catch (\Exception $e) {
            Log::error('TeacherJokes connection error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Could not connect to the joke generator.']);
        }
    }
}
