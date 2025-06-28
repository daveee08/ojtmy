<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EmailResponderController extends Controller
{
    public function showForm()
    {
        Log::info('Displaying Email Responder form.');
        return view('EmailResponder');
    }

    public function processEmail(Request $request)
    {
        Log::info('Processing email generation request.', $request->all());
        try {
            $validatedData = $request->validate([
                'author_name' => 'required|string|max:255',
                'email_responding_to' => 'required|string',
                'communication_intent' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed for processEmail: ' . $e->getMessage());
            return response()->json([
                'error' => 'Validation failed: ' . $e->getMessage(),
                'details' => $e->errors()
            ], 422); // 422 Unprocessable Entity for validation errors
        }

        try {
            $response = Http::timeout(180)->post('http://127.0.0.1:5001/generate-email-response', [
                'author_name' => $validatedData['author_name'],
                'email_responding_to' => $validatedData['email_responding_to'],
                'communication_intent' => $validatedData['communication_intent'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $generatedEmail = $data['generated_email'] ?? 'No email generated.';
                Log::info('Email response received from Python backend.');
                return response()->json(['generated_email' => $generatedEmail]);
            } else {
                $errorMessage = 'Failed to generate email from API.' . ($response->json()['error'] ?? $response->body());
                Log::error('API call failed: ' . $errorMessage);
                return response()->json(['error' => $errorMessage], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Could not connect to Python backend for email generation: ' . $e->getMessage());
            return response()->json(['error' => 'Could not connect to the email generation service. Please ensure the Python backend is running. Error: ' . $e->getMessage()], 500);
        }
    }
}
