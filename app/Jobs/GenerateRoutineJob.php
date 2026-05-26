<?php

namespace App\Jobs;

use App\Models\Exercise;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateRoutineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly User $user,
        private readonly ?string $notes = null,
    ) {}

    public function handle(): void
    {
        $routine = $this->generateWithAI();

        if ($routine) {
            Log::info("Routine generated for user {$this->user->id}", ['routine_id' => $routine->id]);
        }
    }

    private function generateWithAI(): ?Routine
    {
        $prompt = $this->buildPrompt();

        // Try Anthropic first
        $json = $this->callAnthropic($prompt) ?? $this->callGemini($prompt);

        if (! $json) {
            $this->generateFallbackRoutine();
            return null;
        }

        return $this->persistRoutine($json);
    }

    private function buildPrompt(): string
    {
        $equipment = implode(', ', $this->user->equipment ?? ['bodyweight']);

        // Injuries may be stored as plain strings (old format) or {zone, notes} objects (new)
        $injuries = collect($this->user->injuries ?? [])
            ->filter(fn ($i) => is_array($i) ? ($i['zone'] ?? '') !== 'none' : $i !== 'none')
            ->map(fn ($i) => is_array($i)
                ? $i['zone'] . (!empty($i['notes']) ? " ({$i['notes']})" : '')
                : $i)
            ->implode(', ');

        $goal = match ($this->user->goal) {
            'fat_loss'           => 'pérdida de grasa',
            'muscle_gain'        => 'ganancia muscular',
            'strength'           => 'fuerza',
            'maintain'           => 'mantenimiento',
            'flexibility'        => 'flexibilidad',
            'cardio'             => 'resistencia cardiovascular',
            'body_recomposition' => 'recomposición corporal (ganar músculo y perder grasa simultáneamente)',
            default              => 'condición física general',
        };

        $extras = [];
        if ($this->user->days_per_week)            $extras[] = "Días disponibles: {$this->user->days_per_week} por semana";
        if ($this->user->session_duration_minutes) $extras[] = "Duración por sesión: {$this->user->session_duration_minutes} minutos";
        if ($this->user->place) {
            $placeLabel = match ($this->user->place) { 'home' => 'casa', 'gym' => 'gimnasio', 'both' => 'casa y gimnasio', default => '' };
            if ($placeLabel) $extras[] = "Lugar: {$placeLabel}";
        }
        if (!empty($this->user->preferred_muscles)) {
            $extras[] = 'Músculos prioritarios: ' . implode(', ', $this->user->preferred_muscles);
        }
        if ($this->notes) $extras[] = "Notas: {$this->notes}";

        $extrasText   = $extras ? "\n- " . implode("\n- ", $extras) : '';
        $daysTarget   = $this->user->days_per_week
            ? "exactamente {$this->user->days_per_week} días de entrenamiento"
            : 'entre 3 y 5 días de entrenamiento';

        return <<<PROMPT
Genera una rutina de entrenamiento semanal personalizada en formato JSON para:
- Nivel: {$this->user->level}
- Objetivo: {$goal}
- Equipamiento: {$equipment}
- Lesiones/limitaciones: {$injuries ?: 'ninguna'}{$extrasText}

La rutina debe tener {$daysTarget} por semana.
Responde SOLO con JSON válido, sin explicación extra, con esta estructura exacta:

{
  "name": "Nombre descriptivo de la rutina",
  "description": "Descripción breve (1-2 oraciones)",
  "days_per_week": 4,
  "days": [
    {
      "day_number": 1,
      "name": "Día 1 - Descripción",
      "focus": "push|pull|legs|full_body|cardio|rest",
      "exercises": [
        {
          "exercise_name": "Nombre del ejercicio en español",
          "sets": 3,
          "reps": "8-12",
          "rest_seconds": 90,
          "notes": "Indicación técnica breve"
        }
      ]
    }
  ]
}
PROMPT;
    }

    private function callAnthropic(string $prompt): ?array
    {
        $key = config('services.anthropic.key');
        if (! $key) return null;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $key,
                'anthropic-version' => '2023-06-01',
            ])->timeout(90)->post('https://api.anthropic.com/v1/messages', [
                'model'      => config('services.anthropic.model', 'claude-haiku-4-5-20251001'),
                'max_tokens' => 2048,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]);

            $text = $response->json('content.0.text') ?? '';
            return $this->extractJson($text);
        } catch (\Throwable $e) {
            Log::warning('Anthropic routine generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function callGemini(string $prompt): ?array
    {
        $key = config('services.gemini.key');
        if (! $key) return null;

        try {
            $model    = config('services.gemini.model', 'gemini-1.5-flash');
            $response = Http::timeout(90)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}",
                ['contents' => [['parts' => [['text' => $prompt]]]]]
            );

            $text = $response->json('candidates.0.content.parts.0.text') ?? '';
            return $this->extractJson($text);
        } catch (\Throwable $e) {
            Log::warning('Gemini routine generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function extractJson(string $text): ?array
    {
        preg_match('/\{.*\}/s', $text, $matches);
        if (empty($matches[0])) return null;

        $data = json_decode($matches[0], true);
        return is_array($data) ? $data : null;
    }

    /** Mapea el objetivo del usuario al ENUM de la tabla routines. */
    private function mappedGoal(): string
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

    private function persistRoutine(array $data): Routine
    {
        // Desactiva rutinas previas para que solo haya una activa
        $this->user->routines()->update(['is_active' => false]);

        $routine = $this->user->routines()->create([
            'name'            => $data['name'] ?? 'Mi rutina personalizada',
            'description'     => $data['description'] ?? null,
            'goal'            => $this->mappedGoal(),
            'generated_by_ai' => true,
            'days_per_week'   => $data['days_per_week'] ?? count($data['days']),
            'is_active'       => true,
        ]);

        foreach ($data['days'] as $dayData) {
            $day = $routine->days()->create([
                'day_number' => $dayData['day_number'],
                'name'       => $dayData['name'],
                'focus'      => $dayData['focus'] ?? 'full_body',
            ]);

            foreach ($dayData['exercises'] as $order => $exData) {
                $exercise = Exercise::where('name', 'like', '%' . $exData['exercise_name'] . '%')
                    ->first();

                if ($exercise) {
                    $day->exercises()->create([
                        'exercise_id' => $exercise->id,
                        'sets'        => $exData['sets'] ?? 3,
                        'reps'        => $exData['reps'] ?? '10',
                        'rest_seconds'=> $exData['rest_seconds'] ?? 90,
                        'notes'       => $exData['notes'] ?? null,
                        'order'       => $order + 1,
                    ]);
                }
            }
        }

        return $routine;
    }

    private function generateFallbackRoutine(): void
    {
        // Simple 3-day full-body routine using home/bodyweight exercises
        $exercises = Exercise::whereIn('environment', ['home', 'both'])
            ->where('level', $this->user->level ?? 'beginner')
            ->limit(12)
            ->get();

        if ($exercises->isEmpty()) {
            $exercises = Exercise::whereIn('environment', ['home', 'both'])->limit(12)->get();
        }

        if ($exercises->isEmpty()) return;

        $this->user->routines()->update(['is_active' => false]);

        $routine = $this->user->routines()->create([
            'name'            => 'Rutina Básica 3 Días',
            'description'     => 'Plan de entrenamiento con peso corporal generado automáticamente.',
            'goal'            => $this->mappedGoal(),
            'generated_by_ai' => true,
            'days_per_week'   => 3,
            'is_active'       => true,
        ]);

        $chunks   = $exercises->chunk(4);
        $dayNames = ['Día 1 - Full Body A', 'Día 2 - Full Body B', 'Día 3 - Full Body C'];

        foreach ($chunks->take(3) as $i => $chunk) {
            $day = $routine->days()->create([
                'day_number' => $i + 1,
                'name'       => $dayNames[$i],
                'focus'      => 'full_body',
            ]);

            foreach ($chunk as $order => $exercise) {
                $day->exercises()->create([
                    'exercise_id'  => $exercise->id,
                    'sets'         => 3,
                    'reps'         => '10-12',
                    'rest_seconds' => 60,
                    'order'        => $order + 1,
                ]);
            }
        }
    }
}
