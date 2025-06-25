<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProofreaderController extends Controller
{
    public function showForm()
    {
        return view('proofreader');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'profile' => 'required|in:academic,casual,concise',
            'text'    => 'nullable|string',
            'pdf'     => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $inputText = $validated['text'] ?? '';
        $pdfPath = null;

        if ($request->hasFile('pdf')) {
            $path = $request->file('pdf')->store('uploads');
            $pdfPath = storage_path('app/' . $path);
        }

        if (empty($inputText) && !$pdfPath) {
            return back()->withErrors(['error' => 'Please provide text or upload a PDF.'])->withInput();
        }

        // ✅ Conditionally build the payload
        $payload = [
            'profile' => $validated['profile'],
            'text'    => $inputText,
        ];

        if ($pdfPath) {
            $payload['pdf_path'] = $pdfPath;
        }

        $response = Http::timeout(0)->post('http://127.0.0.1:5001/proofread', $payload);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Proofreader service failed.'])->withInput();
        }

        $result = $response->json();

        return view('proofreader', [
            'response' => $result,
            'old' => [
                'profile' => $validated['profile'],
                'text'    => $inputText,
            ],
        ]);
    }
}
