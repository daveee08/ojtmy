<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Http, Log, Auth, DB};
use App\Models\{Message, ParameterInput};

class TutorController extends Controller
{
    public function showForm(Request $request)
    {
        $selectedThread = $request->query('thread_id');

        $threads = Message::where('user_id', Auth::id())
            ->whereColumn('id', 'message_id')
            ->latest()
            ->get();

        $history = $selectedThread
            ? Message::where('message_id', $selectedThread)->orderBy('created_at')->get()->map(fn($m) => [
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
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'nullable|string',
            'input_type' => 'required|in:topic,pdf',
            'topic' => 'nullable|string',
            'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
            'add_cont' => 'nullable|string',
            'message_id' => 'nullable|integer',
        ]);

        $gradeLevel = $validated['grade_level']
            ?? ParameterInput::where('parameter_id', 1)->where('agent_id', 1)->whereNotNull('input')->latest()->value('input')
            ?? Auth::user()->grade_level;

        $parameterInput = ParameterInput::firstOrCreate([
            'input' => $gradeLevel,
            'agent_id' => 1,
            'parameter_id' => 1
        ]);

        $newMessage = $validated['topic'] ?? '[PDF Upload]';
        if (!empty($validated['add_cont'])) {
            $newMessage .= "\n\nAdditional Context:\n" . $validated['add_cont'];
        }

        DB::beginTransaction();

        $human = Message::create([
            'agent_id' => 1,
            'user_id' => Auth::id(),
            'sender' => 'human',
            'topic' => $newMessage,
            'grade_level' => $gradeLevel,
            'parameter_inputs' => $parameterInput->id,
            'message_id' => 0,
        ]);

        $human->update(['message_id' => $validated['message_id'] ?? $human->id]);

        $priorMessages = Message::where('message_id', $human->message_id)
            ->orderBy('created_at')
            ->pluck('topic')
            ->implode("\n");

        $finalTopic = "Prior Conversation Summary:\n{$priorMessages}\n\nStudent's Follow-up:\n{$newMessage}";

        $mode = Message::where('message_id', $validated['message_id'] ?? 0)->exists() ? 'chat' : 'manual';

        $multipartData = [
            ['name' => 'grade_level', 'contents' => $gradeLevel],
            ['name' => 'input_type', 'contents' => $validated['input_type']],
            ['name' => 'topic', 'contents' => $finalTopic],
            ['name' => 'add_cont', 'contents' => ''],
            ['name' => 'mode', 'contents' => $mode],
            ['name' => 'user_id', 'contents' => Auth::id()],
            ['name' => 'history', 'contents' => json_encode([])],
        ];

        if ($request->hasFile('pdf_file')) {
            $pdf = $request->file('pdf_file');
            $multipartData[] = [
                'name'     => 'pdf_file',
                'contents' => fopen($pdf->getPathname(), 'r'),
                'filename' => $pdf->getClientOriginalName(),
                'headers'  => ['Content-Type' => $pdf->getMimeType()],
            ];
        }

        $response = Http::timeout(0)->asMultipart()->post('http://127.0.0.1:5001/tutor', $multipartData);

        if ($response->failed()) {
            DB::rollBack();
            Log::error('Python API call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return response()->json(['error' => 'Python API failed'], 500);
        }

        $output = $response->json()['output'] ?? 'No output';

        Message::create([
            'user_id' => Auth::id(),
            'agent_id' => 1,
            'sender' => 'ai',
            'topic' => $output,
            'grade_level' => $gradeLevel,
            'parameter_inputs' => $parameterInput->id,
            'message_id' => $human->message_id,
        ]);

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
