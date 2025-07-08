<?php

namespace App\Http\Controllers\MakeItRelevant;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class MakeItRelevantController extends Controller
{
    public function showForm()
    {
        return view('Make it Relevant.makeitrelevant');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'input_type'     => 'required|in:text,pdf',
            'grade_level'    => 'required|string',
            'learning_topic' => 'nullable|string',
            'pdf_file'       => 'nullable|file|mimes:pdf|max:5120',
            'interests'      => 'required|string',
        ]);

        $learningText = $validated['learning_topic'] ?? '';

        if ($request->hasFile('pdf_file') && $validated['input_type'] === 'pdf') {
            $pdf = $request->file('pdf_file');
            $pdfPath = $pdf->getRealPath();

            // Optional: If your Python API expects extracted text instead of raw file,
            // you may need to extract text here using a PDF parser.
            // For now, we'll send the file as-is (as in original layout).
            $multipartData = [
                [
                    'name' => 'grade_level',
                    'contents' => $validated['grade_level']
                ],
                [
                    'name' => 'learning_topic',
                    'contents' => ''
                ],
                [
                    'name' => 'interests',
                    'contents' => $validated['interests']
                ],
                [
                    'name'     => 'pdf_file',
                    'contents' => fopen($pdfPath, 'r'),
                    'filename' => $pdf->getClientOriginalName(),
                    'headers'  => [
                        'Content-Type' => $pdf->getMimeType()
                    ],
                ],
            ];
        } else {
            $multipartData = [
                [
                    'name' => 'grade_level',
                    'contents' => $validated['grade_level']
                ],
                [
                    'name' => 'learning_topic',
                    'contents' => $learningText
                ],
                [
                    'name' => 'interests',
                    'contents' => $validated['interests']
                ],
            ];
        }

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/makeitrelevant', $multipartData);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        return view('Make it Relevant.makeitrelevant', ['response' => $response->json()['output'] ?? 'No output']);
    }
}
