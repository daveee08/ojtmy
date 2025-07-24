<?php

namespace App\Http\Controllers\EmailResponder;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ResponderController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://192.168.50.238:8015/sessions/$userId");
        return response()->json($response->json());
    }

    public function showForm()
    {
        return view('Email Responder.responder');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'author' => 'required|string|max:255',
            'email' => 'required|string',
            'intent' => 'required|string',
            'tone' => 'required|string|in:Formal,Friendly,Concise,Apologetic,Assertive',
        ]);

        $multipartData = [
            ['name' => 'author', 'contents' => $validated['author'],],
            ['name' => 'email', 'contents' => $validated['email'],],
            ['name' => 'intent', 'contents' => $validated['intent'],],
            ['name' => 'tone', 'contents' => $validated['tone'],],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        // Replace with your actual backend URL
        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://192.168.50.238:8015/responder', $multipartData);

        if ($response->failed()) {
            logger()->error('FastAPI Leveler error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }
    
        $responseData = $response->json();
        logger($responseData);
    
        $messageId = $responseData['message_id'] ?? null;
    
        if ($messageId) {
            return redirect()->to("/chat/history/{$messageId}");
        }

        return view('Email Responder.responder', [
            'response' => $responseData['output'] ?? 'No output (no message ID)'
        ]);
    }
}
