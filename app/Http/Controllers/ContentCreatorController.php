<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ContentCreatorController extends Controller
{
    public function showForm()
    {
        return view('contentcreator');
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'grade_level' => 'required|string',
            'length' => 'required|string',
            'prompt' => 'required|string',
            'extra' => 'nullable|string',
        ]);

        try {
            $response = Http::timeout(60)->post('http://127.0.0.1:8001/generate-contentcreator', [
                'grade_level' => $validated['grade_level'],
                'length' => $validated['length'],
                'prompt' => $validated['prompt'],
                'extra' => $validated['extra'] ?? '',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return back()
                    ->with('content', $data['content'])
                    ->with('caption', $data['caption']);
            } else {
                return back()->with('error', 'Failed to generate content. Please try again.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Server error: ' . $e->getMessage());
        }
    }
}
