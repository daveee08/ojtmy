<?php

namespace App\Http\Controllers\ThankYouNote;

use App\Http\Controllers\Controller; // ✅ Add this line
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ThankYouNoteController extends Controller
{
    /**
     * Show the form for the Thank You Note
     */
    public function showForm()
    {
        return view('ThankYouNote.thankyounote'); 
        // Make sure this exists: resources/views/ThankYouNote/thankyounote.blade.php
    }

    /**
     * Generate the Thank You Note
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
            return back()->with('thankyou_note', $response->json()['thank_you_note']);
        } else {
            return back()->with('error', 'Failed to generate thank-you note.');
        }
    }
}
