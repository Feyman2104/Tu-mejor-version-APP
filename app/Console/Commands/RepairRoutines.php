<?php

namespace App\Console\Commands;

use App\Models\Routine;
use App\Models\User;
use App\Services\RoutineGeneratorService;
use Illuminate\Console\Command;

class RepairRoutines extends Command
{
    protected $signature   = 'routines:repair';
    protected $description = 'Borra rutinas sin días (huérfanas) y regenera la rutina de usuarios con onboarding completado que quedaron sin una rutina activa válida';

    public function handle(): int
    {
        $orphans = Routine::query()->doesntHave('days')->get();
        foreach ($orphans as $orphan) {
            $orphan->forceDelete();
        }
        $this->info("Rutinas huérfanas eliminadas: {$orphans->count()}");

        $users = User::query()
            ->whereNotNull('onboarding_completed_at')
            ->whereDoesntHave('routines', fn ($q) => $q->where('is_active', true)->has('days'))
            ->get();

        $regenerated = 0;
        foreach ($users as $user) {
            try {
                (new RoutineGeneratorService($user))->generate();
                $regenerated++;
            } catch (\Throwable $e) {
                $this->error("Falló la regeneración para el usuario {$user->id}: {$e->getMessage()}");
            }
        }

        $this->info("Rutinas regeneradas: {$regenerated}");

        return self::SUCCESS;
    }
}
