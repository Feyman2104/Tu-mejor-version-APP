<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateRoutineJob;
use App\Models\WorkoutLog;
use App\Models\WorkoutSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutController extends Controller
{
    public function today(Request $request): Response
    {
        $user = $request->user();

        $routine = $user->routines()
            ->where('is_active', true)
            ->with(['days' => fn ($q) => $q->orderBy('day_number'), 'days.exercises.exercise'])
            ->latest()
            ->first();

        $todayLog = $user->workoutLogs()
            ->with('sets')
            ->whereDate('date', today())
            ->where('completed', false)
            ->latest()
            ->first();

        return Inertia::render('Workout/Today', [
            'routine'  => $routine,
            'todayLog' => $todayLog,
        ]);
    }

    public function log(Request $request): Response
    {
        $logs = $request->user()
            ->workoutLogs()
            ->with(['sets', 'routineDay'])
            ->orderByDesc('date')
            ->paginate(10);

        return Inertia::render('Workout/Log', [
            'logs' => $logs,
        ]);
    }

    public function storeLog(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'routine_day_id' => ['nullable', 'exists:routine_days,id'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $log = $request->user()->workoutLogs()->create([
            'routine_day_id' => $validated['routine_day_id'] ?? null,
            'date'           => today(),
            'completed'      => false,
            'notes'          => $validated['notes'] ?? null,
        ]);

        return response()->json(['log' => $log]);
    }

    public function storeSet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workout_log_id'      => ['required', 'exists:workout_logs,id'],
            'routine_exercise_id' => ['nullable', 'exists:routine_exercises,id'],
            'set_number'          => ['required', 'integer', 'min:1'],
            'reps_done'           => ['nullable', 'integer', 'min:0'],
            'weight_kg'           => ['nullable', 'numeric', 'min:0'],
            'rpe'                 => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $log = WorkoutLog::findOrFail($validated['workout_log_id']);
        abort_if($log->user_id !== $request->user()->id, 403);

        $set = WorkoutSet::create([
            ...$validated,
            'completed_at' => now(),
        ]);

        return response()->json(['set' => $set]);
    }

    public function complete(Request $request, WorkoutLog $workoutLog): RedirectResponse
    {
        abort_if($workoutLog->user_id !== $request->user()->id, 403);

        $workoutLog->update(['completed' => true]);

        return back()->with('success', '¡Entrenamiento completado! Excelente trabajo 💪');
    }

    public function generateRoutine(Request $request): RedirectResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        GenerateRoutineJob::dispatch($request->user(), $request->input('notes'));

        return back()->with('success', 'Tu rutina se está generando. En unos segundos estará lista.');
    }
}
