<?php

namespace App\Http\Controllers\Coachsportpractice;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CoachSportsPracController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://localhost:5003/sessions/$userId");
        return response()->json($response->json());
    }

    public function showForm()
    {
        return view("Coach's Sports Practice.CoachSportsPrac");
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'input_type' => 'required|in:sport',
            'grade_level' => 'required|string',
            'length_of_practice' => 'required|string',
            'sport' => 'required|string',
            'additional_customization' => 'nullable|string',
        ]);

        $multipartData = [
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'length_of_practice', 'contents' => $validated['length_of_practice']],
            ['name' => 'sport', 'contents' => $validated['sport']],
            ['name' => 'additional_customization', 'contents' => $validated['additional_customization'] ?? ''],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        logger('Sending to FastAPI:', $multipartData);
        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://localhost:5003/coach_sports_prac', $multipartData);
        logger('FastAPI response:', [$responseData]);

        if ($response->failed()) {
            logger()->error('FastAPI CoachSportsPrac error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        $responseData = $response->json();
        logger($responseData);

        $messageId = $responseData['message_id'] ?? null;

        if ($messageId) {
            return redirect()->to("/chat/history/{$messageId}");
        }

        return view("Coach's Sports Practice.CoachSportsPrac", [
            'response' => $responseData['output'] ?? 'No output (no message ID)'
        ]);
    }
}
