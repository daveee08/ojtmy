<?php

use App\Http\Controllers\QuizMe\QuizmeController; // Your current namespace, if you moved the file

use App\Http\Controllers\Controller; // Crucial for extending the base Controller class
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuizmeController extends Controller
{
    /**
     * Displays the quiz generation form.
     * This method is called via a GET request to /quizme.
     */
    public function showQuizForm()
    {
        // --- FIX IS HERE ---
        // Changed 'QuizMe.Quizbot' to 'Quiz Me.quizbot'
        // This matches the suggested path 'Quiz Me\quizbot' from the error message.
        return view('Quiz Me.quizbot'); // Ensure this view exists with the correct folder and file name
    }

    public function processForm(Request $request)
    {
        // Log the incoming request data for debugging
        Log::info('Incoming QuizMe form request:', $request->all());

        // 1. Validation
        $validated = $request->validate([
            'input_type' => 'required|in:topic',
            'topic' => 'required|string|min:3|max:500',
            'grade_level' => 'required|string|max:50',
            'num_questions' => 'nullable|integer|min:1|max:300', // Made nullable and added min/max
            'quiz_types' => 'required|array',
            'quiz_types.*' => 'in:multiple_choice,fill_in_the_blanks,identification',
        ]);

        $topic = $validated['topic'];
        $gradeLevel = $validated['grade_level'];
        $numQuestions = $validated['num_questions']; // Will be null if not provided
        $quizTypes = $validated['quiz_types'];

        // Generate a unique session ID for this quiz instance
        $sessionId = Str::uuid()->toString();

        try {
            // 2. Prepare payload for FastAPI
            $fastApiUrl = env('FASTAPI_BASE_URL', 'http://127.0.0.1:5000') . '/quizme';

            $payload = [
                'session_id' => $sessionId,
                'topic' => $topic,
                'grade_level' => $gradeLevel,
                'quiz_types' => $quizTypes,
            ];

            // Only add num_questions to payload if it's not null
            if (!is_null($numQuestions)) {
                $payload['num_questions'] = $numQuestions;
            }

            Log::info('Payload sent to FastAPI for QuizMe:', $payload);

            // 3. Make HTTP request to FastAPI
            $response = Http::timeout(60)->post($fastApiUrl, $payload);

            // 4. Handle response from FastAPI
            if ($response->successful()) {
                $fastApiResponse = $response->json();
                Log::info('FastAPI QuizMe response:', $fastApiResponse);

                if (isset($fastApiResponse['questions']) && is_array($fastApiResponse['questions'])) {
                    // Store the *entire quiz* data in the Laravel session
                    $request->session()->put('quiz_data_' . $sessionId, $fastApiResponse['questions']);

                    // Return a JSON response for AJAX calls
                    return response()->json([
                        'message' => 'Quiz questions generated successfully.',
                        'session_id' => $sessionId,
                        'questions' => $fastApiResponse['questions']
                    ]);
                } else {
                    Log::error('FastAPI QuizMe response missing "questions" or not an array.', ['response' => $fastApiResponse]);
                    return response()->json(['message' => 'Failed to generate quiz questions: Invalid response from AI.', 'error' => $fastApiResponse], 500);
                }
            } else {
                $errorMessage = 'Error from AI service: ' . $response->status() . ' - ' . ($response->json()['detail'] ?? $response->body());
                Log::error('FastAPI QuizMe request failed:', ['status' => $response->status(), 'response' => $response->body()]);
                return response()->json(['message' => $errorMessage], $response->status());
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Could not connect to FastAPI QuizMe service:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to connect to the quiz generation service. Please ensure the AI backend is running.', 'error' => $e->getMessage()], 503);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred during QuizMe generation:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handles submission of a single answer during an interactive quiz.
     */
    public function submitAnswer(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'question_index' => 'required|integer|min:0',
            'user_answer' => 'nullable|string',
            'question_type' => 'required|string',
            'correct_answer' => 'required',
        ]);

        $sessionId = $validated['session_id'];
        $questionIndex = $validated['question_index'];
        $userAnswer = $validated['user_answer'];
        $questionType = $validated['question_type'];
        $correctAnswer = $validated['correct_answer'];

        // Retrieve quiz data from session
        $quizData = $request->session()->get('quiz_data_' . $sessionId);

        if (!$quizData || !isset($quizData[$questionIndex])) {
            Log::warning('Invalid session ID or question index for submitAnswer.', ['session_id' => $sessionId, 'question_index' => $questionIndex]);
            return response()->json(['message' => 'Quiz session expired or invalid question.'], 404);
        }

        $currentQuestion = $quizData[$questionIndex];

        // Perform basic answer checking (can be more sophisticated)
        $isCorrect = false;
        $feedback = "Incorrect.";

        // Normalize answers for comparison (case-insensitive, trim whitespace)
        $normalizedUserAnswer = Str::lower(trim($userAnswer));
        $normalizedCorrectAnswer = Str::lower(trim($correctAnswer));

        if ($questionType === 'multiple_choice') {
            if ($normalizedUserAnswer === $normalizedCorrectAnswer) {
                $isCorrect = true;
                $feedback = "Correct!";
            } else {
                $feedback = "Incorrect. The correct answer was: " . $correctAnswer;
            }
        } else {
            // For fill-in-the-blanks and identification
            if ($normalizedUserAnswer === $normalizedCorrectAnswer) {
                $isCorrect = true;
                $feedback = "Correct!";
            } else {
                $feedback = "Incorrect. The correct answer was: " . $correctAnswer;
            }
        }

        // Update the session quiz_data with user's answer and correctness
        $quizData[$questionIndex]['user_answer'] = $userAnswer;
        $quizData[$questionIndex]['is_correct'] = $isCorrect;
        $request->session()->put('quiz_data_' . $sessionId, $quizData);


        return response()->json([
            'feedback' => $feedback,
            'is_correct' => $isCorrect,
            'done' => ($questionIndex + 1 >= count($quizData))
        ]);
    }

    /**
     * Retrieves all answers for a given quiz session.
     */
    public function revealAnswers(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        $sessionId = $validated['session_id'];

        $quizData = $request->session()->get('quiz_data_' . $sessionId);

        if (!$quizData) {
            return response()->json(['message' => 'Quiz session expired or invalid.'], 404);
        }

        return response()->json(['answers' => $quizData]);
    }
}