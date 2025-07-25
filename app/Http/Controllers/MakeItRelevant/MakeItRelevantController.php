<?php

namespace App\Http\Controllers\MakeItRelevant;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class MakeItRelevantController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://192.168.50.32:8013/sessions/$userId");
        $response = Http::get("http://192.168.50.32:8013/sessions/$userId");
        return response()->json($response->json());
    }

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
            'interests'      => 'required|string',
            'learning_topic' => 'nullable|string',
            'pdf_file'       => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $multipartData = [
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'interests', 'contents' => $validated['interests']],
            ['name' => 'learning_topic', 'contents' => $validated['learning_topic'] ?? ''],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        if ($request->hasFile('pdf_file') && $validated['input_type'] === 'pdf') {
            $pdf = $request->file('pdf_file');
            $pdfPath = $pdf->getRealPath();

            $multipartData[] = [
                'name'     => 'pdf_file',
                'contents' => fopen($pdfPath, 'r'),
                'filename' => $pdf->getClientOriginalName(),
                'headers'  => ['Content-Type' => $pdf->getMimeType()],
            ];
        }

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://192.168.50.32:8013/makeitrelevant', $multipartData);

        if ($response->failed()) {
            logger()->error('FastAPI Make It Relevant error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        $responseData = $response->json();
        logger($responseData);

        $messageId = $responseData['message_id'] ?? null;

        if ($messageId) {
            return redirect()->to("/chat/history/{$messageId}");
        }

        return view('Make it Relevant.makeitrelevant', [
            'response' => $responseData['output'] ?? 'No output (no message ID)'
        ]);
    }
}
