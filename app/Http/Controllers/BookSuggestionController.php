<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BookSuggestionController extends Controller
{
    public function index()
    {
        return view('CkBookSuggestion');
    }

    public function getSuggestions(Request $request)
    {
        $request->validate([
            'interests' => 'nullable|string', // Changed to nullable
            'grade_level' => 'nullable|string' // Optional grade level to match the dropdown
        ]);
    
        $interests = $request->input('interests');
        $gradeLevel = $request->input('grade_level');
    
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('http://127.0.0.1:5005/suggest', [
                'json' => [
                    'interests' => $interests,
                    'grade_level' => $gradeLevel ?: null
                ]
            ]);
    
            $suggestions = json_decode($response->getBody(), true);
    
            return response()->json([
                'success' => true,
                'suggestions' => $suggestions['suggestion'] ?? $suggestions
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get book suggestions: ' . $e->getMessage()
            ], 500);
        }
    }
}