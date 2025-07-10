<?php

namespace App\Http\Controllers\ChatWithDocs;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ChatWithDocsController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://localhost:5001/sessions/$userId");
        return response()->json($response->json());
    }

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
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'input_type_1', 'contents' => $validated['input_type_1'] ?? ''],
            ['name' => 'topic', 'contents' => $validated['topic'] ?? ''],
            ['name' => 'topic_1', 'contents' => $validated['topic_1'] ?? ''],
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
            ->post('http://localhost:5001/chatwithdocs', $multipartData);

        if ($response->failed()) {
            logger()->error('FastAPI Chat with Docs error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }
    
        $responseData = $response->json();
        logger($responseData);
    
        $messageId = $responseData['message_id'] ?? null;
    
        if ($messageId) {
            return redirect()->to("/chat/history/{$messageId}");
        }

        return view('Chat with Docs.chatwithdocs', [
            'response' => $responseData['output'] ?? 'No output (no message ID)'
        ]);
    }
}
