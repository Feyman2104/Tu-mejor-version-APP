<?php

namespace App\Http\Controllers;

use App\Models\WorkoutLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostureController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Posture/Analyzer');
    }

    public function storeSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exercise_slug' => ['required', 'string'],
            'score'         => ['required', 'integer', 'min:0', 'max:100'],
            'feedback'      => ['required', 'array'],
            'duration_sec'  => ['nullable', 'integer', 'min:0'],
            'reps'          => ['nullable', 'integer', 'min:0'],
            'good_reps'     => ['nullable', 'integer', 'min:0'],
            'aspects'       => ['nullable', 'array'],
        ]);

        // Guardar como nota de progreso (se puede extender con tabla posture_sessions en el futuro)
        $request->user()->progressEntries()->create([
            'notes'       => json_encode([
                'type'          => 'posture',
                'exercise'      => $validated['exercise_slug'],
                'score'         => $validated['score'],
                'feedback'      => $validated['feedback'],
                'duration_sec'  => $validated['duration_sec'] ?? 0,
                'reps'          => $validated['reps'] ?? 0,
                'good_reps'     => $validated['good_reps'] ?? 0,
                'aspects'       => $validated['aspects'] ?? [],
            ]),
            'recorded_at' => now(),
        ]);

        return back()->with('success', "Sesión de postura guardada. Puntuación: {$validated['score']}/100");
    }
}
