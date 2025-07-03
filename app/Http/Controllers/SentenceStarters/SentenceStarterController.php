<?php

namespace App\Http\Controllers\SentenceStarters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class SentenceStarterController extends Controller
{
    public function showForm()
    {
        return view('Sentence Starter.sentencestarter');
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

        return view('Sentence Starter.sentencestarter', [
            'output' => $data['starters'] ?? [],
            'old' => $validated,
        ]);
    }
}