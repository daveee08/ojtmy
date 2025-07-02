<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TongueTwistController extends Controller
{
    public function showForm()
    {
        return view('TongueTwist', [
            'tongueTwister' => '',
            'topic' => '',
            'grade' => 'Pre-K', // Set a default grade to match the first option
        ]);
    }

    public function generateTongueTwister(Request $request)
    {
        $request->validate([
            'topic' => 'required|string',
            'grade' => 'required|string',
        ]);

        $topic = $request->input('topic');
        $grade = $request->input('grade');
        $tongueTwister = '';

        try {
            $response = Http::timeout(120)->post('http://127.0.0.1:5002/generate-tongue-twister', [
                'topic' => $topic,
                'grade_level' => $grade,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $tongueTwister = $responseData['tongue_twister'] ?? 'Error: Could not retrieve tongue twister.';
                return response()->json(['tongue_twister' => $tongueTwister]);
            } else {
                $responseData = $response->json();
                $errorMessage = $responseData['detail'] ?? ($responseData['error'] ?? 'Failed to generate tongue twister from API.');
                Log::error('Tongue Twister API Error: ' . $errorMessage);
                return response()->json(['error' => 'Error: ' . $errorMessage], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Could not connect to Python backend for tongue twister: ' . $e->getMessage());
            return response()->json(['error' => 'Error: Could not connect to the tongue twister generation service. Please ensure the Python backend is running. Error: ' . $e->getMessage()], 500);
        }
    }
}
