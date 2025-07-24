<?php

namespace App\Http\Controllers\QuizMe;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class QuizmeController extends Controller
{
    /**
     * Fetches user-specific sessions for Quote of the Day.
     * This method is generally used for displaying a list of past QOTD sessions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchUserSessions()
    {
        // Get the authenticated user's ID. Default to 1 if not authenticated for testing/dev.
        $userId = Auth::id() ?? 1;

        // Make an HTTP GET request to your QuizMe backend's sessions endpoint.
        $response = Http::get("http://127.0.0.1:5000/quizme-sessions/{$userId}");

        // Return the JSON response directly from the backend.
        return response()->json($response->json());
    }

    /**
     * Displays the form for generating a quiz.
     *
     * @return \Illuminate\View\View
     */
    public function showQuizForm()
    {
        // Pass empty/default values to the view so old() doesn't throw errors on first load
        return view('Quiz Me.quizbot', [
            'topic' => '',
            'grade_level' => '',
            'num_questions' => null,
            'quiz_types' => [] // Ensure this is an array for old() to work with checkboxes
        ]);
    }

    /**
     * Handles the quiz generation request by calling the FastAPI backend.
     * This method is called via a POST request to /quizme/generate.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function generateQuiz(Request $request) // Line 55 for reference from your error
    {
        // Set an infinite time limit for this script execution,
        // useful if the backend process is long-running (e.g., LLM generation).
        set_time_limit(0);

        try {
            // Validate the incoming request data to ensure required fields are present.
            $validated = $request->validate([
                'topic' => 'required|string',
                'grade_level' => 'required|string',
                'num_questions' => 'nullable|integer|min:1|max:300',
                'quiz_types' => 'required|array|min:1',
                'quiz_types.*' => 'string|in:multiple_choice,fill_in_the_blanks,identification',
                'user_id' => 'nullable|integer',
            ]);

            $topic = $validated['topic'];
            $gradeLevel = $validated['grade_level'];
            $numQuestions = $validated['num_questions'];
            $quizTypes = $validated['quiz_types'];
            $userId = $validated['user_id'] ?? (Auth::id() ?? 1);

            $multipartData = [
                ['name' => 'topic', 'contents' => $topic],
                ['name' => 'grade_level', 'contents' => $gradeLevel],
                ['name' => 'num_questions', 'contents' => $numQuestions ?? 10],
                ['name' => 'quiz_types', 'contents' => implode(',', $quizTypes)],
                ['name' => 'user_id', 'contents' => $userId],
            ];

            Log::info('Payload sent to FastAPI:', [
                'topic' => $topic,
                'grade_level' => $gradeLevel,
                'num_questions' => $numQuestions,
                'quiz_types' => $quizTypes,
                'user_id' => $userId
            ]);

            $response = Http::timeout(0)->asMultipart()->post('http://127.0.0.1:5000/quizme',
                $multipartData
            );

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('FastAPI QuizMe Response:', ['response' => $responseData]);

                $messageId = $responseData['session_id'] ?? null;
                $quizQuestions = $responseData['questions'] ?? [];

                if ($messageId) {
                    return redirect()->to("/chat/history/{$messageId}");
                } else {
                    Log::error('FastAPI QuizMe Response missing session_id:', ['response' => $responseData]);
                    return back()->withErrors(['error' => 'Quiz generated, but session ID was not returned.']);
                }

            } else {
                Log::error('Quiz API Error:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                $errorMessage = $response->json()['detail'] ?? 'Unknown error from quiz generation service.';
                return back()->withErrors(['error' => 'Error contacting quiz generation service: ' . $errorMessage]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Laravel Validation Error:', [
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ]);
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Quiz Generation Error (Laravel side):', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'An unexpected error occurred while generating the quiz. Please check Laravel logs.']);
        }
    } // This missing closing brace was the most likely cause of your syntax error.

    // This closing brace is for the class QuizmeController
}