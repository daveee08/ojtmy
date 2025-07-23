<?php

<<<<<<< HEAD
namespace App\Http\Controllers\TeacherJokes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\TeacherJokeHistory;
=======
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
>>>>>>> 074e8dffacfbb9951b315ed18c886c8ce4f55b18

class TeacherJokesController extends Controller
{
    public function showForm()
    {
<<<<<<< HEAD
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
=======
        return view('TeacherJokes');
    }

    public function generateJoke(Request $request)
    {
        $request->validate([
            'grade' => 'required|string',
            'customization' => 'nullable|string',
        ]);

        $grade = $request->input('grade');
        $customization = $request->input('customization');
        $joke = '';

        try {
            $response = Http::post('http://127.0.0.1:5004/generate-joke', [
                'grade_level' => $grade,
                'additional_customization' => $customization,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $joke = $responseData['joke'] ?? 'Error: Could not retrieve joke.';
                return response()->json(['joke' => $joke]);
            } else {
                $joke = 'Error contacting joke generation service.';
                \Illuminate\Support\Facades\Log::error('TeacherJokes API Error:' . $response->body());
                return response()->json(['error' => $joke], $response->status() ?: 500);
            }
        } catch (\Exception $e) {
            $joke = 'Error: Could not connect to the joke generation service.';
            \Illuminate\Support\Facades\Log::error('TeacherJokes connection error:' . $e->getMessage());
            return response()->json(['error' => $joke], 500);
>>>>>>> 074e8dffacfbb9951b315ed18c886c8ce4f55b18
        }
    }
}
