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

        // Calcular racha de entrenamientos completados
        $logs = $user->workoutLogs()
            ->where('completed', true)
            ->orderByDesc('date')
            ->get(['date']);

        $streak     = $this->calculateStreak($logs);
        $weekDays   = $this->weekActivity($logs);

        // Rutina activa del usuario con todos sus ejercicios
        $today = $user->routines()
            ->where('is_active', true)
            ->with(['days.exercises.exercise'])
            ->latest()
            ->first();

        // Registro de entrenamiento de hoy (si existe)
        $todayLog = $user->workoutLogs()
            ->whereDate('date', today())
            ->first();

        // Últimas 5 entradas de progreso para mostrar tendencia
        $recentProgress = $user->progressEntries()
            ->orderByDesc('recorded_at')
            ->limit(5)
            ->get(['weight_kg', 'body_fat_pct', 'recorded_at']);

        return Inertia::render('Dashboard', [
            'streak'         => $streak,
            'weekDays'       => $weekDays,
            'routine'        => $today,
            'todayLog'       => $todayLog,
            'recentProgress' => $recentProgress,
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
