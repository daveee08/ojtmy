<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FiveQuestionsController extends Controller
{
    public function showForm()
    {
        return view('fivequestions');
    }
    public function processForm(Request $request)
{
    set_time_limit(0);          // allow up to 3 minutes (adjust as needed)

    $validated = $request->validate([
        'grade_level' => 'required|string',
        'prompt'      => 'required|string',
    ]);

    $response = Http::timeout(0)->post(
        'http://127.0.0.1:5001/5questions',
        [
            'grade_level' => $validated['grade_level'],
            'prompt'      => $validated['prompt'],
        ]
    );

    if ($response->failed()) {
        return back()
            ->withErrors(['error' => 'Agent failed. Try again later.'])
            ->withInput();
    }

    $data = $response->json();

    return view('fivequestions', [
        'questions' => collect($data['questions'] ?? [])
            ->filter(function ($q) {
                // Remove non-question intros
                return preg_match('/[?.]$/', trim($q)); // keep only lines ending in ? or .
            })
            ->values()
            ->take(5)
            ->all(),
    ]);
}
}
