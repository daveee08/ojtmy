<?php

namespace App\Http\Controllers\TextLeveler;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LevelerController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://192.168.50.144:5001/sessions/$userId");
        return response()->json($response->json());
    }
    public function showForm()
    {
        return view('Text Leveler.leveler');
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
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'learning_speed', 'contents' => $validated['learning_speed']],
            ['name' => 'topic', 'contents' => $validated['topic'] ?? ''],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];
    
        if ($request->hasFile('pdf_file')) {
            $pdf = $request->file('pdf_file');
            $multipartData[] = [
                'name'     => 'pdf_file',
                'contents' => fopen($pdf->getPathname(), 'r'),
                'filename' => $pdf->getClientOriginalName(),
                'headers'  => ['Content-Type' => $pdf->getMimeType()],
            ];
        }
    
        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://192.168.50.144:5001/leveler', $multipartData);
    
        if ($response->failed()) {
            logger()->error('FastAPI Leveler error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }
    
        $responseData = $response->json();
        logger($responseData); // ✅ Log the response
    
        $messageId = $responseData['message_id'] ?? null;
    
        if ($messageId) {
            // ✅ External redirect
            return redirect()->to("/chat/history/{$messageId}");
        }
    
        // fallback if missing message ID
        return view('Text Leveler.leveler', [
            'response' => $responseData['output'] ?? 'No output (no message ID)'
        ]);
    }
}
