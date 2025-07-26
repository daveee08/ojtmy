<?php

namespace App\Http\Controllers\Coachsportpractice;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use FPDF;

class CoachSportsPracController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://localhost:8026/sessions/$userId");
        return response()->json($response->json());
    }

    public function showForm()
    {
        return view("Coach's Sports Practice.CoachSportsPrac", [
            'cleanContent' => '', // Always define it
            'response' => '',
            'currentSport' => '',
            'currentGrade' => '',
            'example' => [
                'grade_level' => 'University',
                'length_of_practice' => '30 mins',
                'sport' => 'Soccer',
                'additional_customization' => 'Warmup and Drills'
            ]
        ]);
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'input_type' => 'required|in:sport',
            'grade_level' => 'required|string',
            'length_of_practice' => 'required|string',
            'sport' => 'required|string',
            'additional_customization' => 'nullable|string',
        ]);

        $multipartData = [
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'length_of_practice', 'contents' => $validated['length_of_practice']],
            ['name' => 'sport', 'contents' => $validated['sport']],
            ['name' => 'additional_customization', 'contents' => $validated['additional_customization'] ?? ''],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        logger('Sending to FastAPI:', $multipartData);
        $response = Http::timeout(60) // 60 seconds
            ->asMultipart()
            ->post('http://127.0.0.1:8026/coach_sports_prac', $multipartData);

        if ($response->failed()) {
            logger()->error('FastAPI CoachSportsPrac error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        $responseData = $response->json();
        logger('FastAPI response:', [$responseData]);

        $messageId = $responseData['message_id'] ?? null;

        if ($messageId) {
            return redirect()->to("/chat/history/{$messageId}");
        }

        $responseText = $responseData['output'] ?? 'No output (no message ID)';
        // Clean up for display and download
        $cleanContent = preg_replace('/^[\*\+]\s?/m', '', $responseText); // Remove * and + at line start
        $cleanContent = preg_replace('/[_\*]/', '', $cleanContent); // Remove all _ and * anywhere
        $cleanContent = preg_replace('/\n{2,}/', "\n\n", $cleanContent); // Normalize newlines

        // Get the last used sport and grade
        $sport = $request->input('sport', 'Practice');
        $grade = $request->input('grade_level', 'All Levels');

        // After generating the plan
        return view("Coach's Sports Practice.CoachSportsPrac", [
            'response' => $responseText,
            'cleanContent' => $cleanContent,
            'currentSport' => $sport, // pass the last used sport
            'currentGrade' => $grade, // pass the last used grade
            'example' => [
                'grade_level' => 'University',
                'length_of_practice' => '30 mins',
                'sport' => 'Soccer',
                'additional_customization' => 'Warmup and Drills'
            ]
        ]);
    }

    public function downloadPracticePlan(Request $request)
    {
        set_time_limit(0);
        $content = $request->input('content');
        $sport = $request->input('sport', 'Practice');
        $grade = $request->input('grade_level', 'All Levels');

        // Clean up for filename (remove special chars)
        $sport_clean = preg_replace('/[^A-Za-z0-9 ]/', '', $sport);
        $grade_clean = preg_replace('/[^A-Za-z0-9 ]/', '', $grade);
        $filename = trim($sport_clean) . ' Practice Plan for ' . trim($grade_clean) . ' Level';

        $format = $request->input('format', 'txt');

        // Clean up content for download (remove *, +, _, etc.)
        $cleanContent = preg_replace('/^[\*\+]\s?/m', '', $content);
        $cleanContent = preg_replace('/[_\*]/', '', $cleanContent);
        $cleanContent = preg_replace('/\n{2,}/', "\n\n", $cleanContent);

        if ($format === 'txt') {
            return response($cleanContent)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'.txt"');
        } elseif ($format === 'pdf') {
            // Call the Python FastAPI for PDF generation
            // Build a dynamic filename
            $response = Http::timeout(0)
                ->asMultipart()
                ->post('http://127.0.0.1:8026/generate-pdf', [
                    ['name' => 'content', 'contents' => $cleanContent],
                    ['name' => 'filename', 'contents' => $filename],
                ]);
            if ($response->successful()) {
                return response($response->body())
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="'.$filename.'.pdf"');
            } else {
                return back()->withErrors(['download' => 'Error generating PDF: ' . $response->body()]);
            }
        }
    }

    public function downloadPdf(Request $request)
    {
        $content = $request->input('content', '');
        $sport = $request->input('sport', 'Practice');
        $grade = $request->input('grade_level', 'All Levels');
        $filename = trim($sport) . ' Practice Plan for ' . trim($grade) . ' Level.pdf';
        $filename = preg_replace('/\s+/', ' ', $filename); // Clean up spaces
        $filename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $filename); // Remove illegal filename chars

        // Send to FastAPI
        $response = Http::timeout(0)
            ->asForm()
            ->post('http://127.0.0.1:8026/generate-pdf', [
                'content' => $content,
            ]);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Failed to generate PDF: ' . $response->body()]);
        }

        // Return PDF as download with custom filename
        return response($response->body(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

// Route for PDF download:
// Route::post('/coachsportprac/download-pdf', [CoachSportsPracController::class, 'downloadPdf']);
}
