<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IdeaGeneratorController extends Controller
{
    public function showForm()
    {
        return view('idea-generator');  // This should load your view for idea generation form
    }

    public function generate(Request $request)
    {
         set_time_limit(0);
        // Validate the incoming request to ensure both fields are provided
        $request->validate([
            'grade_level' => 'required|string',
            'prompt' => 'required|string',
        ]);

        try {
            // Send the POST request to FastAPI with a timeout of 300 seconds (5 minutes)
            $response = Http::asForm()->post('http://127.0.0.1:8001/generate-idea', [
                'grade_level' => $request->grade_level,
                'prompt' => $request->prompt,
            ]);

            // Check if the response was successful and return the 'idea'
            if ($response->successful()) {
                // Store the returned 'idea' in session and pass it to the view
                return back()->with('ideas', $response->json()['idea']);
            } else {
                // Handle failure in the API response
                return back()->with('error', 'Failed to generate ideas. Please try again.');
            }
        } catch (\Exception $e) {
            // Catch any exceptions and handle timeout or other errors
            return back()->with('error', 'An error occurred while generating ideas: ' . $e->getMessage());
        }
    }
}
