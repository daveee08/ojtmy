<?php

namespace App\Http\Controllers\ContentCreator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ContentCreatorController extends Controller
{
    /**
     * Show the content creator form.
     */
    public function showForm()
    {
        return view('ContentCreator.contentcreator'); // Make sure the Blade file exists: resources/views/ContentCreator/contentcreator.blade.php
    }

    /**
     * Generate content using the AI backend.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'grade_level' => 'required|string',
            'length' => 'required|string',
            'prompt' => 'required|string',
            'extra' => 'nullable|string',
        ]);

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'length', 'contents' => $validated['length']],
            ['name' => 'prompt', 'contents' => $validated['prompt']],
            ['name' => 'extra', 'contents' => $validated['extra'] ?? ''],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        try {
            $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:8001/contentcreator', $multipartData);

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
            \Log::error('Content generation failed', [
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Server error: ' . $e->getMessage());
        }
    }
}
