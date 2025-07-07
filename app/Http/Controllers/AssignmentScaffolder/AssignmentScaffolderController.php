<?php

namespace App\Http\Controllers\AssignmentScaffolder;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class AssignmentScaffolder extends Controller
{
    public function showForm()
    {
        return view('Assignment Scaffolder.assignmentscaffolder');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'input_type' => 'required|in:topic,pdf',
            'grade_level' => 'required|string',
            'topic' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $multipartData = [
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
        ];

        if ($validated['input_type'] === 'topic') {
            $multipartData[] = ['name' => 'topic', 'contents' => $validated['topic'] ?? ''];
        }

        if ($request->hasFile('pdf_file') && $validated['input_type'] === 'pdf') {
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

        try {
            $response = Http::timeout(0)
                ->asMultipart()
                ->post('http://192.168.50.123:5001/assignmentscaffolder', $multipartData);

            if ($response->failed()) {
                return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
            }

            return view('Assignment Scaffolder.assignmentscaffolder', ['response' => $response->json()['output'] ?? 'No output']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Request error: ' . $e->getMessage()]);
        }
    }
}
