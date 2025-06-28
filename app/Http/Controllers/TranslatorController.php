<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TranslatorController extends Controller
{
    public function showForm()
    {
        return view('translator');
    }

    public function processForm(Request $request)
    {
         set_time_limit(0);
         
        $validated = $request->validate([
            'text'     => 'required|string',
            'language' => 'required|string',
        ]);

        $response = Http::timeout(0)->post('http://127.0.0.1:5001/translate', [
            'text'             => $validated['text'],
            'target_language'  => $validated['language'],
        ]);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Translation failed.'])->withInput();
        }

        $data = $response->json();

        return view('translator', [
            'translation' => $data['translation'] ?? 'No translation returned.',
            'old' => $validated,
        ]);
    }
}
