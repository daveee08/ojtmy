<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use GuzzleHttp\Psr7;

class TutorController extends Controller
{
    public function showForm()
    {
        return view('tutor');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'required|string',
            'input_type' => 'required|in:topic,pdf',
            'topic' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
            'add_cont' => 'nullable|string',
        ]);

        $multipartData = [
            [
                'name' => 'grade_level',
                'contents' => $validated['grade_level']
            ],
            [
                'name' => 'input_type',
                'contents' => $validated['input_type']
            ],
            [
                'name' => 'topic',
                'contents' => $validated['topic'] ?? ''
            ],
            [
                'name' => 'add_cont',
                'contents' => $validated['add_cont'] ?? ''
            ],
        ];

        if ($request->hasFile('pdf_file')) {
            $pdf = $request->file('pdf_file');
            $multipartData[] = [
                'name'     => 'pdf_file',
                'contents' => fopen($pdf->getPathname(), 'r'),
                'filename' => $pdf->getClientOriginalName(),
                'headers'  => [
                    'Content-Type' => $pdf->getMimeType()
                ],
            ];
        }

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://192.168.50.127:5001/tutor', $multipartData);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        return view('tutor', ['response' => $response->json()['output'] ?? 'No output']);
    }
}


// http://0.0.0.0:5001/tutor