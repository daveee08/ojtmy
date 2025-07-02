<?php

namespace App\Http\Controllers\IdeaGenerator;

use App\Http\Controllers\Controller; // ✅ Required
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IdeaGeneratorController extends Controller
{
    public function showForm()
    {
        return view('IdeaGenerator.idea-generator');
    }

    public function generate(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'grade_level' => 'required|string',
            'prompt' => 'required|string',
        ]);

        try {
            $response = Http::asForm()->post('http://127.0.0.1:8001/generate-idea', [
                'grade_level' => $request->grade_level,
                'prompt' => $request->prompt,
            ]);

            if ($response->successful()) {
                return back()->with('ideas', $response->json()['idea']);
            } else {
                return back()->with('error', 'Failed to generate ideas. Please try again.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while generating ideas: ' . $e->getMessage());
        }
    }
}
