<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SentenceStarterController extends Controller
{
    public function showForm()
    {
        return view('sentencestarter');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'required|string',
            'topic'       => 'required|string',
        ]);

        $response = Http::timeout(0)->post('http://127.0.0.1:5001/sentencestarters', [
            'grade_level' => $validated['grade_level'],
            'topic'       => $validated['topic'],
        ]);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Agent failed. Try again later.']);
        }

        $data = $response->json();

        return view('sentencestarter', [
            'output' => $data['starters'] ?? [],
            'old' => $validated,
        ]);
    }
}