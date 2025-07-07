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
        set_time_limit(0);   // allow the user to wait for the LLM

        // ------------------------------------------------------------------ #
        // 1. Validate input                                                  #
        // ------------------------------------------------------------------ #
        $validated = $request->validate([
            'grade_level' => 'required|string',
            'topic'       => 'required|string',
        ]);

        // ------------------------------------------------------------------ #
        // 2. Build multipart payload                                         #
        // ------------------------------------------------------------------ #
        $multipart = [
            ['name' => 'grade_level', 'contents' => $validated['grade_level']],
            ['name' => 'topic',        'contents' => $validated['topic']],
            ['name' => 'mode',         'contents' => 'manual'],                // direct generation
            ['name' => 'user_id',      'contents' => auth()->id() ?: 1],
            // ['name' => 'agent_id',  'contents' => 14],  // optional override
        ];

        // ------------------------------------------------------------------ #
        // 3. Call the Python service                                         #
        // ------------------------------------------------------------------ #
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

        // Example: append the follow‑up to the original topic
        $combinedTopic = "{$request->original_topic}. {$request->followup}";

        // Re‑call the agent with the combined topic
        return $this->processForm(new Request([
            'grade_level' => $request->grade_level,
            'topic'       => $combinedTopic,
        ]));
    }
}