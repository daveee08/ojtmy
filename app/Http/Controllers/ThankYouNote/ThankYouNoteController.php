<?php

namespace App\Http\Controllers\ThankYouNote;

use App\Http\Controllers\Controller; // ✅ Add this line
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;




class ThankYouNoteController extends Controller
{

    // public function fetchUserSessions()
    // {
    //     $userId = Auth::id();
    //     $response = Http::get("http://localhost:5001/sessions/$userId");
    //     return response()->json($response->json());
    // }
    /**
     * Show the form for the Thank You Note
     */
    public function showForm()
    {
        return view('ThankYouNote.thankyounote'); 
        // Make sure this exists: resources/views/ThankYouNote/thankyounote.blade.php
    }

    /**
     * Generate the Thank You Note
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $multipartData = [
            ['name' => 'reason', 'contents' => $validated['reason']],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],

        ];

        try{

            $response = Http::Timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/generate-thankyou', $multipartData);


            Log::info('Thank you generator response:', ['response' => $response -> body()]);

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
        }
        catch (\Exception $e) {
            return back()->with('error', 'An error occurred while generating ideas: ' . $e->getMessage());
        }

    }
}
