<?php

namespace App\Http\Controllers\Proofreader;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ProofreaderController extends Controller
{
    public function fetchUserSession()
    {
        $userId = Auth::id();
        $response = Http::get("http://127.0.0.1:5001/sessions/$userId");
        return response()->json($response->json());
        // return view('Text Proofreader.proofreader');
    }
    public function showForm()
    {
        return view('Text Proofreader.proofreader');
    }
    public function processForm(Request $request)
    {
        set_time_limit(0);
        $validated = $request->validate([
            'profile' => 'required|string',
            'text'    => 'nullable|string',
            // 'pdf'     => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $multipartData = [
            ['name' => 'profile', 'contents' => $validated['profile']],
            ['name' => 'text', 'contents' => $validated['text'] ?? ''],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.1:5001/proofread', $multipartData);
        
        if ($response->failed()) {
            logger()->error('FastAPI Proofreader error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        $responseData = $response->json();
        logger($responseData); // ✅ Log the response

        $messageId = $responseData['message_id'] ?? null;
        if ($messageId) {
            // ✅ External redirect
            return redirect()->to("/chat/history/{$messageId}");
        }

        return view('Text Proofreader.proofreader', [
            'response' => $responseData['output'] ?? 'No output (no message ID)',
        ]);
    }
}

//         if ($request->hasFile('pdf')) {
//             $pdf = $request->file('pdf');
//             $multipartData[] = [
//                 'name'     => 'pdf_file', // 🛠️ match FastAPI param
//                 'contents' => fopen($pdf->getPathname(), 'r'),
//                 'filename' => $pdf->getClientOriginalName(),
//                 'headers'  => [
//                     'Content-Type' => $pdf->getMimeType()
//                 ],
//             ];
//         }

//         if (empty($validated['text']) && !$request->hasFile('pdf')) {
//             return back()->withErrors(['error' => 'Please provide text or upload a PDF.'])->withInput();
//         }

//         $response = Http::timeout(0)
//             ->asMultipart()
//             ->post('http://127.0.0.1:5001/proofread', $multipartData);

//         if ($response->failed()) {
//             return back()->withErrors(['error' => 'Proofreader service failed: ' . $response->body()])
//                          ->withInput();
//         }

//         return view('Text Proofreader.proofreader', [
//             'response' => $response->json(),
//             'old' => [
//                 'profile' => $validated['profile'],
//                 'text'    => $validated['text'],
//             ],
//         ]);
//     }
// }
