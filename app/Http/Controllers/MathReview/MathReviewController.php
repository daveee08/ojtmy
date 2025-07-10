<?php

namespace App\Http\Controllers\MathReview; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MathReviewController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://localhost:5001/sessions/$userId");
        return response()->json($response->json());
    }

    public function showForm()
    {
        return view('Math Review.mathreview'); 
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'required|string',
            'number_of_problems' => 'required|integer|min:1|max:100',
            'math_content' => 'required|string',
            'additional_criteria' => 'nullable|string',
        ]);

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'number_of_problems', 'contents' => $validated['number_of_problems']],
            ['name' => 'math_content', 'contents' => $validated['math_content']],
            ['name' => 'additional_criteria', 'contents' => $validated['additional_criteria'] ?? ''],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://localhost:5001/mathreview', $multipartData);

        if ($response->failed()) {
            logger()->error('FastAPI Math Review error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }
    
        $responseData = $response->json();
        logger($responseData); // ✅ Log the response
    
        $messageId = $responseData['message_id'] ?? null;
    
        if ($messageId) {
            // ✅ External redirect
            return redirect()->to("/chat/history/{$messageId}");
        }

        return view('Math Review.mathreview', [
            'response' => $responseData['output'] ?? 'No output (no message ID)'
        ]);
    }
}
