<?php

<<<<<<< HEAD
namespace App\Http\Controllers\Quoteoftheday;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth; // Corrected: Removed "->"
use Illuminate\Support\Facades\Log;   // Corrected: Removed "->"
use App\Http\Controllers\Controller; // Corrected: Changed "->" to "\"

class QOTDController extends Controller
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
        
        // Make an HTTP GET request to your QOTD backend's sessions endpoint.
        // --- PORT CHANGED TO 5000 ---
        $response = Http::get("http://127.0.0.1:5000/qotd-sessions/{$userId}");

        // Return the JSON response directly from the backend.
        return response()->json($response->json());
    }

    /**
     * Displays the form for generating a Quote of the Day.
     *
     * @return \Illuminate\View\View
     */
    public function showForm()
    {
        // This view is where the user inputs topic and grade.
        return view('Quote of the Day.QOTD');
    }

    /**
     * Generates a Quote of the Day based on user input.
     * This method calls the FastAPI service, saves the interaction to the database,
     * and then redirects to the unified chat history page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function generateQuote(Request $request)
    {
        // Set an infinite time limit for this script execution,
        // useful if the backend process is long-running (e.g., LLM generation).
        set_time_limit(0); 

        // Validate the incoming request data to ensure required fields are present.
        $validated = $request->validate([
=======
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class QOTDController extends Controller
{
    public function showForm()
    {
        return view('layouts.QOTD');
    }

    public function generateQuote(Request $request)
    {
        $request->validate([
>>>>>>> 074e8dffacfbb9951b315ed18c886c8ce4f55b18
            'topic' => 'required|string',
            'grade' => 'required|string',
        ]);

<<<<<<< HEAD
        $topic = $validated['topic'];
        $grade = $validated['grade'];
        // Get the authenticated user's ID. Default to 1 if not authenticated.
        $userId = Auth::id() ?? 1; 

        try {
            // Make an HTTP POST request to your FastAPI service's quote generation endpoint.
            // Use timeout(0) for no timeout, allowing the FastAPI to complete its process.
            // --- PORT CHANGED TO 5000 ---
            $response = Http::timeout(0)->post('http://127.0.0.1:5000/generate-quote', [
                'topic' => $topic,
                'grade_level' => $grade,
                'user_id' => $userId, // Pass the user ID to the backend for session tracking
            ]);

            // Check if the HTTP request to FastAPI was successful (HTTP 2xx status code).
            if ($response->successful()) {
                $responseData = $response->json();
                // Log the full response from FastAPI for debugging purposes.
                Log::info('QOTD API Response:', ['response' => $responseData]);

                // Extract the message_id (which is the session_id from the database) from the response.
                $messageId = $responseData['message_id'] ?? null;

                // If a valid message_id is returned, redirect to the chat history page.
                if ($messageId) {
                    // --- REDIRECTING TO GENERAL CHAT HISTORY (Leveler's Logic) ---
                    // This assumes the /chat/history/{session_id} route is handled by ChatconversationController::showForm.
                    return redirect()->to("/chat/history/{$messageId}");
                }

                // Fallback if FastAPI was successful but did not return a message_id.
                // This scenario should ideally not happen if db_utils_final is working correctly.
                $quote = $responseData['quote'] ?? 'Error: Could not retrieve quote or message ID.'; 

            } else {
                // Log the error details if the FastAPI call failed (non-2xx status).
                Log::error('QOTD API Error:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                // Provide a user-friendly error message.
                $quote = 'Error contacting quote generation service: ' . ($response->json()['error'] ?? 'Unknown error');
                // Redirect back to the form with the error message.
                return back()->withErrors(['error' => $quote]);
            }
        } catch (\Exception $e) {
            // Catch any exceptions that occur during the HTTP request (e.g., network issues, FastAPI not running).
            Log::error('QOTD connection error: ' . $e->getMessage());
            $quote = 'Error: Could not connect to the quote generation service.';
            // Redirect back to the form with the connection error.
            return back()->withErrors(['error' => $quote]);
        }

        // This return statement is a final fallback and should only be reached if
        // the FastAPI call failed and no redirection occurred.
        return view('Quote of the Day.QOTD', [
=======
        $topic = $request->input('topic');
        $grade = $request->input('grade');

        try {
            $response = Http::post('http://127.0.0.1:5006/generate-quote', [
                'topic' => $topic,
                'grade_level' => $grade,
            ]);

            if ($response->successful()) {
                $quoteData = $response->json();
                $quote = $quoteData['quote'] ?? 'Error: Could not retrieve quote.';
            } else {
                $quote = 'Error contacting quote generation service.';
                // Log the error response for debugging
                \Illuminate\Support\Facades\Log::error('QOTD API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            $quote = 'Error: Could not connect to the quote generation service.';
            \Illuminate\Support\Facades\Log::error('QOTD connection error: ' . $e->getMessage());
        }

        return view('layouts.QOTD', [
>>>>>>> 074e8dffacfbb9951b315ed18c886c8ce4f55b18
            'quote' => $quote,
            'topic' => $topic,
            'grade' => $grade,
        ]);
    }

<<<<<<< HEAD
    /**
     * Handles the download of the generated quote in TXT or PDF format.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function downloadQuote(Request $request)
    {
        set_time_limit(0); 

=======
    public function downloadQuote(Request $request)
    {
>>>>>>> 074e8dffacfbb9951b315ed18c886c8ce4f55b18
        $request->validate([
            'content' => 'required|string',
            'filename' => 'required|string',
            'format' => 'required|in:txt,pdf',
        ]);

        $content = $request->input('content');
        $filename = $request->input('filename');
        $format = $request->input('format');

        if ($format === 'txt') {
            return response($content)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '.txt"');
        } elseif ($format === 'pdf') {
            try {
<<<<<<< HEAD
                // --- PORT CHANGED TO 5000 ---
                $response = Http::timeout(0)->post('http://127.0.0.1:5000/generate-pdf', [
=======
                $response = Http::post('http://127.0.0.1:5006/generate-pdf', [
>>>>>>> 074e8dffacfbb9951b315ed18c886c8ce4f55b18
                    'content' => $content,
                    'filename' => $filename,
                ]);

                if ($response->successful()) {
                    return response($response->body())
                        ->header('Content-Type', 'application/pdf')
                        ->header('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');
                } else {
<<<<<<< HEAD
                    Log::error('QOTD PDF API Error:', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return back()->withErrors(['download' => 'Error generating PDF: ' . ($response->json()['error'] ?? 'Unknown error')]);
                }
            } catch (\Exception $e) {
                Log::error('QOTD PDF connection error: ' . $e->getMessage());
=======
                    return back()->withErrors(['download' => 'Error generating PDF: ' . ($response->json()['error'] ?? 'Unknown error')]);
                }
            } catch (\Exception $e) {
>>>>>>> 074e8dffacfbb9951b315ed18c886c8ce4f55b18
                return back()->withErrors(['download' => 'Error connecting to PDF generation service: ' . $e->getMessage()]);
            }
        }
    }
}
