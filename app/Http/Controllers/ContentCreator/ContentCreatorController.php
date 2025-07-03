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

        try {
            $response = Http::timeout(60)->post('http://127.0.0.1:8001/contentcreator',
 [
                'grade_level' => $validated['grade_level'],
                'length' => $validated['length'],
                'prompt' => $validated['prompt'],
                'extra' => $validated['extra'] ?? '',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return back()->with('content', $data['content']);
            } else {
                return back()->with('error', 'Failed to generate content. Please try again.');
            }
        } catch (\Exception $e) {
            \Log::error('Content generation failed', [
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Server error: ' . $e->getMessage());
        }
    }
}
