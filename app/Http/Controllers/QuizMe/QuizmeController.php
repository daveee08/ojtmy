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
     * Fetches user-specific sessions for Quiz Me!
     * This method is generally used for displaying a list of past Quiz Me sessions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchUserSessions()
    {
        // Get the authenticated user's ID. Default to 1 if not authenticated for testing/dev.
        $userId = Auth::id() ?? 1;

        // Make an HTTP GET request to your QuizMe backend's sessions endpoint.
        try {
            $response = Http::get("http://127.0.0.1:5000/quizme-sessions/{$userId}");

            if ($response->successful()) {
                // Return the JSON response directly from the backend.
                return response()->json($response->json());
            } else {
                Log::error('Failed to fetch QuizMe sessions from FastAPI:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'user_id' => $userId
                ]);
                return response()->json(['error' => 'Failed to load sessions. Please try again.'], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Exception while fetching QuizMe sessions:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);
            return response()->json(['error' => 'An unexpected error occurred while loading sessions.'], 500);
        }
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
                'user_id' => 'nullable|integer', // This field is not directly used from request, but validated if present
            ]);

            $topic = $validated['topic'];
            $gradeLevel = $validated['grade_level'];
            $numQuestions = $validated['num_questions'];
            $userId = Auth::id() ?? 1; // Always use Auth::id() if available, fallback to 1

            $multipartData = [
                ['name' => 'topic', 'contents' => $topic],
                ['name' => 'grade_level', 'contents' => $gradeLevel],
                ['name' => 'num_questions', 'contents' => $numQuestions ?? 10], // Default to 10 if null
                ['name' => 'user_id', 'contents' => $userId],
            ];

            Log::info('Payload sent to FastAPI for quiz generation:', [
                'topic' => $topic,
                'grade_level' => $gradeLevel,
                'num_questions' => $numQuestions,
                'user_id' => $userId
            ]);

            // Make the POST request to your FastAPI quiz generation service
            $response = Http::timeout(0)->asMultipart()->post('http://127.0.0.1:5000/quizme',
                $multipartData
            );

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('FastAPI QuizMe Response (successful):', ['response' => $responseData]);

                $messageId = $responseData['session_id'] ?? null;
                $quizQuestions = $responseData['questions'] ?? []; // Store questions if needed for later use

                if ($messageId) {
                    // Redirect to the chat history page with the new session ID
                    return redirect()->to("/chat/history/{$messageId}");
                } else {
                    Log::error('FastAPI QuizMe Response missing session_id:', ['response' => $responseData]);
                    return back()->withErrors(['error' => 'Quiz generated, but session ID was not returned.']);
                }

            } else {
                // Log and return error if FastAPI call was not successful
                Log::error('Quiz API Error (FastAPI returned non-successful status):', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                $errorMessage = $response->json()['detail'] ?? 'Unknown error from quiz generation service.';
                return back()->withErrors(['error' => 'Error contacting quiz generation service: ' . $errorMessage]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            Log::error('Laravel Validation Error during quiz generation:', [
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ]);
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            // Catch any other unexpected exceptions
            Log::error('Quiz Generation Error (Laravel side - unexpected exception):', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'An unexpected error occurred while generating the quiz. Please check Laravel logs for details.']);
        }
    } // Closing brace for generateQuiz method
} // Closing brace for QuizmeController class
