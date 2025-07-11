<?php

namespace App\Http\Controllers\EmailWriter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;



class EmailWriterController extends Controller
{
    public function show()
    {
        return view('EmailWriter.email-writer');
    }

    public function generate(Request $request)
    {

        set_time_limit(0);
        $validated = $request->validate([
            'email_input' => 'required|string',

        ]);

        $multipartData = [
            ['name' => 'content', 'contents' => $validated['email_input']],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],

        ];

        // $response = Http::asForm()->post('http://127.0.0.1:5001/generate-email', [
        //     'content' => $request->email_input,
        // ]);

        try{

            $response = Http::Timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/generate-email', $multipartData);


            Log::info('Email Writer response:', ['response' => $response -> body()]);

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
