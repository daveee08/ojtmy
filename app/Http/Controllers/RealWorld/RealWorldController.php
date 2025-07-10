<?php
namespace App\Http\Controllers\RealWorld;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class RealWorldController extends Controller
{
    public function fetchUserSession()
    {
        $yserId = Auth::id();
        $response = Http::get("http://127.0.1:5001/sessions/$yserId");
        return response()->json($response->json());
        // return view('Real World Connections.realworld');
    }
    public function showForm()
    {
        return view('Real World Connections.realworld');
    }
    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
        'grade_level' => 'required|string',
        'topic'       => 'required|string',
    ]);

    $multipartData = [
        ['name' => 'grade_level', 'contents' => $validated['grade_level']],
        ['name' => 'topic', 'contents' => $validated['topic']],
        ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
    ];

    $response = Http::timeout(0)
        ->asMultipart()
        ->post('http://127.0.1:5001/real_world', $multipartData); // Adjusted URL to match your FastAPI endpoint

    if ($response->failed()) {
        logger()->error('FastAPI Real World error', ['body' => $response->body()]);
        return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
    }
    
    $responseData = $response->json();
    logger($responseData); // ✅ Log the response

    $messageId = $responseData['message_id'] ?? null;
    if ($messageId) {
        // ✅ External redirect
        return redirect()->to("/chat/history/{$messageId}");
    }

    return view('Real World Connections.realworld', [
        'response' => $responseData['output'] ?? 'No output (no message ID)',
    ]);

}
}


//     $data = $response->json();

//     // 🔧 Format the bold parts by replacing **text** with <strong>text</strong>
//     $examples = array_map(function ($item) {
//         return preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $item);
//     }, $data['examples'] ?? []);

//     return view('Real World Connections.realworld', [
//         'output' => $examples,
//         'old' => $validated,
//     ]);
//     }
// }