<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Http, Log, Auth, DB};
use App\Models\{Message, ParameterInput};


class TestTutorController extends BackendServiceController
{
    public function showForm(Request $request)
    {
        $selectedThread = $request->query('thread_id');

        $threads = \App\Models\Message::where('user_id', auth()->id())
            ->whereColumn('id', 'message_id')
            ->where('agent_id', 1)
            ->orderByDesc('created_at')
            ->get();

        $history = $selectedThread
            ? \App\Models\Message::where('message_id', $selectedThread)->orderBy('created_at')->get()->map(fn($m) => [
                'role' => $m->sender,
                'content' => $m->topic,
                'id' => $m->id
            ])
            : collect();

        return view('Conceptual Understanding.tutor', [
            'history' => $history,
            'threads' => $threads,
            'activeThread' => $selectedThread
        ]);
    }

    public function processForm(Request $request)
    {
        $validated = $request->validate([
            'grade_level' => 'nullable|string',
            // 'writing_style' => 'nullable|string', // additional parameter example
            'input_type' => 'required|in:topic,pdf',
            'topic' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
            'add_cont' => 'nullable|string',
            'message_id' => 'nullable|integer',
        ]);

        $agent = $this->resolveAgent('tutor');
        $paramInputs = $this->resolveAllParameterInputs($agent, $validated);
        $gradeLevel = $paramInputs['grade_level']->input ?? 'unknown';

        $topic = $validated['topic'] ?? '[PDF Upload]';
        if (!empty($validated['add_cont'])) {
            $topic .= "\n\nAdditional Context:\n" . $validated['add_cont'];
        }

        DB::beginTransaction();

        $human = $this->createHumanMessage($agent->id, Auth::id(), $topic, $gradeLevel, $paramInputs, $validated['message_id'] ?? null);
        $prior = $this->buildPriorConversation($human->message_id);
        $finalTopic = "Prior Conversation Summary:\n{$prior}\n\nStudent's Follow-up:\n{$topic}";
        // $mode = $validated['message_id'] ? 'chat' : 'manual';
        $mode = Message::where('message_id', $validated['message_id'] ?? 0)->exists() ? 'chat' : 'manual';


        $multipartData = [
            ['name' => 'grade_level', 'contents' => $gradeLevel],
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'topic', 'contents' => $finalTopic],
            ['name' => 'add_cont', 'contents' => ''],
            ['name' => 'mode', 'contents' => $mode],
            ['name' => 'user_id', 'contents' => Auth::id()],
            ['name' => 'history', 'contents' => json_encode([])],
            ['name' => 'message_id', 'contents' => $human->message_id],
        ];

        if ($request->hasFile('pdf_file')) {
            $pdf = $request->file('pdf_file');
            $multipartData[] = [
                'name' => 'pdf_file',
                'contents' => fopen($pdf->getPathname(), 'r'),
                'filename' => $pdf->getClientOriginalName(),
                'headers' => ['Content-Type' => $pdf->getMimeType()],
            ];
        }

        $response = $this->sendToBackendAPI($multipartData, 'http://192.168.50.10:8002/tutor');

        if ($response->failed()) {
            DB::rollBack();
            return response()->json(['error' => 'API call failed'], 500);
        }

        $output = $response->json()['output'] ?? 'No output';

        $this->createAIMessage($output, $agent->id, $gradeLevel, $paramInputs, $human->message_id);

        DB::commit();

        return response()->json([
            'message' => $output,
            'message_id' => $human->message_id
        ]);
    }
    public function clearHistory(Request $request)
    {
        Message::where('user_id', Auth::id())->delete();
        session()->forget('grade_level');
        return redirect()->back()->with('status', 'Conversation history cleared.');
    }
}
