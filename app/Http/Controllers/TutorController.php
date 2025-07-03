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
            // ->where('agent', 'tutor') // Ensure we only get threads for this agent
            ->orderByDesc('created_at')
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

        // --- Get agent and parameter dynamically ---
        $agent = DB::table('agents')->where('agent', 'tutor')->first();

        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $parameter = DB::table('agent_parameters')
            ->where('agent_id', $agent->id)
            ->where('parameter', 'grade_level')
            ->first();

        if (!$parameter) {
            return response()->json(['error' => 'Parameter not found'], 404);
        }

        // --- Fallback to latest input or user's stored grade_level ---
        $gradeLevel = $validated['grade_level']
            ?? ParameterInput::where('parameter_id', $parameter->id)->where('agent_id', $agent->id)->whereNotNull('input')->latest()->value('input')
            ?? Auth::user()->grade_level;

        // --- Dynamically resolve ParameterInput ---
        $parameterInput = ParameterInput::firstOrCreate([
            'input' => $gradeLevel,
            'agent_id' => $agent->id,
            'parameter_id' => $parameter->id
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
            ['name' => 'mode', 'contents' => $mode], // refer to the tutor_agent.py para giunsa pag gamit sa mode na gi pass dani
            ['name' => 'user_id', 'contents' => Auth::id()],
            ['name' => 'history', 'contents' => json_encode([])],
            ['name' => 'message_id', 'contents' => $human->message_id],
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

        Log::info('API Request', [
            'grade_level' => $gradeLevel,
            'input_type' => $validated['input_type'],
            'topic' => $finalTopic,
            'mode' => $mode,
            'user_id' => Auth::id(),
        ]);

        Log::info('API Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

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



// <?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
// use App\Models\Message;
// use App\Models\ParameterInput;

// class TutorController extends Controller
// {
//     public function showForm(Request $request)
//     {
//         $selectedThread = $request->query('thread_id');

//         // Get all top-level threads (where id == message_id)
//         $threads = Message::where('user_id', Auth::id())
//             ->whereColumn('id', 'message_id')
//             ->whereHas('agent', function ($query) {
//                 $query->where('agent', 'tutor');
//             })
//             ->orderByDesc('created_at')
//             ->get();

//         $history = collect();

//         if ($selectedThread) {
//             $history = Message::where('message_id', $selectedThread)
//                 ->orderBy('created_at')
//                 ->get()
//                 ->map(fn($m) => [
//                     'role' => $m->sender,
//                     'content' => $m->topic,
//                     'id' => $m->id
//                 ]);
//         }

//         return view('Conceptual Understanding.tutor', [
//             'history' => $history,
//             'threads' => $threads,
//             'activeThread' => $selectedThread
//         ]);
//     }

//     public function processForm(Request $request)
//     {
//         set_time_limit(0);

//         $validated = $request->validate([
//             'grade_level' => 'nullable|string',
//             'input_type' => 'required|in:topic,pdf',
//             'topic' => 'nullable|string',
//             'pdf_file' => 'nullable|file|mimes:pdf|max:5120',
//             'add_cont' => 'nullable|string',
//             'message_id' => 'nullable|integer',
//         ]);

//         $gradeParamInput = \App\Models\ParameterInput::where('parameter_id', 1)
//             ->where('agent_id', 1)
//             ->whereNotNull('input')
//             ->latest()
//             ->first();

//         $gradeLevel = $gradeParamInput->input ?? Auth::user()->grade_level;


//         $newMessage = $validated['topic'] ?? '[PDF Upload]';
//         if (!empty($validated['add_cont'])) {
//             $newMessage .= "\n\nAdditional Context:\n" . $validated['add_cont'];
//         }

//         Log::info("grade_level", ['grade_level' => $gradeLevel]);
//         Log::info("grade_level that is passed", ['grade_level' => $validated['grade_level']]);



//         // Insert or reuse ParameterInput
//         $parameterInput = ParameterInput::firstOrCreate([
//             'input' => $validated['grade_level'] ?? $gradeLevel,
//             'agent_id' => 1,
//             'parameter_id' => 1
//         ]);

//         $parentMessageId = $validated['message_id'] ?? null;
//         $isNewThread = !$parentMessageId;

//         // Insert or reuse ParameterInput
//         $parameterInput = ParameterInput::firstOrCreate([
//             'input' => $validated['grade_level'] ?? $gradeLevel,
//             'agent_id' => 1,
//             'parameter_id' => 1
//         ]);

//         DB::beginTransaction();

//         // Create the human message
//         $human = Message::create([
//             'agent_id' => 1,
//             'user_id' => Auth::id(),
//             'sender' => 'human',
//             'topic' => $newMessage,
//             'grade_level' => $gradeLevel,
//             'parameter_inputs' => $parameterInput->id,
//             'message_id' => 0, // temporary, updated below
//         ]);

//         // If new thread, set message_id = own ID (self-reference)
//         if ($isNewThread) {
//             $human->message_id = $human->id;
//             $human->save();
//         } else {
//             // Follow-up → assign existing thread ID
//             $human->message_id = $parentMessageId;
//             $human->save();
//         }

//         // Build context summary
//         $priorMessages = Message::where('message_id', $human->message_id)
//             ->orderBy('created_at')
//             ->get()
//             ->map(fn($msg) => $msg->topic)
//             ->implode("\n");

//         $finalTopic = "Prior Conversation Summary:\n" . $priorMessages . "\n\nStudent's Follow-up:\n" . $newMessage;

//         // $mode = count($priorMessages) > 1 ? 'chat' : 'manual';
//         // Log::info('Mode determined:', ['mode' => $mode]);

        
//         $existingMessages = Message::where('message_id', $parentMessageId ?? 0)->count();
//         $mode = $existingMessages > 0 ? 'chat' : 'manual';

//         // API call to FastAPI
//         $multipartData = [
//             ['name' => 'grade_level', 'contents' => $gradeLevel],
//             ['name' => 'input_type', 'contents' => $validated['input_type']],
//             ['name' => 'topic', 'contents' => $finalTopic],
//             ['name' => 'add_cont', 'contents' => ''],
//             ['name' => 'mode', 'contents' => $mode], // Changed to 'manual' for single message context
//             ['name' => 'user_id', 'contents' => Auth::id()],
//             ['name' => 'history', 'contents' => json_encode([])],
//         ];

//         if ($request->hasFile('pdf_file')) {
//             $pdf = $request->file('pdf_file');
//             $multipartData[] = [
//                 'name'     => 'pdf_file',
//                 'contents' => fopen($pdf->getPathname(), 'r'),
//                 'filename' => $pdf->getClientOriginalName(),
//                 'headers'  => ['Content-Type' => $pdf->getMimeType()],
//             ];
//         }

//         $response = Http::timeout(0)->asMultipart()->post('http://127.0.0.1:5001/tutor', $multipartData);

//         if ($response->failed()) {
//             DB::rollBack();

//             Log::error('Python API call failed', [
//                 'status' => $response->status(),
//                 'body' => $response->body(),
//                 'request' => [
//                     'grade_level' => $gradeLevel,
//                     'input_type' => $validated['input_type'],
//                     'topic' => $finalTopic,
//                     'user_id' => Auth::id(),
//                 ],
//             ]);

//             return response()->json([
//                 'error' => 'Python API failed',
//                 'status' => $response->status(),
//                 'details' => $response->body(),
//             ], 500);
//         }


//         $output = $response->json()['output'] ?? 'No output';

//         Message::create([
//             'user_id' => Auth::id(),
//             'agent_id' => 1,
//             'sender' => 'ai',
//             'topic' => $output,
//             'grade_level' => $gradeLevel,
//             'parameter_inputs' => $parameterInput->id,
//             'message_id' => $human->message_id,
//         ]);

//         DB::commit();

//         return response()->json([
//             'message' => $output,
//             'message_id' => $human->message_id
//         ]);
//     }

//     public function clearHistory(Request $request)
//     {
//         Message::where('user_id', Auth::id())->delete();
//         session()->forget('grade_level');
//         return redirect()->back()->with('status', 'Conversation history cleared.');
//     }
// }
