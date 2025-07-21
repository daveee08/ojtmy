<?php

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
            'topic' => 'required|string',
            'grade' => 'required|string',
        ]);

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
            'quote' => $quote,
            'topic' => $topic,
            'grade' => $grade,
        ]);
    }

    public function downloadQuote(Request $request)
    {
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
                $response = Http::post('http://127.0.0.1:5006/generate-pdf', [
                    'content' => $content,
                    'filename' => $filename,
                ]);

                if ($response->successful()) {
                    return response($response->body())
                        ->header('Content-Type', 'application/pdf')
                        ->header('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');
                } else {
                    return back()->withErrors(['download' => 'Error generating PDF: ' . ($response->json()['error'] ?? 'Unknown error')]);
                }
            } catch (\Exception $e) {
                return back()->withErrors(['download' => 'Error connecting to PDF generation service: ' . $e->getMessage()]);
            }
        }
    }
}
