<?php

namespace App\Http\Controllers\StudyHabits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
class StudyHabitsController extends Controller
{
    public function showForm()
    {
        return view('Study Habits.studyhabits');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);
        
        $validated = $request->validate([
            'grade_level' => 'required|string',
            'goal'        => 'required|string',
        ]);

        $response = Http::timeout(0)->post('http://127.0.0.1:5001/studyhabits', [
            'grade_level' => $validated['grade_level'],
            'goal'        => $validated['goal'],
        ]);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Agent failed. Try again.'])->withInput();
        }

        return view('Study Habits.studyhabits', [
            'plan' => $response['plan'] ?? 'No response generated.',
        ]);
    }
}
