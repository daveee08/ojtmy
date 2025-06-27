<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SummarizeController extends Controller
{
    public function index()
{
    return view('summarize');
}

    public function summarize(Request $request)
{
    $validated = $request->validate([
        'conditions' => 'required|string',
        'input_text' => 'nullable|string',
        'pdf' => 'nullable|mimes:pdf|max:10240',
    ]);
    

    $multipart = [
        [
            'name' => 'conditions',
            'contents' => $validated['conditions'],
        ],
        [
            'name' => 'text',
            'contents' => $validated['input_text'] ?? '',
        ],
    ];

    if ($request->hasFile('pdf')) {
        $multipart[] = [
            'name' => 'pdf',
            'contents' => fopen($request->file('pdf')->getPathname(), 'r'),
            'filename' => $request->file('pdf')->getClientOriginalName(),
        ];
    }

    $response = Http::timeout(60)
        ->asMultipart()
        ->post('http://127.0.0.1:8001/summarize', $multipart); // ⬅ make sure it's the FastAPI port

    $summary = $response->json()['summary'] ?? 'No summary returned.';

    return view('summarize', compact('summary'));
}

}
