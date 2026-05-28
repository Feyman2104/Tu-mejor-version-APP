<?php

namespace App\Http\Controllers;

use App\Services\BodyCompositionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProgressController extends Controller
{
    public function __construct(private readonly BodyCompositionService $bcs) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $entries = $user->progressEntries()
            ->orderByDesc('recorded_at')
            ->get();

        // ── Estimación automática de % grasa (CUN-BAE, sin cinta) ────────────
        $autoBodyFat = null;
        if ($user->weight_kg && $user->height_cm && $user->age) {
            try {
                $autoBodyFat = $this->bcs->estimateCunbae(
                    (float) $user->weight_kg,
                    (float) $user->height_cm,
                    (int)   $user->age,
                    $user->sex ?? 'male',
                );
            } catch (\Throwable) {
                // Si falla, quedará null
            }
        }

        // ── Métricas de entrenamiento ─────────────────────────────────────────
        $weekStart  = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();

        $logs = $user->workoutLogs()
            ->where('completed', true)
            ->orderByDesc('date')
            ->get(['id', 'date']);

        $streak = $this->calculateStreak($logs);

        // Contar en BD (evita comparar Carbon vs string en colección)
        $workoutsThisWeek = $user->workoutLogs()
            ->where('completed', true)
            ->where('date', '>=', $weekStart)
            ->count();

        $workoutsThisMonth = $user->workoutLogs()
            ->where('completed', true)
            ->where('date', '>=', $monthStart)
            ->count();

        $weeklyVolume = (float) $user->workoutLogs()
            ->where('completed', true)
            ->where('date', '>=', $weekStart)
            ->with('sets')
            ->get()
            ->flatMap(fn ($log) => $log->sets)
            ->reduce(fn ($carry, $set) => $carry + ($set->reps_done ?? 0) * ((float) ($set->weight_kg ?? 0)), 0.0);

        // PRs: peso máximo por ejercicio (top 5)
        $prs = DB::table('workout_sets')
            ->join('workout_logs',       'workout_sets.workout_log_id',       '=', 'workout_logs.id')
            ->join('routine_exercises',  'workout_sets.routine_exercise_id',  '=', 'routine_exercises.id')
            ->join('exercises',          'routine_exercises.exercise_id',     '=', 'exercises.id')
            ->where('workout_logs.user_id', $user->id)
            ->whereNotNull('workout_sets.weight_kg')
            ->select('exercises.name as exercise_name', DB::raw('MAX(workout_sets.weight_kg) as max_weight_kg'))
            ->groupBy('exercises.id', 'exercises.name')
            ->orderByDesc('max_weight_kg')
            ->limit(5)
            ->get()
            ->toArray();

        return Inertia::render('Progress/Index', [
            'entries'           => $entries,
            'autoBodyFat'       => $autoBodyFat,
            'streak'            => $streak,
            'workoutsThisWeek'  => $workoutsThisWeek,
            'workoutsThisMonth' => $workoutsThisMonth,
            'weeklyVolume'      => round($weeklyVolume, 1),
            'prs'               => $prs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'weight_kg'      => ['nullable', 'numeric', 'min:20', 'max:300'],
            'body_fat_pct'   => ['nullable', 'numeric', 'min:1',  'max:70'],
            'muscle_mass_kg' => ['nullable', 'numeric', 'min:5',  'max:150'],
            'notes'          => ['nullable', 'string',  'max:500'],
            'recorded_at'    => ['nullable', 'date'],
        ]);

        $request->user()->progressEntries()->create([
            ...$validated,
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        return back()->with('success', 'Medida registrada correctamente.');
    }

    private function calculateStreak($logs): int
    {
        if ($logs->isEmpty()) return 0;

        $streak = 0;
        $check  = Carbon::today();

        foreach ($logs->groupBy(fn ($l) => Carbon::parse($l->date)->toDateString()) as $date => $_) {
            if (Carbon::parse($date)->eq($check) || Carbon::parse($date)->eq($check->copy()->subDay())) {
                $streak++;
                $check = Carbon::parse($date)->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }
}
