<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\AIConversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show AI Chat main page
     */
    public function index(Request $request)
    {
        // Get recent conversations for sidebar
        $conversations = AIConversation::where('user_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get(['id', 'title', 'created_at']);

        return Inertia::render('AI/Chat', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Show specific conversation
     */
    public function show(Request $request, string $conversationId)
    {
        $conversation = AIConversation::where('user_id', auth()->id())
            ->findOrFail($conversationId);

        // Get all messages for this conversation
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get(['id', 'role', 'content', 'created_at'])
            ->toArray();

        return Inertia::render('AI/Chat', [
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    /**
     * Show AI Reports page
     */
    public function reports(Request $request)
    {
        return Inertia::render('AI/Reports');
    }

    /**
     * Show available models for querying
     */
    public function models(Request $request)
    {
        return Inertia::render('AI/Models');
    }
}
