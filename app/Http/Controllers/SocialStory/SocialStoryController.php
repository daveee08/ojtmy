<?php

namespace App\Http\Controllers\SocialStory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;


class SocialStoryController extends Controller
{

    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://localhost:5001/sessions/$userId");
        return response()->json($response->json());
    }
    public function showForm()
    {
        return view('SocialStory.socialstory');
    }

    public function generate(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'required|string',
            'situation' => 'required|string',
        ]);

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'situation', 'contents' => $validated['situation'] ?? ''],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        try {
            $response = Http::asMultipart()->post('http://127.0.0.1:5001/generate-socialstory', $multipartData);

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
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
