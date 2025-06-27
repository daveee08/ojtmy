<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ThankYouNoteController extends Controller
{
    /**
     * Show the form for the Thank You Note
     */
    public function showForm()
    {
        return view('thankyounote');  // Ensure this Blade file exists in resources/views
    }

    /**
     * Generate the Thank You Note
     */
    public function generate(Request $request)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        // Send the reason to FastAPI endpoint
        $response = Http::asForm()->post('http://127.0.0.1:8001/generate-thankyou', [
            'reason' => $request->reason,
        ]);

        // Handle the response
        if ($response->successful()) {
            return back()->with('thankyou_note', $response->json()['thank_you_note']);
        } else {
            return back()->with('error', 'Failed to generate thank-you note.');
        }
    }
}
