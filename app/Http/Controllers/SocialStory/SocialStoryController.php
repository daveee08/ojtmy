<?php

namespace App\Http\Controllers\SocialStory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SocialStoryController extends Controller
{
    public function showForm()
    {
        return view('SocialStory.socialstory');
    }

    public function generate(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'grade_level' => 'required|string',
            'situation' => 'required|string',
        ]);

        try {
            $response = Http::asForm()->post('http://127.0.0.1:8001/generate-socialstory', [
                'grade_level' => $request->grade_level,
                'situation' => $request->situation,
            ]);

            if ($response->successful()) {
                return back()->with('story', $response->json()['story']);
            } else {
                return back()->with('error', 'Failed to generate story. Please try again.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
