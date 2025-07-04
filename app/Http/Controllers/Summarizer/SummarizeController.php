<?php

namespace App\Http\Controllers\Summarizer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SummarizeController extends Controller
{
    public function index()
    {
        // Get the Summarizer agent by name
        $agent = DB::table('agents')->where('agent', 'Summarizer')->first();

        // If not found, handle it gracefully
        if (!$agent) {
            abort(404, 'Summarizer agent not found.');
        }

        // Get the associated parameter inputs
        $parameters = DB::table('parameter_inputs')
            ->where(column: 'agent_id', $agent->id)
            ->get();

        return view('TextSummarizer.summarize', compact('parameters'));
    }

    public function summarize(Request $request)
    {
        $validated = $request->validate([
            'conditions' => 'required|string',
            'input_text' => 'nullable|string',
            'pdf' => 'nullable|mimes:pdf|max:10240',
        ]);

        $multipart = [
            [
                'name' => 'conditions',
                'contents' => $validated['conditions'],
            ],
            [
                'name' => 'text',
                'contents' => $validated['input_text'] ?? '',
            ],
        ];

        if ($request->hasFile('pdf')) {
            $multipart[] = [
                'name' => 'pdf',
                'contents' => fopen($request->file('pdf')->getPathname(), 'r'),
                'filename' => $request->file('pdf')->getClientOriginalName(),
            ];
        }

        $response = Http::timeout(60)
            ->asMultipart()
            ->post('http://127.0.0.1:8001/summarize', $multipart);

        $summary = $response->json()['summary'] ?? 'No summary returned.';

        // Re-fetch parameters for re-rendering
        $agent = DB::table('agents')->where('name', 'Summarizer')->first();
        $parameters = DB::table('parameter_inputs')->where('agent_id', $agent->id)->get();

        return view('TextSummarizer.summarize', compact('summary', 'parameters'));
    }
}
