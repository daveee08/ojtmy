<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TeacherJokesController extends Controller
{
    public function showForm()
    {
        return view('TeacherJokes');
    }

    public function generateJoke(Request $request)
    {
        $request->validate([
            'grade' => 'required|string',
            'customization' => 'nullable|string',
        ]);

        $grade = $request->input('grade');
        $customization = $request->input('customization');
        $joke = '';

        try {
            $response = Http::post('http://127.0.0.1:5004/generate-joke', [
                'grade_level' => $grade,
                'additional_customization' => $customization,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $joke = $responseData['joke'] ?? 'Error: Could not retrieve joke.';
                return response()->json(['joke' => $joke]);
            } else {
                $joke = 'Error contacting joke generation service.';
                \Illuminate\Support\Facades\Log::error('TeacherJokes API Error:' . $response->body());
                return response()->json(['error' => $joke], $response->status() ?: 500);
            }
        } catch (\Exception $e) {
            $joke = 'Error: Could not connect to the joke generation service.';
            \Illuminate\Support\Facades\Log::error('TeacherJokes connection error:' . $e->getMessage());
            return response()->json(['error' => $joke], 500);
        }
    }
}
