<?php

namespace App\Http\Controllers\TeacherJokes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TeacherJokesController extends Controller
{
    public function showForm()
    {
        return view('TeacherJokes', [
            'joke' => null,
            'currentGrade' => '',
            'currentCustomization' => '',
        ]);
    }

    public function generateJoke(Request $request)
    {
        $request->validate([
            'grade' => 'required|string',
            'customization' => 'nullable|string',
        ]);

        $grade = $request->input('grade');
        $customization = $request->input('customization');
        $userId = Auth::id() ?? 1;

        $formData = [
            'grade_level' => $grade,
            'additional_customization' => $customization ?? '',
            'user_id' => $userId,
        ];

        try {
            $response = Http::asForm()
                ->timeout(0)
                ->post('http://127.0.0.1:5000/teacherjokes', $formData);

            if ($response->failed()) {
                Log::error('TeacherJokes API Error:', ['body' => $response->body()]);
                return back()->withErrors(['error' => 'Failed to contact the joke service.']);
            }

            $responseData = $response->json();
            $joke = $responseData['joke'] ?? 'No joke returned.';
            $messageId = $responseData['message_id'] ?? null;

            if ($messageId) {
                // Redirect to message history if it exists
                return redirect()->to("/chat/history/{$messageId}");
            }

            return view('TeacherJokes', [
                'joke' => $joke,
                'currentGrade' => $grade,
                'currentCustomization' => $customization,
            ]);

        } catch (\Exception $e) {
            Log::error('TeacherJokes connection error:', ['exception' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Could not connect to the joke generator.']);
        }
    }
}
