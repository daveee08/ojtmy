<?php

namespace App\Http\Controllers\Translator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
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

        // Check if message_id exists in session, otherwise generate a new one
        if (!session()->has('translator_message_id')) {
            $generatedId = \Illuminate\Support\Str::uuid();
            session(['translator_message_id' => $generatedId]);
        }

        $threadId = session('translator_message_id');

        $multipartData = [
            ['name' => 'text', 'contents' => $validated['text']],
            ['name' => 'target_language', 'contents' => $validated['language']],
            ['name' => 'mode', 'contents' => 'manual'],
            ['name' => 'agent_id', 'contents' => 2], // Assuming agent_id for translator is 2
            ['name' => 'user_id', 'contents' => auth()->id() ?: 1], // Use authenticated user ID or default to 1
            // ['name' => 'parameter_inputs', 'contents' => json_encode(['grade_level' => '8'])] // Example parameter input
            ['name' => 'message_id', 'contents' => $threadId],
        ];
        
        

        $response = Http::timeout(0)->asMultipart()
            ->post('http://127.0.0.1:8013/translate', $multipartData);

        Log::info('Translation request sent', [
            'text' => $validated['text'],
            'language' => $validated['language'],
            'response_status' => $response->status(),
        ]);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Translation failed.'])->withInput();
        }

        $data = $response->json();

        return view('Text Translator.translator', [
            'translation' => $data['translation'] ?? 'No translation returned.',
            'old' => $validated,
        ]);
    }

    public function followUp(Request $request)
{
    $request->validate([
        'original_text' => 'required|string',
        'language' => 'required|string',
        'followup' => 'required|string',
    ]);

    // You can build logic like: "$original + $followup context"
    $message = "Original: {$request->original_text}\nFollow-up: {$request->followup}";

    $translation = YourTranslationService::translate($message, $request->language); // Replace with your logic

    return view('translator_blade', [
        'translation' => $translation,
        'old' => [
            'text' => $message,
            'language' => $request->language
        ]
    ]);
}

}
