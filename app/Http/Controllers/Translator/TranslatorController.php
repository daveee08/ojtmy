<?php

namespace App\Http\Controllers\Translator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
class TranslatorController extends Controller
{
    public function showForm()
    {
        return view('Text Translator.translator');
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

        return view('Text Translator.translator', [
            'translation' => $data['translation'] ?? 'No translation returned.',
            'old' => $validated,
        ]);
    }
}
