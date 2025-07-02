<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TongueTwistController extends Controller
{
    public function showForm()
    {
        return view('layouts.TongueTwist');
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
            $response = Http::post('http://127.0.0.1:5002/generate-tongue-twister', [
                'topic' => $topic,
                'grade_level' => $grade,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $tongueTwister = $responseData['tongue_twister'] ?? 'Error: Could not retrieve tongue twister.';
            } else {
                $tongueTwister = 'Error contacting tongue twister generation service.';
                \Illuminate\Support\Facades\Log::error('Tongue Twister API Error:' . $response->body());
            }
        } catch (\Exception $e) {
            $tongueTwister = 'Error: Could not connect to the tongue twister generation service.';
            \Illuminate\Support\Facades\Log::error('Tongue Twister connection error:' . $e->getMessage());
        }

        return view('layouts.TongueTwist', [
            'tongueTwister' => $tongueTwister,
            'topic' => $topic,
            'grade' => $grade,
        ]);
    }
}
