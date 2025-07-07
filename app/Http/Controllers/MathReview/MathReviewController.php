<?php

namespace App\Http\Controllers\MathReview; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class MathReviewController extends Controller
{
    public function showForm()
    {
        return view('Math Review.mathreview'); 
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'required|string',
            'number_of_problems' => 'required|integer|min:1|max:100',
            'math_content' => 'required|string',
            'additional_criteria' => 'nullable|string',
        ]);

        $multipartData = [
            [
                'name' => 'grade_level',
                'contents' => $validated['grade_level']
            ],
            [
                'name' => 'number_of_problems',
                'contents' => $validated['number_of_problems']
            ],
            [
                'name' => 'math_content',
                'contents' => $validated['math_content']
            ],
            [
                'name' => 'additional_criteria',
                'contents' => $validated['additional_criteria'] ?? ''
            ],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/mathreview', $multipartData);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        return view('Math Review.mathreview', ['response' => $response->json()['output'] ?? 'No output']);
    }
}
