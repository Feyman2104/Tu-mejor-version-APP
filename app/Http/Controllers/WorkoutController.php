<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateRoutineJob;
use App\Models\Exercise;
use App\Models\RoutineDay;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\WorkoutSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutController extends Controller
{
    /**
     * Pantalla "Entrenamiento" — lista todas las rutinas del usuario como tarjetas
     * (cada RoutineDay es una plantilla independiente que se puede empezar cualquier día).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $routines = $user->routines()
            ->with([
                'days' => fn ($q) => $q->orderBy('day_number'),
                'days.exercises' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
                'days.exercises.exercise:id,name',
            ])
            ->latest()
            ->get();

        // Aplanar a tarjetas: una por cada RoutineDay
        $cards = [];
        foreach ($routines as $routine) {
            foreach ($routine->days as $day) {
                $exercises = $day->exercises;
                $cards[] = [
                    'routine_day_id' => $day->id,
                    'routine_id'     => $routine->id,
                    'name'           => $day->name,
                    'focus'          => $day->focus,
                    'exercise_count' => $exercises->count(),
                    'preview'        => $exercises->take(3)->map(fn ($re) => $re->exercise?->name)->filter()->values(),
                    'is_custom'      => ! $routine->generated_by_ai,
                    'is_active'      => $routine->is_active,
                ];
            }
        }

        return Inertia::render('Workout/Index', [
            'cards' => $cards,
        ]);
    }

    public function today(Request $request): Response
    {
        $user = $request->user();

        $routine = $user->routines()
            ->where('is_active', true)
            ->with([
                'days' => fn ($q) => $q->orderBy('day_number'),
                'days.exercises' => fn ($q) => $q->where('is_active', true),
                'days.exercises.exercise',
                'days.exercises.exercise.contraindications',
            ])
            ->latest()
            ->first();

        $todayIso  = (int) now()->isoFormat('E');
        $todayDay  = $routine?->days->firstWhere('day_number', $todayIso);
        $isRestDay = $routine && ! $todayDay;

        // Buscar sesión incompleta activa (sin filtro de fecha para reanudar sesiones de días anteriores)
        $todayLog = $user->workoutLogs()
            ->with('sets')
            ->where('completed', false)
            ->latest()
            ->first();

        $exerciseIds = $routine
            ? $routine->days->flatMap(fn ($d) => $d->exercises)->pluck('exercise_id')->unique()
            : collect();

        return Inertia::render('Workout/Today', [
            'routine'          => $routine,
            'todayDay'         => $todayDay,
            'isRestDay'        => $isRestDay,
            'todayLog'         => $todayLog,
            'prevSets'         => $this->prevSetsFor($user, $exerciseIds),
            'suggestedWeights' => $this->suggestedWeightsFor($user, $exerciseIds),
            'autostart'        => false,
            'emptyMode'        => false,
        ]);
    }

    /**
     * Abre la sesión de registro para un RoutineDay concreto (desde "Empezar Rutina").
     */
    public function session(Request $request, RoutineDay $routineDay): Response
    {
        $user = $request->user();
        $routineDay->load('routine');
        abort_if($routineDay->routine->user_id !== $user->id, 403);

        $routine = $user->routines()
            ->whereKey($routineDay->routine_id)
            ->with([
                'days' => fn ($q) => $q->orderBy('day_number'),
                'days.exercises' => fn ($q) => $q->where('is_active', true),
                'days.exercises.exercise',
                'days.exercises.exercise.contraindications',
            ])
            ->first();

        $targetDay = $routine?->days->firstWhere('id', $routineDay->id);

        // Resume un log incompleto de hoy ligado a este día, si existe
        $todayLog = $user->workoutLogs()
            ->with('sets')
            ->whereDate('date', today())
            ->where('completed', false)
            ->where('routine_day_id', $routineDay->id)
            ->latest()
            ->first();

        $exerciseIds = collect($targetDay?->exercises ?? [])->pluck('exercise_id')->unique();

        return Inertia::render('Workout/Today', [
            'routine'          => $routine,
            'todayDay'         => $targetDay,
            'isRestDay'        => false,
            'todayLog'         => $todayLog,
            'prevSets'         => $this->prevSetsFor($user, $exerciseIds),
            'suggestedWeights' => $this->suggestedWeightsFor($user, $exerciseIds),
            'autostart'        => $todayLog === null,
            'emptyMode'        => false,
        ]);
    }

    /**
     * Inicia una sesión vacía: sin día ni ejercicios preestablecidos.
     */
    public function empty(Request $request): Response
    {
        return Inertia::render('Workout/Today', [
            'routine'          => null,
            'todayDay'         => null,
            'isRestDay'        => false,
            'todayLog'         => null,
            'prevSets'         => (object) [],
            'suggestedWeights' => (object) [],
            'autostart'        => true,
            'emptyMode'        => true,
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

    public function destroy(Request $request, WorkoutLog $workoutLog): RedirectResponse
    {
        abort_if($workoutLog->user_id !== $request->user()->id, 403);

        $workoutLog->delete();

        return redirect()->route('dashboard');
    }

    public function generateRoutine(Request $request): RedirectResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        GenerateRoutineJob::dispatch($request->user(), $request->input('notes'));

        return back()->with('success', 'Tu rutina se está generando. En unos segundos estará lista.');
    }

    /**
     * Último peso/reps registrado por el usuario para cada ejercicio dado.
     *
     * @return array<int, array{weight_kg: mixed, reps_done: mixed}>
     */
    private function prevSetsFor(User $user, \Illuminate\Support\Collection $exerciseIds): array
    {
        $prevSets = [];
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

        return $prevSets;
    }

    /**
     * Peso de partida sugerido (heurístico) para ejercicios que el usuario aún no ha hecho.
     * Basado en el nivel del usuario y el patrón de movimiento del ejercicio.
     * NO es una prescripción: es un punto de partida orientativo.
     *
     * @return array<int, float>
     */
    private function suggestedWeightsFor(User $user, \Illuminate\Support\Collection $exerciseIds): array
    {
        if ($exerciseIds->isEmpty()) {
            return [];
        }

        $levelFactor = match ($user->level) {
            'intermediate' => 2,
            'advanced'     => 3,
            default        => 1, // beginner / null
        };

        $exercises = Exercise::whereIn('id', $exerciseIds)
            ->get(['id', 'movement_pattern']);

        $suggested = [];
        foreach ($exercises as $ex) {
            // Peso base por patrón (kg, para nivel principiante)
            $patternBase = match ($ex->movement_pattern) {
                'squat', 'hinge' => 20, // compuestos pesados (sentadilla, peso muerto)
                'push', 'pull'   => 12, // empujes/jalones
                'carry'          => 16,
                default          => 5,  // rotation / core / aislamiento ligero
            };
            $suggested[$ex->id] = (float) ($patternBase * $levelFactor);
        }

        return $suggested;
    }
}
