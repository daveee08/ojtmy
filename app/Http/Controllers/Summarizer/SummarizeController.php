<?php

namespace App\Http\Controllers\Summarizer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Http, Auth, Log};
use App\Models\{ParameterInput, Message};

class SummarizeController extends Controller
{
    public function index()
    {
        $agent = DB::table('agents')->where('agent', 'summarizer')->first();

        if (!$agent) {
            abort(404, 'Summarizer agent not found.');
        }

        $parameters = DB::table('agent_parameters')
            ->where('agent_id', $agent->id)
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

        $agent = DB::table('agents')->where('agent', 'summarizer')->first();

        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        // Get the 'conditions' parameter definition
        $parameter = DB::table('agent_parameters')
            ->where('agent_id', $agent->id)
            ->where('parameter', 'conditions')
            ->first();

        if (!$parameter) {
            return response()->json(['error' => 'Parameter not found'], 404);
        }

        $parameterInput = ParameterInput::firstOrCreate([
            'input' => $validated['conditions'],
            'agent_id' => $agent->id,
            'parameter_id' => $parameter->id,
        ]);

        $textInput = $validated['input_text'] ?? '';

        $multipart = [
            ['name' => 'conditions', 'contents' => $validated['conditions']],
            ['name' => 'text', 'contents' => $textInput],
        ];

        if ($request->hasFile('pdf')) {
            $pdf = $request->file('pdf');
            $multipart[] = [
                'name'     => 'pdf',
                'contents' => fopen($pdf->getPathname(), 'r'),
                'filename' => $pdf->getClientOriginalName(),
                'headers'  => ['Content-Type' => $pdf->getMimeType()],
            ];
        }

        $response = Http::timeout(60)
            ->asMultipart()
            ->post($agent->endpoint, $multipart);

        Log::info('Summarizer API Request', [
            'conditions' => $validated['conditions'],
            'text' => $textInput,
            'user_id' => Auth::id(),
        ]);

        Log::info('Summarizer API Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $summary = $response->json()['summary'] ?? 'No summary returned.';

        Message::create([
            'user_id' => Auth::id(),
            'agent_id' => $agent->id,
            'sender' => 'human',
            'topic' => $textInput ?: '[PDF Upload]',
            'parameter_inputs' => $parameterInput->id,
            'message_id' => 0,
        ]);

        Message::create([
            'user_id' => Auth::id(),
            'agent_id' => $agent->id,
            'sender' => 'ai',
            'topic' => $summary,
            'parameter_inputs' => $parameterInput->id,
            'message_id' => 0,
        ]);

        $parameters = DB::table('agent_parameters')
            ->where('agent_id', $agent->id)
            ->get();

        return view('TextSummarizer.summarize', compact('summary', 'parameters'));
    }
}
