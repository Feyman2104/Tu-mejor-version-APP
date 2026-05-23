<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\AICoachService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(Request $request): Response
    {
        $messages = $request->user()
            ->chatMessages()
            ->orderBy('created_at')
            ->get(['id', 'role', 'content', 'created_at']);

        return Inertia::render('Chat/Index', [
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, AICoachService $ai): HttpResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        // Persist user message
        $user->chatMessages()->create([
            'role'    => 'user',
            'content' => $validated['message'],
        ]);

        // Build conversation history (last 20 messages)
        $history = $user->chatMessages()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        // Stream SSE response
        return response()->stream(function () use ($user, $history, $ai) {
            $fullContent = '';

            foreach ($ai->streamChat($user, $history) as $chunk) {
                $fullContent .= $chunk;
                echo "data: " . json_encode(['text' => $chunk]) . "\n\n";
                ob_flush();
                flush();
            }

            // Persist assistant reply
            $user->chatMessages()->create([
                'role'    => 'assistant',
                'content' => $fullContent,
            ]);

            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}
