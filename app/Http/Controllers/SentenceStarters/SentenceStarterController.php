<?php

namespace App\Http\Controllers\SentenceStarters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentenceStarterController extends Controller
{
    /**  Display the blade form. */
    public function showForm()
    {
        return view('Sentence Starter.sentencestarter');
    }

    /**  Handle form POST. */
    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'grade_level' => 'required|string',
            'topic'       => 'required|string',
        ]);

        // Generate or retrieve message_id for chat history
        if (!session()->has('sentence_starter_message_id')) {
            $generatedId = \Illuminate\Support\Str::uuid();
            session(['sentence_starter_message_id' => $generatedId]);
        }
        $messageId = session('sentence_starter_message_id');

        $multipart = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'topic'        , 'contents' => $validated['topic']],
            ['name' => 'mode'         , 'contents' => 'manual'],
            ['name' => 'user_id'      , 'contents' => auth()->id() ?: 1],
            ['name' => 'message_id'   , 'contents' => $messageId], // Pass message_id for chat context
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:8014/sentence-starters', $multipart);

        Log::info('Sentence‑starter request', [
            'payload'         => $validated,
            'response_status' => $response->status(),
            'response_body'   => $response->body(),
        ]);

        if ($response->failed() ||
            !($json = $response->json()) ||
            !isset($json['sentence_starters'])) {

            return back()
                ->withErrors(['error' => 'Sentence‑starter agent failed.'])
                ->withInput();
        }

        // ------------------------------------------------------------------ #
        // 4. Render view with starters                                       #
        // ------------------------------------------------------------------ #
        return view('Sentence Starter.sentencestarter', [
            'output' => $json['sentence_starters'],
            'old'    => $validated,
        ]);
    }

    /* --------------------------------------------------------------------- *
     *  OPTIONAL: quick follow‑up endpoint                                   *
     *  (kept here only if you really need it; otherwise delete)             *
     * --------------------------------------------------------------------- */
    public function followUp(Request $request)
    {
        $request->validate([
            'original_topic' => 'required|string',
            'grade_level'    => 'required|string',
            'followup'       => 'required|string',
        ]);

        // Retrieve message_id for chat context
        $messageId = session('sentence_starter_message_id') ?? \Illuminate\Support\Str::uuid();

        // Use chat mode and pass followup as topic
        $multipart = [
            ['name' => 'grade_level', 'contents' => $request->grade_level],
            ['name' => 'topic'       , 'contents' => $request->followup],
            ['name' => 'mode'        , 'contents' => 'chat'],
            ['name' => 'user_id'     , 'contents' => auth()->id() ?: 1],
            ['name' => 'message_id'  , 'contents' => $messageId],
        ];

        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://127.0.0.1:8014/sentence-starters', $multipart);

        if ($response->failed() || !($json = $response->json()) || !isset($json['sentence_starters'])) {
            return back()
                ->withErrors(['error' => 'Sentence‑starter agent failed.'])
                ->withInput();
        }

        return view('Sentence Starter.sentencestarter', [
            'output' => $json['sentence_starters'],
            'old'    => [
                'grade_level'    => $request->grade_level,
                'topic'          => $request->followup,
                'original_topic' => $request->original_topic,
            ],
        ]);
    }

    public function resetChat()
    {
        session()->forget('sentence_starter_message_id');
        return redirect()->back();
    }
}
