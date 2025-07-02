<?php

namespace App\Http\Controllers\TextLeveler; # added

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller; # added

class LevelerController extends Controller
{
    public function showForm()
    {
        return view('Text Leveler.leveler'); # added
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'input_type' => 'required|in:topic,pdf',
            'grade_level' => 'required|string',
            'learning_speed' => 'required|string',
            'topic' => 'nullable|string',
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
                'name' => 'learning_speed',
                'contents' => $validated['learning_speed']
            ],
            [
                'name' => 'topic',
                'contents' => $validated['topic'] ?? ''
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
            ->post('http://192.168.50.144:5001/leveler', $multipartData);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }
 
        return view('Text Leveler.leveler', ['response' => $response->json()['output'] ?? 'No output']); # added
    }
}