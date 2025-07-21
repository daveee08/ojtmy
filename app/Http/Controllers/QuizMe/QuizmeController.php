<?php

namespace App\Http\Controllers\QuizMe;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class QuizmeController extends Controller
{
    public function fetchUserSessions()
    {
        $userId = Auth::id();
        $response = Http::get("http://localhost:5000/sessions/$userId");
        return response()->json($response->json());
    }

    public function showForm()
    {
        return view('Quiz Me.quizbot', [
            'cleanContent' => '',
            'response' => '',
            'currentTopic' => '',
            'currentGrade' => '',
            'example' => [
                'topic' => 'Photosynthesis',
                'grade_level' => '6th Grade',
                'num_questions' => 5
            ]
        ]);
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'input_type' => 'required|in:topic',
            'topic' => 'required|string|max:255',
            'grade_level' => 'required|string|max:255',
            'num_questions' => 'required|integer|min:1',
        ]);

        $multipartData = [
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'topic', 'contents' => $validated['topic']],
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'num_questions', 'contents' => $validated['num_questions']],
            ['name' => 'user_id', 'contents' => Auth::id() ?? 1],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5000/quizme', $multipartData);

        if ($response->failed()) {
            logger()->error('FastAPI QuizMe error', ['body' => $response->body()]);
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        $responseData = $response->json();
        logger($responseData);

        $messageId = $responseData['message_id'] ?? null;

        if ($messageId) {
            return redirect()->to("/chat/history/{$messageId}");
        }

        $responseText = $responseData['output'] ?? 'No output (no message ID)';
        // Clean up for display and download
        $cleanContent = preg_replace('/^[\*\+]\s?/m', '', $responseText); // Remove * and + at line start
        $cleanContent = preg_replace('/[_\*]/', '', $cleanContent); // Remove all _ and * anywhere
        $cleanContent = preg_replace('/\n{2,}/', "\n\n", $cleanContent); // Normalize newlines

        $topic = $request->input('topic', 'Quiz');
        $grade = $request->input('grade_level', 'All Levels');

        return view('Quiz Me.quizbot', [
            'response' => $responseText,
            'cleanContent' => $cleanContent,
            'currentTopic' => $topic,
            'currentGrade' => $grade,
            'example' => [
                'topic' => 'Photosynthesis',
                'grade_level' => '6th Grade',
                'num_questions' => 5
            ]
        ]);
    }

    public function downloadPracticePlan(Request $request)
    {
        set_time_limit(0);
        $content = $request->input('content');
        $topic = $request->input('topic', 'Quiz');
        $grade = $request->input('grade_level', 'All Levels');

        $topic_clean = preg_replace('/[^A-Za-z0-9 ]/', '', $topic);
        $grade_clean = preg_replace('/[^A-Za-z0-9 ]/', '', $grade);
        $filename = trim($topic_clean) . ' Quiz for ' . trim($grade_clean) . ' Level';

        $format = $request->input('format', 'txt');

        $cleanContent = preg_replace('/^[\*\+]\s?/m', '', $content);
        $cleanContent = preg_replace('/[_\*]/', '', $cleanContent);
        $cleanContent = preg_replace('/\n{2,}/', "\n\n", $cleanContent);

        if ($format === 'txt') {
            return response($cleanContent)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'.txt"');
        } elseif ($format === 'pdf') {
            $response = Http::timeout(0)
                ->asMultipart()
                ->post('http://127.0.0.1:5000/generate-pdf', [
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
        $topic = $request->input('topic', 'Quiz');
        $grade = $request->input('grade_level', 'All Levels');
        $filename = trim($topic) . ' Quiz for ' . trim($grade) . ' Level.pdf';
        $filename = preg_replace('/\s+/', ' ', $filename);
        $filename = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $filename);

        $response = Http::timeout(0)
            ->asForm()
            ->post('http://127.0.0.1:5000/generate-pdf', [
                'content' => $content,
            ]);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Failed to generate PDF: ' . $response->body()]);
        }

        return response($response->body(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
