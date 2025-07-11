<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Http, Log, Auth, DB};
use App\Models\{Message, ParameterInput};

class TutorController extends Controller
{

    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://192.168.50.10:8002/sessions/$userId");
        return response()->json($response->json());
    }
    public function showForm(Request $request)
    {
        return view('Conceptual Understanding.tutor');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'nullable|string',
            'topic' => 'nullable|string',
            'add_cont' => 'nullable|string',
        ]);

        if (empty($validated['grade_level']) && empty($validated['topic']) && empty($validated['add_cont'])) {
        return back()->withErrors(['error' => 'Please provide at least one input field.']);
}

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'topic', 'contents' => $validated['topic']],
            ['name' => 'add_cont', 'contents' => $validated['add_cont'] ?? ""],//refer to the tutor_agent.py para giunsa pag gamit sa mode na gi pass dani
            ['name' => 'user_id', 'contents' => Auth::id()],
        ];

        Log::info('Tutor multipart payload:', ['multipart' => $multipartData]);




        try {
            $response = Http::timeout(0)
                ->asMultipart()
                ->post('http://192.168.50.10:8002/tutor', $multipartData);

            if ($response->failed()) {
                logger()->error('Tutor API failed', ['body' => $response->body()]);
                return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
            }

            $responseData = $response->json();
            $messageId = $responseData['message_id'] ?? null;

            if ($messageId) {
                session(['tutor_message_id' => $messageId]); // store if needed
                return redirect()->to("/chat/history/{$messageId}");
            }

            return back()->with('error', 'No message ID returned.');
        } catch (\Exception $e) {
            logger()->error('Tutor API exception', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Tutor API connection error: ' . $e->getMessage()]);
        }

    }

    public function clearHistory(Request $request)
    {
        Message::where('user_id', Auth::id())->delete();
        session()->forget('grade_level');
        return redirect()->back()->with('status', 'Conversation history cleared.');
    }
}
