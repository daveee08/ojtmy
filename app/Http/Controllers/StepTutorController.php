<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StepTutorController extends Controller
{
    public function showForm()
    {
        return view('step-tutor');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'required|string',
            'topic' => 'required|string',
        ]);

        $response = Http::timeout(0)
            ->post('http://127.0.0.1:8000/step-tutor', $validated); // change IP if deployed

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        return view('step-tutor', ['response' => $response->json()['response'] ?? 'No output']);
    }
}
