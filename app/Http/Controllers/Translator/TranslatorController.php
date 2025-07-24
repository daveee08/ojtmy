<?php

namespace App\Http\Controllers\Translator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\View; // Import View facade

class TranslatorController extends Controller
{

    public function showForm()
    {

        return view('Text Translator.translator');

    }



    public function processForm(Request $request)
    {

        set_time_limit(0);
         
        $validated = $request->validate([
            'text'     => 'required|string',
            'language' => 'required|string',
        ]);


        $multipartData = [
            ['name' => 'text', 'contents' => $validated['text']],
            ['name' => 'target_language', 'contents' => $validated['language']],
            ['name' => 'user_id', 'contents' => auth()->id() ?: 1], // Use authenticated user ID or default to 1
        ];


        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://localhost:8019/translate', $multipartData);

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

// ✅ Move this inside the class as a private method
    private function waitForFastApiService($url, $tries = 5, $delaySeconds = 1)
    {
        for ($i = 0; $i < $tries; $i++) {
            try {
                $ping = Http::timeout(2)->get($url);
                if ($ping->successful()) {
                    return true;
                }
            } catch (\Exception $e) {
                sleep($delaySeconds);
            }
        }

        return false;
    }
}