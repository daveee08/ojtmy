<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ExplanationsController extends Controller
{
    public function showForm()
    {
        return view('Explanations.explanations');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        // Corrected field names
        $validated = $request->validate([
            'input_type' => 'required|in:topic,pdf',
            'grade_level' => 'required|string',
            'concept' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $multipartData = [
            [
                'name' => 'input_type',
                'contents' => $validated['input_type']
            ],
            [
                'name' => 'grade_level',
                'contents' => $validated['grade_level']
            ],
            [
                'name' => 'concept',
                'contents' => $request->input('concept', '')
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

        // Replace with your actual backend API URL
        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://192.168.50.123:5001/explanations', $multipartData);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        return view('Explanations.explanations', ['response' => $response->json()['output'] ?? 'No output']);
    }
}
