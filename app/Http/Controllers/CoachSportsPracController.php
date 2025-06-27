<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CoachSportsPracController extends Controller
{
    public function showForm()
    {
        return view('CoachSportsPrac');
    }

    public function generatePracticePlan(Request $request)
    {
        $request->validate([
            'grade' => 'required|string',
            'length' => 'required|string',
            'sport' => 'required|string',
            'customization' => 'nullable|string',
        ]);

        $grade = $request->input('grade');
        $length = $request->input('length');
        $sport = $request->input('sport');
        $customization = $request->input('customization');
        $practicePlan = '';

        try {
            $response = Http::post('http://127.0.0.1:5003/generate-practice-plan', [
                'grade_level' => $grade,
                'length_of_practice' => $length,
                'sport' => $sport,
                'additional_customization' => $customization,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $practicePlan = $responseData['practice_plan'] ?? 'Error: Could not retrieve practice plan.';
            } else {
                $practicePlan = 'Error contacting practice plan generation service.';
                \Illuminate\Support\Facades\Log::error('CoachSportsPrac API Error:' . $response->body());
            }
        } catch (\Exception $e) {
            $practicePlan = 'Error: Could not connect to the practice plan generation service.';
            \Illuminate\Support\Facades\Log::error('CoachSportsPrac connection error:' . $e->getMessage());
        }

        // Format the practice plan: replace any sequence of asterisks at the beginning of a line with a single bullet point
        // and ensure the bullet symbol doesn't turn into '?' by using a standard Unicode bullet.
        $practicePlanFormatted = preg_replace('/^\s*\*+\s*/m', '• ', $practicePlan);
        // Replace common markdown bold/italic for display, can be stripped or handled differently for download
        $practicePlanFormatted = str_replace(['**', '*'], ['', ''], $practicePlanFormatted); // Remove bold/italic markdown for display

        return view('CoachSportsPrac', [
            'practicePlan' => $practicePlan, // Keep original for download content
            'practicePlanFormatted' => $practicePlanFormatted,
            'grade' => $grade,
            'length' => $length,
            'sport' => $sport,
            'customization' => $customization,
        ]);
    }

    public function downloadPracticePlan(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'filename' => 'required|string',
            'format' => 'required|in:txt,pdf',
        ]);

        $content = $request->input('content');
        $filename = $request->input('filename');
        $format = $request->input('format');

        // Format the content for download: replace any sequence of asterisks at the beginning of a line with a single bullet point
        $formattedContentForDownload = preg_replace('/^\s*\*+\s*/m', '• ', $content);

        if ($format === 'txt') {
            return response($formattedContentForDownload)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '.txt"');
        } elseif ($format === 'pdf') {
            try {
                // Call the Python FastAPI for PDF generation
                $response = Http::post('http://127.0.0.1:5003/generate-pdf', [
                    'content' => $formattedContentForDownload,
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
