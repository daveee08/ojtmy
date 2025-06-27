<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ConversationHistory;

class ConversationController extends Controller
{
    // Get conversation history for the authenticated user and agent
    public function history(Request $request)
    {
        $agent = $request->input('agent', 'tutor');
        $history = ConversationHistory::where('user_id', Auth::id())
            ->where('agent', $agent)
            ->orderBy('created_at')
            ->get();
        return response()->json($history);
    }

    // Store a new message in the conversation history
    public function store(Request $request)
    {
        $validated = $request->validate([
            'agent' => 'required|string',
            'message' => 'required|string',
            'sender' => 'required|in:user,agent',
        ]);
        $history = ConversationHistory::create([
            'user_id' => Auth::id(),
            'agent' => $validated['agent'],
            'message' => $validated['message'],
            'sender' => $validated['sender'],
        ]);
        return response()->json($history, 201);
    }
}
