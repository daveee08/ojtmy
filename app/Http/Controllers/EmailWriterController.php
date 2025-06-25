<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EmailWriterController extends Controller
{
    /**
     * Show the email writer form.
     */
    public function showForm()
    {
        return view('email-writer');
    }

    /**
     * Handle form submission and send request to FastAPI.
     */
    public function generateEmail(Request $request)
    {
        // Validate user input
        $request->validate([
            'email_input' => 'required|string',
        ]);

        try {
            // Send POST request to FastAPI
            $response = Http::timeout(30)->asForm()->post('http://192.168.50.238:8001/generate-email', [
                'content' => $request->email_input,
]);


            // Check if request was successful
            if ($response->successful() && isset($response['email'])) {
                return back()->with('generated_email', $response['email']);
            }

            // If FastAPI didn't return success
            return back()->with('generated_email', '⚠️ Failed to generate email. Please try again.');
        } catch (\Exception $e) {
            // Catch errors like timeout or connection failure
            return back()->with('generated_email', '⚠️ Backend error: ' . $e->getMessage());
        }
    }
}
