<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ThankYouNoteController extends Controller
{
    /**
     * Show the thank you note form.
     */
    public function showForm()
    {
        return view('thankyou-note');
    }

    /**
     * Handle generation of the thank you note via FastAPI.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        $response = Http::asForm()->post('http://127.0.0.1:8001/generate-thankyou', [
            'reason' => $request->reason,
        ]);

        if ($response->successful()) {
            return back()->with('thank_you_note', $response->json()['note']);
        } else {
            return back()->with('thank_you_note', '⚠️ Failed to generate note. Please try again.');
        }
    }
}
