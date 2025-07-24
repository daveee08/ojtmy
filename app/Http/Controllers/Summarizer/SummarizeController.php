<?php

namespace App\Http\Controllers\Summarizer;

use App\Http\Controllers\BackendServiceController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Http, Log, Auth, DB};
use App\Models\{Message, ParameterInput};

class SummarizeController extends BackendServiceController
{
    public function index(Request $request)
    {
        

        return view('TextSummarizer.summarize');
    }

    public function summarize(Request $request)
    {
        $validated = $request->validate([
            'summary_instructions' => 'required|string',
            'input_text' => 'nullable|string',
            'pdf' => 'nullable|mimes:pdf|max:10240',
        ]);



        
        $multipart = [
            [
                'name' => 'conditions',
                'contents' => $validated['summary_instructions'],
            ],
            [
                'name' => 'text',
                'contents' => $validated['input_text'] ?? '',
            ],
            ['name' => 'user_id', 'contents' => Auth::id()],
        ];

        if ($request->hasFile('pdf')) {
            $multipart[] = [
                'name' => 'pdf',
                'contents' => fopen($request->file('pdf')->getPathname(), 'r'),
                'filename' => $request->file('pdf')->getClientOriginalName(),
            ];
        }
        

        try{

            $response = Http::Timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:8003/summarize', $multipart);


            Log::info('Summarizer Response:', ['response' => $response -> body()]);

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