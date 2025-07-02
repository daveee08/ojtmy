<?php

namespace App\Http\Controllers\ChatWithDocs;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class ChatWithDocsController extends Controller
{
    public function showForm()
    {
        return view('Chat with Docs.chatwithdocs');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        if ($request->input('input_type_1') === 'cancel') {
            $request->merge(['input_type_1' => null]);
        }

        $validated = $request->validate([
            'input_type' => 'required|in:topic,pdf',
            'input_type_1' => 'nullable|in:topic_1,pdf_1',
            'topic' => 'nullable|string',
            'topic_1' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
            'pdf_file_1' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $multipartData = [
            [
                'name' => 'input_type',
                'contents' => $validated['input_type']
            ],
            [
                'name' => 'input_type_1',
                'contents' => $validated['input_type_1'] ?? ''
            ],
            [
                'name' => 'topic',
                'contents' => $validated['topic'] ?? ''
            ],
            [
                'name' => 'topic_1',
                'contents' => $validated['topic_1'] ?? ''
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

        if ($request->hasFile('pdf_file_1')) {
            $pdf1 = $request->file('pdf_file_1');
            $multipartData[] = [
                'name'     => 'pdf_file_1',
                'contents' => fopen($pdf1->getPathname(), 'r'),
                'filename' => $pdf1->getClientOriginalName(),
                'headers'  => [
                    'Content-Type' => $pdf1->getMimeType()
                ],
            ];
        }

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://192.168.50.144:5001/chatwithdocs', $multipartData);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        return view('Chat with Docs.chatwithdocs', [
            'response' => $response->json()['output'] ?? 'No output'
        ]);
    }
}
