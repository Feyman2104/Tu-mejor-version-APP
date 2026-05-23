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

        $todayIso  = (int) now()->isoFormat('E');
        $todayDay  = $routine?->days->firstWhere('day_number', $todayIso);
        $isRestDay = $routine && ! $todayDay;

        $todayLog = $user->workoutLogs()
            ->with('sets')
            ->whereDate('date', today())
            ->where('completed', false)
            ->latest()
            ->first();

        $prevSets = [];
        if ($routine) {
            $exerciseIds = $routine->days
                ->flatMap(fn ($d) => $d->exercises)
                ->pluck('exercise_id')
                ->unique();

            foreach ($exerciseIds as $exId) {
                $last = WorkoutSet::whereHas('workoutLog', fn ($q) => $q
                        ->where('user_id', $user->id)
                        ->where('completed', true)
                    )
                    ->whereHas('routineExercise', fn ($q) => $q->where('exercise_id', $exId))
                    ->latest('completed_at')
                    ->select('weight_kg', 'reps_done')
                    ->first();

                if ($last) {
                    $prevSets[$exId] = [
                        'weight_kg' => $last->weight_kg,
                        'reps_done' => $last->reps_done,
                    ];
                }
            }
        }

        return Inertia::render('Workout/Today', [
            'routine'   => $routine,
            'todayDay'  => $todayDay,
            'isRestDay' => $isRestDay,
            'todayLog'  => $todayLog,
            'prevSets'  => $prevSets,
        ]);
    }

    public function log(Request $request): Response
    {
        $logs = $request->user()
            ->workoutLogs()
            ->with(['sets.routineExercise.exercise', 'routineDay'])
            ->where('completed', true)
            ->orderByDesc('date')
            ->paginate(15);

        return Inertia::render('Workout/Log', [
            'logs' => $logs,
        ]);
    }

    public function show(Request $request, WorkoutLog $workoutLog): Response
    {
        abort_if($workoutLog->user_id !== $request->user()->id, 403);

        $workoutLog->load(['sets.routineExercise.exercise', 'routineDay']);

        // Detectar PRs: peso máximo de esta sesión vs. histórico anterior
        $prs = [];
        $byExercise = $workoutLog->sets
            ->filter(fn ($s) => $s->routineExercise?->exercise)
            ->groupBy(fn ($s) => $s->routineExercise->exercise->id);

        foreach ($byExercise as $exerciseId => $sets) {
            $exercise   = $sets->first()->routineExercise->exercise;
            $sessionMax = $sets->max('weight_kg');
            if (! $sessionMax) continue;

            $prevBest = WorkoutSet::whereHas('workoutLog', fn ($q) => $q
                    ->where('user_id', $request->user()->id)
                    ->where('completed', true)
                    ->where('id', '!=', $workoutLog->id)
                )
                ->whereHas('routineExercise', fn ($q) => $q->where('exercise_id', $exerciseId))
                ->max('weight_kg');

            if ($sessionMax > ($prevBest ?? 0)) {
                $prs[] = [
                    'exercise_name' => $exercise->name,
                    'muscle_group'  => $exercise->muscle_group,
                    'weight_kg'     => (float) $sessionMax,
                    'prev_kg'       => $prevBest ? (float) $prevBest : null,
                ];
            }
        }

        return Inertia::render('Workout/Show', [
            'log'          => $workoutLog,
            'prs'          => $prs,
            'isFresh'      => session('fresh_completion', false),
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

        $validated = $request->validate([
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
        ]);

        $workoutLog->update([
            'completed'        => true,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
        ]);

        return redirect()
            ->route('workout.logs.show', $workoutLog)
            ->with('fresh_completion', true);
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
