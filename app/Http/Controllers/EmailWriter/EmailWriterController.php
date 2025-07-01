<?php

namespace App\Http\Controllers\EmailWriter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EmailWriterController extends Controller
{
    public function show()
    {
        return view('EmailWriter.email-writer');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'email_input' => 'required|string',
        ]);

        $response = Http::asForm()->post('http://127.0.0.1:8001/generate-email', [
            'content' => $request->email_input,
        ]);

        if ($response->successful()) {
            return back()->with('generated_email', $response->json()['email']);
        } else {
            return back()->with('generated_email', '⚠️ Failed to generate email. Please try again.');
        }
    }
}
