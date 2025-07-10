<?php

namespace App\Http\Controllers\Summarizer;

use App\Http\Controllers\BackendServiceController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Http, Log, Auth, DB};
use App\Models\{Message, ParameterInput};

class SummarizeController extends BackendServiceController
{
    public function index(Request $request)
    {
        $selectedThread = $request->query('thread_id');

        $threads = \App\Models\Message::where('user_id', auth()->id())
            ->whereColumn('id', 'message_id')
            ->where('agent_id', 3)
            ->orderByDesc('created_at')
            ->get();

        $history = $selectedThread
            ? \App\Models\Message::where('message_id', $selectedThread)->orderBy('created_at')->get()->map(fn($m) => [
                'role' => $m->sender,
                'content' => $m->topic,
                'id' => $m->id
            ])
            : collect();

        return view('TextSummarizer.summarize', [
            'history' => $history,
            'threads' => $threads,
            'activeThread' => $selectedThread
        ]);
    }

    public function summarize(Request $request)
    {
        $validated = $request->validate([
            'summary_instructions' => 'required|string',
            'input_text' => 'nullable|string',
            'pdf' => 'nullable|mimes:pdf|max:10240',
            'message_id' => 'nullable|integer',
        ]);

        $agent = $this->resolveAgent('summarizer');
        $paramInputs = $this->resolveAllParameterInputs($agent, $validated);

        $topic = $validated['input_text'] ?? '[PDF Upload]';
        if (!empty($validated['add_cont'])) {
            $topic .= "\n\nAdditional Context:\n" . $validated['add_cont'];
        }


        DB::beginTransaction();

        $human = $this->createHumanMessage($agent->id, Auth::id(), $topic, $paramInputs, $validated['message_id'] ?? null);
        $prior = $this->buildPriorConversation($human->message_id);
        $mode = Message::where('message_id', $validated['message_id'] ?? 0)->exists() ? 'chat' : 'manual';
        
        $multipart = [
            [
                'name' => 'summary_instructions',
                'contents' => $validated['summary_instructions'],
            ],
            [
                'name' => 'text',
                'contents' => $validated['input_text'] ?? '',
            ],
            ['name' => 'mode', 'contents' => $mode],
            ['name' => 'user_id', 'contents' => Auth::id()],
            ['name' => 'history', 'contents' => json_encode([])],
            ['name' => 'message_id', 'contents' => $human->message_id],
        ];

        if ($request->hasFile('pdf')) {
            $multipart[] = [
                'name' => 'pdf',
                'contents' => fopen($request->file('pdf')->getPathname(), 'r'),
                'filename' => $request->file('pdf')->getClientOriginalName(),
            ];
        }
        

        $response = $this->sendToBackendAPI($multipart, 'http://127.0.0.1:5001/summarize');

        if ($response->failed()) {
            DB::rollBack();
            return response()->json(['error' => 'API call failed'], 500);
        }

        $output = $response->json()['summary'] ?? 'No output';  

        // If $output is a JSON string, decode it
        if (is_string($output) && preg_match('/^\s*\{.*\}\s*$/s', $output)) {
            $decoded = json_decode($output, true);
            if (is_array($decoded)) {
                if (isset($decoded['summary'])) {
                    $output = $decoded['summary'];
                } elseif (isset($decoded['output'])) {
                    $output = $decoded['output'];
                }
            }
        }

        $this->createAIMessage($output, $agent->id, $paramInputs, $human->message_id);

        DB::commit();
        
        Log::info('Summarizer API output:', ['output' => $output]);


        return response()->json([
            'message' => $output,
            'message_id' => $human->message_id
        ]);

        // $summary = $response->json()['summary'] ?? 'No summary returned.';

        // return view('TextSummarizer.summarize', compact('summary'));

        
    }
}