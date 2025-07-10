<?php

namespace App\Http\Controllers\Explanations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ExplanationsController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://localhost:5001/sessions/$userId");
        return response()->json($response->json());
    }

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
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'concept', 'contents' => $request->input('concept', '')],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
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
            ->post('http://localhost:5001/explanations', $multipartData);

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

        return view('Explanations.explanations', [
            'response' => $responseData['output'] ?? 'No output (no message ID)'
        ]);
    }
}
