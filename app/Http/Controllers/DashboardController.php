<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $logs = $user->workoutLogs()
            ->where('completed', true)
            ->orderByDesc('date')
            ->get(['date']);

        $streak   = $this->calculateStreak($logs);
        $weekDays = $this->weekActivity($logs);

        $routine = $user->routines()
            ->where('is_active', true)
            ->with([
                'days' => fn ($q) => $q->orderBy('day_number'),
                'days.exercises' => fn ($q) => $q->where('is_active', true),
                'days.exercises.exercise',
            ])
            ->latest()
            ->first();

        $todayIso  = (int) now()->isoFormat('E');
        $todayDay  = $routine?->days->firstWhere('day_number', $todayIso);
        $isRestDay = $routine && ! $todayDay;

        $todayLog = $user->workoutLogs()
            ->whereDate('date', today())
            ->latest()
            ->first();

        $recentProgress = $user->progressEntries()
            ->orderByDesc('recorded_at')
            ->limit(5)
            ->get(['weight_kg', 'body_fat_pct', 'recorded_at']);

        $monthStart = Carbon::now()->startOfMonth();
        $weekStart  = Carbon::now()->startOfWeek();

        $workoutsThisMonth = $user->workoutLogs()
            ->where('completed', true)
            ->where('date', '>=', $monthStart)
            ->count();

        $workoutsThisWeek = $user->workoutLogs()
            ->where('completed', true)
            ->where('date', '>=', $weekStart)
            ->count();

        $weeklyVolume = (float) $user->workoutLogs()
            ->where('completed', true)
            ->where('date', '>=', $weekStart)
            ->with('sets')
            ->get()
            ->flatMap(fn ($log) => $log->sets)
            ->reduce(fn ($carry, $set) => $carry + ($set->reps_done ?? 0) * ((float) $set->weight_kg), 0.0);

        $weeklyExercises = $user->workoutLogs()
            ->where('completed', true)
            ->where('date', '>=', $weekStart)
            ->with('sets.routineExercise')
            ->get()
            ->flatMap(fn ($log) => $log->sets)
            ->filter(fn ($set) => $set->routineExercise)
            ->map(fn ($set) => $set->routineExercise->exercise_id)
            ->unique()
            ->count();

        $plannedDaysThisWeek = $routine ? min($routine->days_per_week, 7) : 0;
        $trainedDaysThisWeek = $user->workoutLogs()
            ->where('completed', true)
            ->where('date', '>=', $weekStart)
            ->where('date', '<=', Carbon::now())
            ->count();
        $adherencePct = $plannedDaysThisWeek > 0
            ? round(($trainedDaysThisWeek / $plannedDaysThisWeek) * 100)
            : null;

        $weightSeries = $user->progressEntries()
            ->whereNotNull('weight_kg')
            ->orderBy('recorded_at', 'asc')
            ->limit(12)
            ->get(['weight_kg', 'recorded_at'])
            ->map(fn ($e) => [
                'value' => (float) $e->weight_kg,
                'date'  => Carbon::parse($e->recorded_at)->toDateString(),
            ])
            ->toArray();

        return Inertia::render('Dashboard', [
            'streak'              => $streak,
            'weekDays'            => $weekDays,
            'routine'             => $routine,
            'todayDay'            => $todayDay,
            'isRestDay'           => $isRestDay,
            'todayLog'            => $todayLog,
            'recentProgress'      => $recentProgress,
            'workoutsThisMonth'   => $workoutsThisMonth,
            'workoutsThisWeek'    => $workoutsThisWeek,
            'weeklyVolume'        => round($weeklyVolume, 1),
            'weeklyExercises'     => $weeklyExercises,
            'adherencePct'       => $adherencePct,
            'weightSeries'        => $weightSeries,
            'plannedDaysThisWeek' => $plannedDaysThisWeek,
        ]);
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

    private function weekActivity($logs): array
    {
        $activeDays = $logs
            ->map(fn ($l) => Carbon::parse($l->date)->toDateString())
            ->unique()
            ->values()
            ->toArray();

        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date     = Carbon::today()->subDays($i)->toDateString();
            $result[] = in_array($date, $activeDays);
        }

        return $result;
    }
}