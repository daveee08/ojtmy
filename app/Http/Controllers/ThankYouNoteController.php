<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ThankYouNoteController extends Controller
{
    public function showForm()
    {
        return view('thankyounote'); // Make sure this Blade file exists in resources/views
    }

    public function processForm(Request $request)
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
