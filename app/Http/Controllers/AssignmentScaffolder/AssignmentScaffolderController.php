<?php

namespace App\Http\Controllers\AssignmentScaffolder;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AssignmentScaffolderController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://192.168.50.238:8014/sessions/$userId");
        return response()->json($response->json());
    }

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
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
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

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://192.168.50.238:8014/assignmentscaffolder', $multipartData);

        if ($response->failed()) {
            logger()->error('FastAPI Leveler error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }
    
        $responseData = $response->json();
        logger($responseData);
    
        $messageId = $responseData['message_id'] ?? null;
    
        if ($messageId) {
            return redirect()->to("/chat/history/{$messageId}");
        }

        return view('Assignment Scaffolder.assignmentscaffolder', [
            'response' => $responseData['output'] ?? 'No output (no message ID)'
        ]);
    }
}
