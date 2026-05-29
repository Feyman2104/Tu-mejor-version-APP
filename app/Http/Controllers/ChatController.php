<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\AICoachService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    public function send(Request $request, AICoachService $ai): StreamedResponse
    {
        $validated = $request->validate([
            'message'         => ['required', 'string', 'max:2000'],
            'workout_context' => ['nullable', 'string', 'max:3000'],
            'posture_context' => ['nullable', 'string', 'max:2000'],
        ]);

        $user           = $request->user();
        $workoutContext = $validated['workout_context'] ?? null;
        $postureContext = $validated['posture_context'] ?? null;

        // Build conversation history BEFORE persisting the new user message
        // to avoid including it twice in the context sent to the AI
        $history = $user->chatMessages()
            ->orderByDesc('created_at')
            ->limit(19)  // 19 historial + 1 nuevo = 20 total
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        // Append the new user message to history and persist it
        $history[] = ['role' => 'user', 'content' => $validated['message']];
        $user->chatMessages()->create([
            'role'    => 'user',
            'content' => $validated['message'],
        ]);

        // Stream SSE response
        return response()->stream(function () use ($user, $history, $ai, $workoutContext, $postureContext) {
            $fullContent = '';

            foreach ($ai->streamChat($user, $history, $workoutContext, $postureContext) as $chunk) {
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
