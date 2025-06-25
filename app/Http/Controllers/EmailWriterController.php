<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EmailWriterController extends Controller
{
    public function showForm()
    {
        return view('email-writer');
    }

    public function generateEmail(Request $request)
    {
        $request->validate([
            'email_input' => 'required|string',
        ]);

        $response = Http::timeout(0)->post('http://127.0.0.1:8000/generate-email', [
    'content' => $request->email_input,
]);

        if ($response->successful()) {
            return back()->with('generated_email', $response->json()['email']);
        } else {
            return back()->with('generated_email', '⚠️ Failed to generate email. Please try again.');
        }
    }
}
