<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IdeaGeneratorController extends Controller
{
    public function showForm()
    {
        return view('idea-generator');
    }

    public function generate(Request $request)
    {
       $request->validate([
            'grade_level' => 'required|string',
            'prompt' => 'required|string',
        ]);


       $response = Http::timeout(300)->asForm()->post('http://127.0.0.1:8001/generate-idea', [
            'grade_level' => $request->grade_level,
            'prompt' => $request->prompt,
        ]);



        if ($response->successful()) {
            return back()->with('ideas', $response->json()['idea']);
        } else {
            return back()->with('error', 'Failed to generate ideas. Please try again.');
        }
    }
}
