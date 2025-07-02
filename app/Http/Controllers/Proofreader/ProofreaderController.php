<?php

namespace App\Http\Controllers\Proofreader;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class ProofreaderController extends Controller
{
    public function showForm()
    {
        return view('Text Proofreader.proofreader');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'profile' => 'required|in:academic,casual,concise',
            'text'    => 'nullable|string',
            'pdf'     => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $multipartData = [
            [
                'name'     => 'profile',
                'contents' => $validated['profile']
            ],
            [
                'name'     => 'text',
                'contents' => $validated['text'] ?? ''
            ],
        ];

        if ($request->hasFile('pdf')) {
            $pdf = $request->file('pdf');
            $multipartData[] = [
                'name'     => 'pdf_file', // 🛠️ match FastAPI param
                'contents' => fopen($pdf->getPathname(), 'r'),
                'filename' => $pdf->getClientOriginalName(),
                'headers'  => [
                    'Content-Type' => $pdf->getMimeType()
                ],
            ];
        }

        if (empty($validated['text']) && !$request->hasFile('pdf')) {
            return back()->withErrors(['error' => 'Please provide text or upload a PDF.'])->withInput();
        }

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/proofread', $multipartData);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Proofreader service failed: ' . $response->body()])
                         ->withInput();
        }

        return view('Text Proofreader.proofreader', [
            'response' => $response->json(),
            'old' => [
                'profile' => $validated['profile'],
                'text'    => $validated['text'],
            ],
        ]);
    }
}
