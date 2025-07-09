<?php

namespace App\Http\Controllers\IdeaGenerator;

use App\Http\Controllers\Controller; // ✅ Required
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;


class IdeaGeneratorController extends Controller
{

    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://localhost:5001/sessions/$userId");
        return response()->json($response->json());
    }

    public function showForm()
    {
        return view('IdeaGenerator.idea-generator');
    }

    public function generate(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'required|string',
            'prompt' => 'required|string',
        ]);

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'prompt', 'contents' => $validated['prompt'] ?? ''],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        try {
            $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/generate-idea', $multipartData);

            if ($response->failed()) {
            logger()->error('FastAPI Leveler error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
            }
        
            $responseData = $response->json();
            logger($responseData); // ✅ Log the response
        
            $messageId = $responseData['message_id'] ?? null;
        
            if ($messageId) {
                // ✅ External redirect
                return redirect()->to("/chat/history/{$messageId}");
            }
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while generating ideas: ' . $e->getMessage());
        }
    }
}
