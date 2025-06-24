<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuizmeController extends Controller
{
    public function showForm()
    {
        Log::info('Displaying quizbot form.');
        return view('quizbot');
    }

    public function processForm(Request $request)
    {
        Log::info('Processing quiz generation request.', $request->all());
        $validatedData = $request->validate([
            'topic' => 'required|string|max:255',
            'grade_level' => 'required|string|max:255',
            'num_questions' => 'required|integer|min:1',
        ]);

        try {
            $response = Http::timeout(180)->post('http://127.0.0.1:5001/generate-quiz', [
                'topic' => $validatedData['topic'],
                'grade_level' => $validatedData['grade_level'],
                'num_questions' => $validatedData['num_questions'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $quiz = $data['quiz'] ?? 'No quiz generated.';
                $resources = $data['resources'] ?? 'No resources generated.';
                Log::info('Quiz and resources received from Python backend.');
                return response()->json(['quiz' => $quiz, 'resources' => $resources]);
            } else {
                $errorMessage = 'Failed to generate quiz or resources from API.' . ($response->json()['error'] ?? '');
                Log::error('API call failed: ' . $errorMessage);
                return response()->json(['error' => $errorMessage], 500);
            }
        } catch (\Exception $e) {
            Log::error('Could not connect to Python backend: ' . $e->getMessage());
            return response()->json(['error' => 'Could not connect to the quiz generation service. Please ensure the Python backend is running. Error: ' . $e->getMessage()], 500);
        }
    }

    public function evaluateAnswer(Request $request)
    {
        Log::info('Processing answer evaluation request.', $request->all());
        try {
            $validatedData = $request->validate([
                'user_answer' => 'required|string',
                'question_text' => 'required|string',
                'options' => 'required|array',
                'correct_answer' => 'required|string',
                'topic' => 'required|string',
                'grade_level' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed for evaluateAnswer: ' . $e->getMessage());
            return response()->json([
                'error' => 'Validation failed: ' . $e->getMessage(),
                'details' => $e->errors()
            ], 422); // 422 Unprocessable Entity for validation errors
        }

        try {
            $response = Http::timeout(180)->post('http://127.0.0.1:5001/evaluate-answer', [
                'user_answer' => $validatedData['user_answer'],
                'question_text' => $validatedData['question_text'],
                'options' => $validatedData['options'],
                'correct_answer' => $validatedData['correct_answer'],
                'topic' => $validatedData['topic'],
                'grade_level' => $validatedData['grade_level'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $feedback = $data['feedback'] ?? 'No feedback generated.';

                // Post-process feedback for correct answers to ensure exact phrasing
                if (isset($validatedData['user_answer']) && isset($validatedData['correct_answer'])) {
                    $userAnswerCleaned = strtoupper(trim($validatedData['user_answer']));
                    $correctAnswerCleaned = strtoupper(trim($validatedData['correct_answer']));

                    if ($userAnswerCleaned === $correctAnswerCleaned) {
                        $feedback = 'Your answer is correct! Excellent work.';
                    }
                }

                Log::info('Answer evaluation feedback received from Python backend.');
                return response()->json(['feedback' => $feedback]);
            } else {
                $errorMessage = 'Failed to get answer evaluation from API.' . ($response->json()['error'] ?? $response->body());
                Log::error('API evaluation call failed: ' . $errorMessage);
                return response()->json(['error' => $errorMessage], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Could not connect to Python backend for evaluation: ' . $e->getMessage());
            return response()->json(['error' => 'Could not connect to the answer evaluation service. Please ensure the Python backend is running. Error: ' . $e->getMessage()], 500);
        }
    }

    public function downloadContent(Request $request)
    {
        Log::info('Processing download request.', $request->all());
        $content = $request->input('content');
        $filename = $request->input('filename', 'download');
        $format = $request->input('format', 'txt'); // 'txt' or 'pdf'
        $topicName = $request->input('topic_name');

        // Sanitize the topic name for use in a filename
        if (!empty($topicName)) {
            $sanitizedTopicName = Str::slug($topicName, '_');
            $filename = $sanitizedTopicName . '_' . $filename;
        }

        if (empty($content)) {
            Log::warning('Attempted to download empty content.');
            return back()->withErrors(['download_error' => 'No content to download.']);
        }

        if ($format === 'txt') {
            $headers = [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.txt"',
            ];
            Log::info(sprintf('Serving %s as text file.', $filename));
            return Response::make($content, 200, $headers);
        } elseif ($format === 'pdf') {
            try {
                Log::info(sprintf('Requesting PDF generation from Python backend for %s.', $filename));
                $response = Http::post('http://127.0.0.1:5001/generate-pdf', [
                    'content' => $content,
                    'filename' => $filename,
                ]);

                if ($response->successful()) {
                    Log::info(sprintf('PDF received from Python backend for %s.', $filename));
                    return Response::make($response->body(), 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"',
                    ]);
                } else {
                    $errorMessage = 'Failed to generate PDF from Python API.' . ($response->json()['error'] ?? '');
                    Log::error('PDF API call failed: ' . $errorMessage);
                    return back()->withErrors(['download_error' => $errorMessage]);
                }
            } catch (\Exception $e) {
                Log::error('Could not connect to PDF generation service: ' . $e->getMessage());
                return back()->withErrors(['download_error' => 'Could not connect to PDF generation service. Error: ' . $e->getMessage()]);
            }
        }

        Log::error(sprintf('Unsupported download format requested: %s', $format));
        return back()->withErrors(['download_error' => 'Unsupported download format.']);
    }

    public function chat(Request $request)
    {
        Log::info('Processing chat request.', $request->all());
        try {
            $validatedData = $request->validate([
                'user_query' => 'required|string',
                'topic' => 'required|string',
                'grade_level' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed for chat: ' . $e->getMessage());
            return response()->json([
                'error' => 'Validation failed: ' . $e->getMessage(),
                'details' => $e->errors()
            ], 422); // 422 Unprocessable Entity for validation errors
        }

        try {
            // The /chat endpoint in Python expects the user_query in the 'topic' field of QuizRequest
            // and num_questions as a hacky way to pass the actual user query as it reuses QuizRequest model.
            // We also send the actual topic and grade_level for context.
            $response = Http::timeout(180)->post('http://127.0.0.1:5001/chat', [
                'topic' => $validatedData['user_query'], // User's actual query
                'grade_level' => $validatedData['grade_level'],
                'num_questions' => 1, // Dummy value since it's not a quiz generation request
                'original_topic_context' => $validatedData['topic'], // Original quiz topic for context
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $aiResponse = $data['response'] ?? 'No response generated.';
                Log::info('AI chat response received from Python backend.');
                return response()->json(['response' => $aiResponse]);
            } else {
                $errorMessage = 'Failed to get chat response from API.' . ($response->json()['error'] ?? $response->body());
                Log::error('API chat call failed: ' . $errorMessage);
                return response()->json(['error' => $errorMessage], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Could not connect to Python backend for chat: ' . $e->getMessage());
            return response()->json(['error' => 'Could not connect to the chat service. Please ensure the Python backend is running. Error: ' . $e->getMessage()], 500);
        }
    }
}
