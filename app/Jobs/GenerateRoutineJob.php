<?php

namespace App\Jobs;

use App\Models\Exercise;
use App\Models\ExerciseContraindication;
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
        $service = new \App\Services\RoutineGeneratorService($this->user);
        $routine = $service->generate($this->notes);

        if ($routine) {
            Log::info("Routine generated for user {$this->user->id}", ['routine_id' => $routine->id]);
        }
    }

    private function generateWithAI(): ?Routine
    {
        $prompt = $this->buildPrompt();

        // Try Anthropic first
        $json = $this->callAnthropic($prompt) ?? $this->callGemini($prompt);

        // Si la IA no respondió o devolvió un JSON inválido (sin días), usamos el
        // fallback determinista que SIEMPRE genera la rutina con los días elegidos.
        if (! $json || empty($json['days']) || ! is_array($json['days'])) {
            return $this->generateFallbackRoutine();
        }

        $routine = $this->persistRoutine($json);

        // Si la IA usó nombres que no coinciden con el catálogo y la rutina quedó
        // sin ejercicios, la descartamos y caemos al fallback determinista.
        $totalExercises = $routine->days()
            ->withCount('exercises')
            ->get()
            ->sum('exercises_count');

        if ($totalExercises === 0) {
            $routine->forceDelete();
            return $this->generateFallbackRoutine();
        }

        return $routine;
    }

    private function buildPrompt(): string
    {
        // ── Perfil físico ────────────────────────────────────────────────────────
        $equipment    = implode(', ', $this->user->equipment ?? ['peso corporal']);
        $injuriesRaw  = collect($this->user->injuries ?? [])
            ->filter(fn ($i) => is_array($i) ? ($i['zone'] ?? '') !== 'none' : $i !== 'none')
            ->map(fn ($i) => is_array($i)
                ? $i['zone'] . (!empty($i['notes']) ? " ({$i['notes']})" : '')
                : $i);
        $injuriesText = $injuriesRaw->isNotEmpty() ? $injuriesRaw->implode(', ') : 'ninguna';

        $goal = match ($this->user->goal) {
            'fat_loss'           => 'pérdida de grasa',
            'muscle_gain'        => 'ganancia muscular',
            'strength'           => 'fuerza máxima',
            'maintain'           => 'mantenimiento',
            'flexibility'        => 'flexibilidad / movilidad',
            'cardio'             => 'resistencia cardiovascular',
            'body_recomposition' => 'recomposición corporal (ganar músculo y perder grasa simultáneamente)',
            default              => 'condición física general',
        };

        $levelLabel = match ($this->user->level) {
            'beginner'     => 'principiante (0–6 meses de experiencia)',
            'intermediate' => 'intermedio (6 meses – 2 años de experiencia)',
            'advanced'     => 'avanzado (+2 años de experiencia)',
            default        => 'principiante',
        };

        $placeLabel = match ($this->user->place) {
            'home'  => 'casa',
            'gym'   => 'gimnasio',
            'both'  => 'casa y gimnasio',
            default => 'gimnasio',
        };

        $mobilityLabel = match ($this->user->mobility) {
            'good'    => 'buena — rango completo de movimiento sin restricciones',
            'average' => 'regular — algunas limitaciones en ciertos movimientos',
            'limited' => 'limitada — restricciones importantes de rango de movimiento',
            default   => 'no especificada',
        };

        $age     = $this->user->age     ? "{$this->user->age} años"                    : 'no especificada';
        $weight  = $this->user->weight_kg  ? "{$this->user->weight_kg} kg"             : 'no especificado';
        $height  = $this->user->height_cm  ? "{$this->user->height_cm} cm"             : 'no especificada';
        $muscles = !empty($this->user->preferred_muscles)
            ? implode(', ', $this->user->preferred_muscles)
            : 'sin preferencia específica';

        $daysTarget = $this->user->days_per_week
            ? "exactamente {$this->user->days_per_week} días de entrenamiento"
            : 'entre 3 y 5 días de entrenamiento';

        $duration = $this->user->session_duration_minutes ?? 60;

        // ── Ejercicios por sesión según duración ─────────────────────────────────
        $exercisesPerSession = match (true) {
            $duration <= 30  => '3–4',
            $duration <= 45  => '4–5',
            $duration <= 60  => '5–6',
            $duration <= 75  => '6–7',
            default          => '7–9',
        };

        // ── Parámetros científicos por objetivo (NSCA/ACSM) ──────────────────────
        $volumeParams = match ($this->user->goal) {
            'fat_loss' =>
                "Sets: 3–4 | Reps: 12–15 | Descanso: 30–60 s | Intensidad: moderada-alta\n" .
                "Prioriza circuitos y supersets para maximizar gasto calórico. Incluye cardio y movimientos compuestos.",
            'muscle_gain' =>
                "Sets: 3–5 | Reps: 8–12 | Descanso: 60–90 s | Intensidad: alta (RIR 1–3)\n" .
                "Alterna compuestos (60%) con aislamientos (40%). Aplica sobrecarga progresiva en cada sesión.",
            'strength' =>
                "Sets: 4–6 | Reps: 3–6 | Descanso: 3–5 min | Intensidad: muy alta (RIR 0–2)\n" .
                "Prioriza movimientos compuestos (sentadilla, peso muerto, press, dominadas). Volumen bajo, calidad técnica máxima.",
            'maintain' =>
                "Sets: 3 | Reps: 10–12 | Descanso: 60 s | Intensidad: moderada\n" .
                "Selección equilibrada de grupos musculares. No es necesario fallo muscular.",
            'flexibility' =>
                "Sets: 2–3 | Reps: 15–20 (o 30–60 s en isométricos) | Descanso: 30–45 s\n" .
                "Incluye movimientos de rango completo, movilidad articular y estiramientos activos.",
            'cardio' =>
                "Sets: 3–4 | Reps: 15–20 | Descanso: 30–45 s\n" .
                "Prioriza movimientos funcionales de alta repetición, circuitos metabólicos y ejercicios cardiovasculares.",
            'body_recomposition' =>
                "Sets: 3–4 | Reps: 10–15 | Descanso: 60 s | Intensidad: moderada-alta\n" .
                "Combina compuestos (fuerza base) con circuitos de alta repetición. Déficit calórico moderado.",
            default =>
                "Sets: 3 | Reps: 10–12 | Descanso: 60 s | Intensidad: moderada",
        };

        // ── Reglas de adaptación por movilidad ───────────────────────────────────
        $mobilityRules = match ($this->user->mobility) {
            'limited' =>
                "- EVITA: press por encima de la cabeza, sentadillas profundas, peso muerto convencional, movimientos de rotación de columna.\n" .
                "- USA: máquinas en lugar de peso libre, ejercicios en rango parcial de movimiento, posiciones sentadas o recostadas.",
            'average' =>
                "- Usa ejercicios estándar pero evita variaciones con rango extremo de movimiento.\n" .
                "- Incluye trabajo de movilidad al inicio de cada sesión.",
            default   => '',
        };

        // ── Reglas de adaptación por edad ────────────────────────────────────────
        $age_val = $this->user->age ?? 0;
        $ageRules = match (true) {
            $age_val > 0 && $age_val < 20 =>
                "- Evita cargas máximas en placa de crecimiento. Prioriza técnica sobre intensidad.",
            $age_val >= 50 =>
                "- Aumenta el descanso mínimo a 90 s entre series.\n" .
                "- Prefiere 12–15 repeticiones con carga moderada.\n" .
                "- Evita ejercicios con alta compresión espinal (sentadilla con barra, peso muerto pesado).",
            $age_val >= 35 =>
                "- Incluye al menos 1 día extra de recuperación activa si los días lo permiten.\n" .
                "- Descanso mínimo de 60 s entre series.",
            default => '',
        };

        // ── Reglas por lesiones ───────────────────────────────────────────────────
        $injuryRules = '';
        if ($injuriesRaw->isNotEmpty()) {
            $injuryRules = "- LESIONES ACTIVAS — aplica estas restricciones ESTRICTAMENTE:\n";
            foreach ($injuriesRaw->all() as $inj) {
                $zone  = is_array($inj) ? ($inj['zone'] ?? '') : $inj;
                $notes = is_array($inj) && !empty($inj['notes']) ? " ({$inj['notes']})" : '';
                $rule  = match ($zone) {
                    'shoulder' => "  • Hombro{$notes}: EVITA press militar, elevaciones laterales pesadas, jalones tras nuca, cualquier movimiento doloroso sobre la cabeza.",
                    'knee'     => "  • Rodilla{$notes}: EVITA sentadillas profundas, prensa con mucho peso, zancadas largas. Permite sentadillas parciales y ejercicios en cadena cerrada de bajo impacto.",
                    'back'     => "  • Lumbar{$notes}: EVITA peso muerto convencional, buenos días, rotaciones con carga. Permite ejercicios de core estabilizador y peso muerto sumo si hay control.",
                    'wrist'    => "  • Muñeca{$notes}: EVITA flexiones de muñeca con carga, press con barra en agarre estrecho. Prefiere mancuernas con agarre neutro.",
                    'hip'      => "  • Cadera{$notes}: EVITA sentadilla profunda con carga, hip thrust pesado en fase aguda. Permite trabajo de glúteos sin flexión extrema.",
                    'ankle'    => "  • Tobillo{$notes}: EVITA ejercicios de salto, zancadas con impacto. Prefiere ejercicios sentados o con apoyo.",
                    'neck'     => "  • Cuello{$notes}: EVITA encogimientos de hombros pesados, jalones tras nuca. Refuerza romboides y trapecio medio con cargas bajas.",
                    default    => "  • {$zone}{$notes}: adapta ejercicios que involucren esa zona.",
                };
                $injuryRules .= $rule . "\n";
            }
        }

        // ── Catálogo filtrado por entorno + nivel + sin contraindicaciones ────────
        // Niveles permitidos: acumulativo (intermedio puede hacer ejercicios de principiante).
        $allowedLevels = match ($this->user->level) {
            'intermediate' => ['beginner', 'intermediate'],
            'advanced'     => ['beginner', 'intermediate', 'advanced'],
            default        => ['beginner'],
        };

        // Excluir ejercicios contraindicados para las lesiones del usuario.
        $injuryZones = collect($this->user->injuries ?? [])
            ->filter(fn ($i) => is_array($i) ? ($i['zone'] ?? '') !== 'none' : $i !== 'none')
            ->map(fn ($i) => is_array($i) ? ($i['zone'] ?? '') : $i)
            ->map(fn ($zone) => match ($zone) {
                'back'     => 'lumbar',
                'knee'     => 'rodilla',
                'shoulder' => 'hombro',
                'wrist'    => 'muneca',
                'hip'      => 'cadera',
                default    => null,
            })
            ->filter()
            ->unique()
            ->values();

        $excludedIds = $injuryZones->isNotEmpty()
            ? ExerciseContraindication::whereIn('body_zone', $injuryZones->all())
                ->pluck('exercise_id')
                ->unique()
                ->all()
            : [];

        $catalog = Exercise::whereIn('environment', $this->environmentsForUser())
            ->whereIn('level', $allowedLevels)
            ->when(! empty($excludedIds), fn ($q) => $q->whereNotIn('id', $excludedIds))
            ->orderBy('muscle_group')
            ->orderBy('level')
            ->pluck('name')
            ->all();

        // Fallback si la combinación filtra demasiado: relajar nivel y luego entorno.
        if (count($catalog) < 8) {
            $catalog = Exercise::whereIn('environment', $this->environmentsForUser())
                ->when(! empty($excludedIds), fn ($q) => $q->whereNotIn('id', $excludedIds))
                ->orderBy('muscle_group')
                ->pluck('name')
                ->all();
        }
        if (count($catalog) < 8) {
            $catalog = Exercise::when(! empty($excludedIds), fn ($q) => $q->whereNotIn('id', $excludedIds))
                ->orderBy('muscle_group')
                ->pluck('name')
                ->all();
        }

        $exerciseList = implode("\n", array_map(fn ($n) => "- {$n}", $catalog));

        // ── Reglas opcionales a añadir solo si hay contenido ─────────────────────
        $adaptRules = trim(implode("\n", array_filter([$mobilityRules, $ageRules, $injuryRules])));
        $adaptSection = $adaptRules
            ? "\nREGLAS DE ADAPTACIÓN OBLIGATORIAS:\n{$adaptRules}"
            : '';

        $notesLine    = $this->notes ? "\nNotas adicionales del usuario: {$this->notes}" : '';
        // Precomputar para evitar operadores dentro de la interpolación del heredoc.
        $daysPerWeek  = $this->user->days_per_week ?? 3;
        $daysDisplay  = $this->user->days_per_week ?? '3–5';

        return <<<PROMPT
Eres un entrenador personal certificado (NSCA-CSCS, ACSM). Diseña una rutina de
entrenamiento semanal 100% personalizada para el siguiente usuario:

═══ PERFIL COMPLETO DEL USUARIO ═══
• Nombre:           {$this->user->name}
• Edad:             {$age}
• Peso / Altura:    {$weight} / {$height}
• Nivel:            {$levelLabel}
• Objetivo:         {$goal}
• Movilidad:        {$mobilityLabel}
• Lugar:            {$placeLabel}
• Equipamiento:     {$equipment}
• Lesiones:         {$injuriesText}
• Días / semana:    {$daysDisplay}
• Duración sesión:  {$duration} min → incluye {$exercisesPerSession} ejercicios por día
• Músculos prio.:   {$muscles}{$notesLine}

═══ PARÁMETROS DE ENTRENAMIENTO (NSCA/ACSM) ═══
{$volumeParams}{$adaptSection}

═══ CATÁLOGO DE EJERCICIOS DISPONIBLES ═══
REGLA CRÍTICA: usa ÚNICAMENTE ejercicios de esta lista. Copia el nombre EXACTAMENTE
como aparece (mismas palabras, tildes y capitalización). NO inventes ejercicios fuera
de la lista.

{$exerciseList}

═══ INSTRUCCIONES DE DISEÑO ═══
1. La rutina DEBE tener {$daysTarget}.
2. Cada día debe tener entre {$exercisesPerSession} ejercicios (respeta la duración de {$duration} min).
3. Aplica los sets/reps/descanso del objetivo definido arriba a TODOS los ejercicios.
4. Prioriza los músculos indicados en los primeros días de la semana.
5. Asegura balance muscular: no sobrecargar el mismo grupo 2 días seguidos.
6. Nombra cada día de forma descriptiva (ej. "Día 1 · Pecho y Tríceps").
7. En el campo "notes" de cada ejercicio escribe una clave técnica específica y accionable.
8. Si hay lesiones, aplica las restricciones ESTRICTAMENTE — es una cuestión de seguridad.

Responde ÚNICAMENTE con JSON válido, sin texto adicional, con esta estructura exacta:

{
  "name": "Nombre descriptivo de la rutina (incluye nivel y objetivo)",
  "description": "Descripción de 1–2 oraciones que explique la lógica de la rutina",
  "days_per_week": {$daysPerWeek},
  "days": [
    {
      "day_number": 1,
      "name": "Día 1 · Descripción del enfoque",
      "focus": "push|pull|legs|full_body|cardio|rest",
      "exercises": [
        {
          "exercise_name": "Nombre EXACTO de la lista de arriba",
          "sets": 3,
          "reps": "8-12",
          "rest_seconds": 90,
          "notes": "Clave técnica específica y accionable"
        }
      ]
    }
  ]
}
PROMPT;
    }

    /** Entornos de ejercicio válidos según el lugar elegido por el usuario. */
    private function environmentsForUser(): array
    {
        return match ($this->user->place) {
            'home'  => ['home', 'both'],
            'gym'   => ['gym', 'both'],
            default => ['home', 'gym', 'both'],
        };
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
            $model    = config('services.gemini.model', 'gemini-2.5-flash');
            $response = Http::timeout(90)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature'      => 0.7,
                        'maxOutputTokens'  => 4096,
                        // Fuerza salida JSON pura (sin markdown) para que extractJson nunca falle.
                        'responseMimeType' => 'application/json',
                        // Desactiva el "thinking" de Gemini 2.5: sin esto consume el
                        // presupuesto de tokens pensando y trunca el JSON de la rutina.
                        'thinkingConfig'   => ['thinkingBudget' => 0],
                    ],
                ]
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

    /**
     * Empareja el nombre que devolvió la IA con un ejercicio real del catálogo.
     * Estrategia en cascada: exacto → contiene → solapamiento de palabras clave.
     */
    private function matchExercise(string $name): ?Exercise
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        // 1) Coincidencia exacta sin distinguir mayúsculas/acentos de capitalización
        $exact = Exercise::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($exact) {
            return $exact;
        }

        // 2) El nombre del catálogo contiene el de la IA (o al revés)
        $like = Exercise::where('name', 'like', '%' . $name . '%')->first();
        if ($like) {
            return $like;
        }

        // 3) Mejor solapamiento de palabras significativas (ignora "de", "con", "en", "y")
        $words = collect(preg_split('/\s+/', mb_strtolower($name)))
            ->filter(fn ($w) => mb_strlen($w) >= 4)
            ->values();

        if ($words->isEmpty()) {
            return null;
        }

        $query = Exercise::query();
        foreach ($words as $w) {
            $query->orWhere('name', 'like', '%' . $w . '%');
        }

        return $query->get()
            ->sortByDesc(fn ($ex) => $words->filter(
                fn ($w) => str_contains(mb_strtolower($ex->name), $w)
            )->count())
            ->first();
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

        $trainingDays = array_values(array_filter(
            array_map('intval', $this->user->training_days ?? []),
            fn ($d) => $d >= 1 && $d <= 7
        ));

        foreach (array_values($data['days']) as $index => $dayData) {
            $day = $routine->days()->create([
                'day_number' => $trainingDays[$index] ?? ($dayData['day_number'] ?? $index + 1),
                'name'       => $dayData['name'] ?? 'Día ' . ($index + 1),
                'focus'      => $dayData['focus'] ?? 'full_body',
            ]);

            foreach (($dayData['exercises'] ?? []) as $order => $exData) {
                if (empty($exData['exercise_name'])) {
                    continue;
                }

                $exercise = $this->matchExercise($exData['exercise_name']);

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

    /**
     * Rutina de respaldo determinista (sin IA). SIEMPRE genera exactamente
     * los días que el usuario eligió en el onboarding, respetando lugar,
     * nivel y objetivo. Garantiza que el usuario nunca llegue al dashboard
     * sin rutina aunque la IA esté caída o sin saldo.
     */
    private function generateFallbackRoutine(): ?Routine
    {
        $days = (int) ($this->user->days_per_week ?: 3);
        $days = max(1, min(7, $days));

        $environments = $this->environmentsForUser();

        // Pool de ejercicios con degradación progresiva si hay pocos.
        $pool = Exercise::whereIn('environment', $environments)
            ->where('level', $this->user->level ?? 'beginner')
            ->get();

        if ($pool->count() < 4) {
            $pool = Exercise::whereIn('environment', $environments)->get();
        }
        if ($pool->count() < 4) {
            $pool = Exercise::query()->get();
        }
        if ($pool->isEmpty()) {
            return null;
        }

        [$reps, $rest] = match ($this->mappedGoal()) {
            'strength'  => ['4-6', 180],
            'endurance' => ['15-20', 45],
            'mobility'  => ['10-12', 60],
            default     => ['8-12', 90], // hypertrophy / fat_loss
        };

        // Enfoque por día según el número de días disponibles.
        $rotations = [
            1 => ['full_body'],
            2 => ['full_body', 'full_body'],
            3 => ['push', 'pull', 'legs'],
            4 => ['push', 'pull', 'legs', 'full_body'],
            5 => ['push', 'pull', 'legs', 'full_body', 'cardio'],
            6 => ['push', 'pull', 'legs', 'push', 'pull', 'legs'],
            7 => ['push', 'pull', 'legs', 'full_body', 'push', 'pull', 'cardio'],
        ];
        $focusByDay = $rotations[$days];

        $patternsByFocus = [
            'push'      => ['push'],
            'pull'      => ['pull'],
            'legs'      => ['squat', 'hinge'],
            'full_body' => ['push', 'pull', 'squat', 'hinge', 'core', 'carry'],
            'cardio'    => ['core', 'carry', 'squat'],
        ];
        $focusLabels = [
            'push'      => 'Empuje (Pecho · Hombro · Tríceps)',
            'pull'      => 'Tirón (Espalda · Bíceps)',
            'legs'      => 'Pierna',
            'full_body' => 'Cuerpo completo',
            'cardio'    => 'Cardio / Acondicionamiento',
        ];

        $trainingDays = array_values(array_filter(
            array_map('intval', $this->user->training_days ?? []),
            fn ($d) => $d >= 1 && $d <= 7
        ));

        $this->user->routines()->update(['is_active' => false]);

        $routine = $this->user->routines()->create([
            'name'            => "Rutina Personalizada {$days} Días",
            'description'     => 'Plan generado automáticamente según tu nivel, equipo y objetivo.',
            'goal'            => $this->mappedGoal(),
            'generated_by_ai' => true,
            'days_per_week'   => $days,
            'is_active'       => true,
        ]);

        $perDay = 5;

        foreach ($focusByDay as $i => $focus) {
            $day = $routine->days()->create([
                'day_number' => $trainingDays[$i] ?? ($i + 1),
                'name'       => 'Día ' . ($i + 1) . ' · ' . ($focusLabels[$focus] ?? 'Entrenamiento'),
                'focus'      => $focus,
            ]);

            // Preferimos ejercicios cuyo patrón coincida con el enfoque del día;
            // si no alcanzan, completamos con el resto del pool. Rotamos el orden
            // por día para que los días repetidos no salgan idénticos.
            $patterns  = $patternsByFocus[$focus] ?? [];
            $matching  = $patterns
                ? $pool->whereIn('movement_pattern', $patterns)->values()
                : collect();
            $rest_pool = $pool->whereNotIn('id', $matching->pluck('id'))->values();

            $ordered = $matching->merge($rest_pool)->values();
            if ($ordered->isEmpty()) {
                $ordered = $pool;
            }

            $offset  = ($i * $perDay) % max(1, $ordered->count());
            $chosen  = $ordered->slice($offset)->merge($ordered->slice(0, $offset))
                ->unique('id')
                ->take($perDay)
                ->values();

            foreach ($chosen as $order => $exercise) {
                $day->exercises()->create([
                    'exercise_id'  => $exercise->id,
                    'sets'         => 3,
                    'reps'         => $reps,
                    'rest_seconds' => $rest,
                    'order'        => $order + 1,
                ]);
            }
        }

        return $routine;
    }
}
