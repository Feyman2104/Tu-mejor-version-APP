<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\ExerciseContraindication;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RoutineGeneratorService
{
    private array $userProfile;
    private array $exercisePool;
    private array $smallMuscleGroups = ['core', 'calves', 'forearms'];
    private int $daysPerWeek;
    private string $splitType;
    private int $age;
    private string $activityLevel;
    private array $preferredMuscles;
    private bool $isNewUser;
    private ?string $lastTrained;
    private array $injuries;

    public function __construct(private readonly User $user)
    {
        $this->loadUserProfile();
    }

    private function loadUserProfile(): void
    {
        $this->daysPerWeek    = (int) ($this->user->days_per_week ?: 3);
        $this->splitType     = $this->user->split_type ?? 'auto';
        $this->age           = (int) ($this->user->age ?: 30);
        $this->activityLevel = $this->user->activity_level ?? 'lightly_active';
        $this->preferredMuscles = $this->user->preferred_muscles ?? [];
        $this->isNewUser     = !$this->user->has_trained_before;
        $this->lastTrained   = $this->user->last_trained;
        $this->injuries      = collect($this->user->injuries ?? [])->filter(fn ($i) => ($i['zone'] ?? '') !== 'none')->values()->all();

        $this->userProfile = [
            'goal'        => $this->user->goal,
            'level'       => $this->user->level ?? 'beginner',
            'equipment'   => $this->user->equipment ?? [],
            'place'       => $this->user->place ?? 'gym',
            'mobility'   => $this->user->mobility ?? 'good',
            'weight_kg'   => $this->user->weight_kg,
            'session_min' => $this->user->session_duration_minutes ?? 60,
        ];

        $this->buildExercisePool();
    }

    private function buildExercisePool(): void
    {
        $allowedLevels = match ($this->userProfile['level']) {
            'intermediate' => ['beginner', 'intermediate'],
            'advanced'     => ['beginner', 'intermediate', 'advanced'],
            default        => ['beginner'],
        };

        $environments = match ($this->userProfile['place']) {
            'home'  => ['home', 'both'],
            'gym'   => ['gym', 'both'],
            default => ['home', 'gym', 'both'],
        };

        $excludedIds = $this->getExcludedExerciseIds();

        $query = Exercise::whereIn('environment', $environments)
            ->whereIn('level', $allowedLevels)
            ->when($excludedIds, fn ($q) => $q->whereNotIn('id', $excludedIds));

        $this->exercisePool = $query->orderBy('muscle_group')
            ->orderBy('level')
            ->get()
            ->toArray();

        if (count($this->exercisePool) < 8) {
            $this->exercisePool = Exercise::whereIn('environment', $environments)
                ->when($excludedIds, fn ($q) => $q->whereNotIn('id', $excludedIds))
                ->orderBy('muscle_group')
                ->get()
                ->toArray();
        }

        if (count($this->exercisePool) < 8) {
            $this->exercisePool = Exercise::when($excludedIds, fn ($q) => $q->whereNotIn('id', $excludedIds))
                ->orderBy('muscle_group')
                ->get()
                ->toArray();
        }
    }

    private function getExcludedExerciseIds(): array
    {
        $injuryZones = collect($this->injuries)
            ->map(fn ($i) => match ($i['zone'] ?? '') {
                'back'     => 'lumbar',
                'knee'     => 'rodilla',
                'shoulder' => 'hombro',
                'wrist'    => 'muneca',
                'hip'      => 'cadera',
                default    => null,
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($injuryZones)) {
            return [];
        }

        return ExerciseContraindication::whereIn('body_zone', $injuryZones)
            ->pluck('exercise_id')
            ->unique()
            ->all();
    }

    public function generate(?string $notes = null): ?Routine
    {
        $this->user->routines()->update(['is_active' => false]);

        $isAdaptationPhase = $this->shouldUseAdaptationPhase();

        $phase = $isAdaptationPhase ? 'adaptation' : 'main';
        $phaseWeeks = $isAdaptationPhase ? $this->getAdaptationWeeks() : 0;

        $splitConfig = $this->resolveSplit();

        $routine = $this->user->routines()->create([
            'name'            => $this->generateRoutineName($phase),
            'description'     => $this->generateRoutineDescription($phase, $phaseWeeks),
            'goal'            => $this->mapGoal(),
            'generated_by_ai' => true,
            'days_per_week'   => $this->daysPerWeek,
            'is_active'       => true,
            'phase'           => $phase,
            'phase_weeks'     => $phaseWeeks,
        ]);

        $days = $this->buildDays($splitConfig, $phase);

        foreach ($days as $dayIndex => $dayData) {
            $day = $routine->days()->create([
                'day_number' => $dayIndex + 1,
                'name'       => $dayData['name'],
                'focus'      => $dayData['focus'],
            ]);

            foreach ($dayData['exercises'] as $order => $exData) {
                $day->exercises()->create([
                    'exercise_id'  => $exData['id'],
                    'sets'         => $exData['sets'],
                    'reps'         => $exData['reps'],
                    'rest_seconds' => $exData['rest'],
                    'rir'          => $exData['rir'],
                    'notes'        => $exData['notes'],
                    'order'        => $order + 1,
                ]);
            }
        }

        Log::info("Routine generated for user {$this->user->id}", [
            'routine_id' => $routine->id,
            'phase'      => $phase,
            'days'       => $this->daysPerWeek,
        ]);

        return $routine;
    }

    private function shouldUseAdaptationPhase(): bool
    {
        if ($this->isNewUser) {
            return true;
        }

        return match ($this->lastTrained) {
            'currently' => false,
            'lt_1m'     => false,
            '1_3m'      => true,
            '3_6m'      => true,
            'gt_6m'     => true,
            default     => true,
        };
    }

    private function getAdaptationWeeks(): int
    {
        return match ($this->lastTrained) {
            '1_3m' => 2,
            '3_6m' => 3,
            'gt_6m' => 4,
            default => 3,
        };
    }

    private function resolveSplit(): array
    {
        $split = $this->splitType;

        if ($split === 'auto') {
            $split = match (true) {
                $this->daysPerWeek <= 3 => 'full_body',
                $this->daysPerWeek === 4 => 'upper_lower',
                $this->daysPerWeek === 5 => 'ppl_hybrid',
                $this->daysPerWeek >= 6 => 'ppl_x2',
            };
        }

        return match ($split) {
            'full_body' => $this->splitFullBody(),
            'upper_lower' => $this->splitUpperLower(),
            'upper_lower_emphasis' => $this->splitUpperLowerEmphasis(),
            'ppl' => $this->splitPPL(),
            'ppl_hybrid' => $this->splitPPLHybrid(),
            'ppl_x2' => $this->splitPPLx2(),
            'weider' => $this->splitWeider(),
            default => $this->splitFullBody(),
        };
    }

    private function splitFullBody(): array
    {
        $count = min($this->daysPerWeek, 3);
        return array_fill(0, $count, [
            'name'   => fn ($i) => "Día {$i} · Cuerpo completo",
            'focus'  => 'full_body',
            'patterns' => ['push', 'pull', 'squat', 'hinge', 'core', 'carry'],
        ]);
    }

    private function splitUpperLower(): array
    {
        $days = [];
        for ($i = 0; $i < $this->daysPerWeek; $i++) {
            $isUpper = $i % 2 === 0;
            $days[] = [
                'name'    => fn ($i) => $isUpper ? "Día {$i} · Torso (Empuje + Tirón)" : "Día {$i} · Pierna",
                'focus'   => $isUpper ? 'push' : 'legs',
                'patterns' => $isUpper ? ['push', 'pull'] : ['squat', 'hinge', 'core'],
            ];
        }
        return $days;
    }

    private function splitUpperLowerEmphasis(): array
    {
        $days = [];
        $pattern = ['legs', 'legs', 'legs', 'push', 'pull'];
        for ($i = 0; $i < $this->daysPerWeek; $i++) {
            $focus = $pattern[$i % count($pattern)];
            $days[] = [
                'name'    => fn ($i) => match ($focus) {
                    'legs' => "Día {$i} · Pierna (Énfasis)",
                    'push' => "Día {$i} · Torso (Empuje)",
                    'pull' => "Día {$i} · Torso (Tirón)",
                },
                'focus'   => $focus,
                'patterns' => match ($focus) {
                    'legs' => ['squat', 'hinge'],
                    'push' => ['push'],
                    'pull' => ['pull'],
                },
            ];
        }
        return $days;
    }

    private function splitPPL(): array
    {
        $focusOrder = ['push', 'pull', 'legs'];
        return array_map(fn ($i) => [
            'name'    => fn ($i) => "Día {$i} · " . match ($focusOrder[$i % 3]) {
                'push' => 'Empuje (Pecho · Hombro · Tríceps)',
                'pull' => 'Tirón (Espalda · Bíceps)',
                'legs' => 'Pierna (Cuádriceps · Isquios · Glúteo)',
            },
            'focus'   => $focusOrder[$i % 3],
            'patterns' => match ($focusOrder[$i % 3]) {
                'push' => ['push'],
                'pull' => ['pull'],
                'legs' => ['squat', 'hinge'],
            },
        ], array_values(array_fill(0, $this->daysPerWeek, null)));
    }

    private function splitPPLHybrid(): array
    {
        return [
            ['name' => fn ($i) => "Día {$i} · Empuje", 'focus' => 'push', 'patterns' => ['push']],
            ['name' => fn ($i) => "Día {$i} · Tirón",  'focus' => 'pull', 'patterns' => ['pull']],
            ['name' => fn ($i) => "Día {$i} · Pierna", 'focus' => 'legs', 'patterns' => ['squat', 'hinge']],
            ['name' => fn ($i) => "Día {$i} · Torso completo", 'focus' => 'full_body', 'patterns' => ['push', 'pull']],
            ['name' => fn ($i) => "Día {$i} · Pierna completa", 'focus' => 'legs', 'patterns' => ['squat', 'hinge', 'carry']],
        ];
    }

    private function splitPPLx2(): array
    {
        $pattern = ['push', 'pull', 'legs', 'push', 'pull', 'legs'];
        return array_map(fn ($i) => [
            'name'    => fn ($i) => "Día {$i} · " . match ($pattern[$i]) {
                'push' => 'Empuje',
                'pull' => 'Tirón',
                'legs' => 'Pierna',
            },
            'focus'   => $pattern[$i],
            'patterns' => match ($pattern[$i]) {
                'push' => ['push'],
                'pull' => ['pull'],
                'legs' => ['squat', 'hinge'],
            },
        ], array_values(array_fill(0, $this->daysPerWeek, null)));
    }

    private function splitWeider(): array
    {
        $groups = ['chest', 'back', 'shoulders', 'biceps', 'triceps', 'legs'];
        return array_map(fn ($i) => [
            'name'    => fn ($i) => "Día {$i} · " . ucfirst($groups[$i % count($groups)]),
            'focus'   => $groups[$i % count($groups)],
            'patterns' => ['push', 'pull', 'squat', 'hinge'],
        ], array_values(array_fill(0, $this->daysPerWeek, null)));
    }

    private function buildDays(array $splitConfig, string $phase): array
    {
        $level = $this->userProfile['level'];
        $goal  = $this->userProfile['goal'];
        $sessionMin = $this->userProfile['session_min'];

        $exercisesPerSession = match (true) {
            $sessionMin <= 30 => 3,
            $sessionMin <= 45 => 4,
            $sessionMin <= 60 => 5,
            $sessionMin <= 75 => 6,
            default => 7,
        };

        $volume = match ($level) {
            'beginner'     => $phase === 'adaptation' ? 8  : 10,
            'intermediate' => $phase === 'adaptation' ? 10 : 14,
            'advanced'     => $phase === 'adaptation' ? 12 : 18,
            default        => 10,
        };

        [$reps, $rest, $rir] = match ($goal) {
            'fat_loss'           => ['12-15', 60, 3],
            'muscle_gain', 'body_recomposition' => ['8-12', 90, 2],
            'strength'           => ['4-6', 180, 1],
            'maintain'           => ['10-12', 60, 3],
            'cardio'             => ['15-20', 45, 4],
            'flexibility'        => ['10-12', 60, 3],
            default              => ['8-12', 90, 2],
        };

        $days = [];
        foreach ($splitConfig as $dayIdx => $config) {
            $patterns = $config['patterns'];
            $focus = $config['focus'];

            $matching = array_values(array_filter(
                $this->exercisePool,
                fn ($e) => in_array($e['movement_pattern'] ?? '', $patterns)
            ));

            if (count($matching) < $exercisesPerSession) {
                $restPool = array_filter(
                    $this->exercisePool,
                    fn ($e) => !in_array($e['id'], array_column($matching, 'id'))
                );
                $matching = array_merge($matching, array_values($restPool));
            }

            usort($matching, fn ($a, $b) => $this->musclePriorityScore($a) <=> $this->musclePriorityScore($b));

            $chosen = array_slice($matching, 0, $exercisesPerSession);

            usort($chosen, fn ($a, $b) => $this->compoundFirst($a, $b));

            $exercises = array_map(function ($ex, $order) use ($reps, $rest, $rir, $phase, $focus, $patterns) {
                $sets = match (true) {
                    $phase === 'adaptation' => 2,
                    $this->isSmallMuscle($ex['muscle_group'] ?? '') => 3,
                    $this->isPreferredMuscle($ex['muscle_group'] ?? '') && $focus !== 'full_body' => 4,
                    default => 3,
                };

                if ($phase === 'adaptation') {
                    $sets = 2;
                    $repsFinal = '10-15';
                    $rirFinal = 4;
                } else {
                    $repsFinal = $reps;
                    $rirFinal = $rir;
                }

                $exerciseSets = $this->getSetsForMuscle($ex['muscle_group'] ?? '', $volume, $this->daysPerWeek, $phase);
                if ($exerciseSets > 0) {
                    $sets = min($sets, $exerciseSets);
                }

                return [
                    'id'    => $ex['id'],
                    'sets'  => $sets,
                    'reps'  => $repsFinal,
                    'rest'  => $rest,
                    'rir'   => $rirFinal,
                    'notes' => $this->generateExerciseNotes($ex, $focus),
                ];
            }, $chosen, array_keys($chosen));

            $name = $config['name'];
            $nameStr = is_callable($name) ? $name($dayIdx + 1) : $name;

            $days[] = [
                'name'     => $nameStr,
                'focus'    => $focus,
                'exercises' => $exercises,
            ];
        }

        return $days;
    }

    private function isSmallMuscle(string $muscleGroup): bool
    {
        return in_array($muscleGroup, $this->smallMuscleGroups);
    }

    private function isPreferredMuscle(string $muscleGroup): bool
    {
        if (empty($this->preferredMuscles)) {
            return false;
        }

        $normalized = strtolower(str_replace([' ', '-'], '', $muscleGroup));
        foreach ($this->preferredMuscles as $pref) {
            $prefNorm = strtolower(str_replace([' ', '-'], '', $pref));
            if (str_contains($normalized, $prefNorm) || str_contains($prefNorm, $normalized)) {
                return true;
            }
        }
        return false;
    }

    private function musclePriorityScore(array $exercise): int
    {
        $muscle = $exercise['muscle_group'] ?? '';
        $pattern = $exercise['movement_pattern'] ?? '';

        $score = 0;

        if ($this->isPreferredMuscle($muscle)) {
            $score += 10;
        }

        if (in_array($pattern, ['push', 'pull', 'squat', 'hinge'])) {
            $score += 5;
        }

        if ($exercise['level'] === 'beginner') {
            $score -= 2;
        }

        return $score;
    }

    private function compoundFirst(array $a, array $b): int
    {
        $compoundPatterns = ['push', 'pull', 'squat', 'hinge', 'carry'];
        $aIsCompound = in_array($a['movement_pattern'] ?? '', $compoundPatterns);
        $bIsCompound = in_array($b['movement_pattern'] ?? '', $compoundPatterns);

        if ($aIsCompound && !$bIsCompound) return -1;
        if (!$aIsCompound && $bIsCompound) return 1;
        return 0;
    }

    private function getSetsForMuscle(string $muscleGroup, int $totalVolume, int $days, string $phase): int
    {
        if ($phase === 'adaptation') {
            return 2;
        }

        $perDay = (int) ceil($totalVolume / $days);
        $perDay = max(2, min(5, $perDay));

        return $perDay;
    }

    private function generateExerciseNotes(array $exercise, string $focus): string
    {
        $name = $exercise['name'] ?? '';
        $pattern = $exercise['movement_pattern'] ?? '';
        $muscleGroup = $exercise['muscle_group'] ?? '';

        $cues = [
            'push'      => 'Mantén los codos a 45° para proteger el hombro. Controla la excentrica.',
            'pull'      => 'Scapulas pinned al inicio. Tira con los dorsales, no solo los brazos.',
            'squat'     => 'Rodillas alineadas con las puntas de los pies. Core activado durante todo el movimiento.',
            'hinge'     => 'Hip hinge: empujar las caderas hacia atrás primero. Espalda plana.',
            'core'      => 'Coordina respiración con el movimiento. Mantén el core tight pero sin contener.',
            'carry'     => 'Escápulas retraídas y hombros arriba. Paso controlado, torso erguido.',
        ];

        $ageAdjustments = '';
        if ($this->age >= 50) {
            $ageAdjustments = ' Evita carga excesiva en columna. Control total en todo el rango.';
        } elseif ($this->age >= 35) {
            $ageAdjustments = ' Incluye calentamiento articular previo. Descansa 90s entre series.';
        }

        return ($cues[$pattern] ?? 'Controla el movimiento en todo el rango.') . $ageAdjustments;
    }

    private function mapGoal(): string
    {
        return match ($this->user->goal) {
            'fat_loss'           => 'fat_loss',
            'muscle_gain',
            'body_recomposition' => 'hypertrophy',
            'strength'           => 'strength',
            'cardio', 'maintain' => 'endurance',
            'flexibility'        => 'mobility',
            default              => 'hypertrophy',
        };
    }

    private function generateRoutineName(string $phase): string
    {
        $phaseLabel = $phase === 'adaptation' ? 'Adaptación' : '';
        $level = ucfirst($this->userProfile['level']);
        $goalLabel = match ($this->user->goal) {
            'fat_loss' => 'Pérdida de grasa',
            'muscle_gain' => 'Hipertrofia',
            'body_recomposition' => 'Recomposición',
            'strength' => 'Fuerza',
            'maintain' => 'Mantenimiento',
            'cardio' => 'Cardio',
            'flexibility' => 'Flexibilidad',
            default => 'General',
        };

        if ($phaseLabel) {
            return "{$phaseLabel} {$level} · {$goalLabel}";
        }

        return "Rutina {$level} · {$goalLabel}";
    }

    private function generateRoutineDescription(string $phase, int $phaseWeeks): string
    {
        if ($phase === 'adaptation') {
            return "Fase de adaptación de {$phaseWeeks} semanas a menor volumen. Diseñada para readaptar tendones y sistema nervioso antes de la rutina objetivo. Vollständig científica (Bompa/NSCA).";
        }

        return match ($this->splitType) {
            'full_body' => 'Split de cuerpo completo. Todos los grupos se entrenan cada sesión, ideal para frecuencia máxima y principiantes.',
            'upper_lower' => 'Split torso-pierna. Equilibrio óptimo entre empuje, tirón y piernas con frecuencia 2 por grupo.',
            'ppl', 'ppl_x2' => 'Split Push/Pull/Legs. Enfoque específico por día para máximo estímulo y recuperación.',
            'upper_lower_emphasis' => 'Énfasis en piernas. Tres sesiones de pierna especializadas con dos sesiones de torso.',
            'weider' => 'División muscular por día. Cada sesión se enfoca en grupos específicos para máximo aislamiento.',
            default => 'Rutina personalizada según tu perfil, objetivo y preferencias.',
        };
    }
}