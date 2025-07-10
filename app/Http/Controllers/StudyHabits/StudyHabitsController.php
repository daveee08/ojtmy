<?php

namespace App\Http\Controllers\StudyHabits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
class StudyHabitsController extends Controller

{
   public function fetchUserSession()
    {
        $userId = Auth::id();
        $response = Http::get("http://127.0.0.1:5001/sessions/$userId");
        return response()->json($response->json());
        // return view('Text Proofreader.proofreader');
    }
    public function showForm()
    {
        return view('Study Habits.studyhabits');
    }
    public function processForm(Request $request)
    {
        set_time_limit(0);
        $validated = $request->validate([
            'grade_level' => 'required|string',
            'goal'        => 'required|string',
        ]);

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'goal', 'contents' => $validated['goal']],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];
        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.1:5001/study_habits', $multipartData); // Adjusted URL to match your FastAPI endpoint
        
        if ($response->failed()) {
            logger()->error('FastAPI Study Habits error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()])->withInput();
        }

        $responseData = $response->json();
        logger($responseData); // ✅ Log the response

        $messageId = $responseData['message_id'] ?? null;
        if ($messageId) {
            // ✅ External redirect
            return redirect()->to("/chat/history/{$messageId}");
        }
        return view('Study Habits.studyhabits', [
            'response' => $responseData['output'] ?? 'No output (no message ID)',
        ]);
    }
}    



//         $response = Http::timeout(0)->post('http://127.0.0.1:5001/studyhabits', [
//             'grade_level' => $validated['grade_level'],
//             'goal'        => $validated['goal'],
//         ]);

//         if ($response->failed()) {
//             return back()->withErrors(['error' => 'Agent failed. Try again.'])->withInput();
//         }

//         return view('Study Habits.studyhabits', [
//             'plan' => $response['plan'] ?? 'No response generated.',
//         ]);
//     }
// }
