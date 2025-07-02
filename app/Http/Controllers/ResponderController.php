<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Storage;
class ResponderController extends Controller
{
    public function showForm()
    {
        return view('responder');
    }

    public function processForm(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'author' => 'required|string|max:255',
            'email' => 'required|string',
            'intent' => 'required|string',
            'tone' => 'required|string|in:Formal,Friendly,Concise,Apologetic,Assertive',
        ]);

        $multipartData = [
            [
                'name' => 'author',
                'contents' => $validated['author'],
            ],
            [
                'name' => 'email',
                'contents' => $validated['email'],
            ],
            [
                'name' => 'intent',
                'contents' => $validated['intent'],
            ],
            [
                'name' => 'tone',
                'contents' => $validated['tone'],
            ],
        ];

        // Replace with your actual backend URL
        $response = Http::timeout(0)
            ->asMultipart()
            ->post('http://192.168.50.123:5001/responder', $multipartData);

        if ($response->failed()) {
            return back()->withErrors(['error' => 'Python API failed: ' . $response->body()]);
        }

        return view('responder', ['response' => $response->body()]);
    }
}
