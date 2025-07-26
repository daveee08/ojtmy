<?php

namespace App\Http\Controllers\TeacherJokes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class TeacherJokesController extends Controller
{

    public function showForm()
    {
        return view('Teacher Jokes.TeacherJokes');
    }

    // ... (other methods like showForm remain unchanged)

    public function generateJoke(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'topic' => 'required|string',
            'grade_level' => 'required|string',
        ]);

        $topic = $validated['topic'];
        $grade_level = $validated['grade_level'];
        $userId = Auth::id() ?? 1;

        $joke = null;
        $errorMessage = null;
        
        $response = Http::timeout(0)->post('http://127.0.0.1:8023/generate-joke', [
            'topic' => $topic,
            'grade_level' => $grade_level,
            'user_id' => $userId,
        ]);

        if ($response->failed()) {
            logger()->error('FastAPI Leveler error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        $responseData = $response->json();

        if (!isset($responseData['message_id']) || !isset($responseData['joke'])) {
            logger()->error('Invalid API response', ['response' => $responseData]);
            return back()->withErrors(['error' => 'Invalid response from Python API.']);
        }

        $messageId = $responseData['message_id'];

        if ($messageId) {
            return redirect()->to("/chat/history/{$messageId}");
        }

        return view('Teacher Jokes.TeacherJokes', [
            'joke' => $joke,
            'topic' => $topic,
            'grade_level' => $grade_level,
            'errorMessage' => $errorMessage
        ]);
    }
}    

//     public function downloadJoke(Request $request)
//     {
//         set_time_limit(0);

//         // ... (validation and content extraction remain unchanged)

//         if ($format === 'txt') {
//             return response($content)
//                 ->header('Content-Type', 'text/plain')
//                 ->header('Content-Disposition', 'attachment; filename="' . $filename . '.txt"');
//         } elseif ($format === 'pdf') {
//             try {
//                 $response = Http::timeout(0)->post('http://127.0.0.1:8023/generate-pdf', [
//                     'content' => $content,
//                     'filename' => $filename,
//                 ]);

//                 // ... (rest of the downloadJoke method remains unchanged)
//                 if ($response->successful()) {
//                     return response($response->body())
//                         ->header('Content-Type', 'application/pdf')
//                         ->header('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');
//                 } else {
//                     Log::error('TeacherJokes PDF API Error:', [
//                         'status' => $response->status(),
//                         'body' => $response->body()
//                     ]);
//                     return back()->withErrors(['download' => 'Error generating PDF: ' . ($response->json()['error'] ?? 'Unknown error')]);
//                 }
//             } catch (\Exception $e) {
//                 Log::error('TeacherJokes PDF connection error: ' . $e->getMessage());
//                 return back()->withErrors(['download' => 'Error connecting to PDF generation service: ' . $e->getMessage()]);
//             }
//         }
//     }
// }