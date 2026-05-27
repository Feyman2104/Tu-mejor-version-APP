<?php

namespace App\Services;

use App\Models\User;
use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AICoachService
{
    private string $anthropicKey;
    private string $anthropicModel;
    private string $geminiKey;
    private string $geminiModel;
    private string $minimaxKey;
    private string $minimaxModel;
    private string $minimaxBaseUrl;

    public function __construct()
    {
        $this->anthropicKey   = config('services.anthropic.key', '');
        $this->anthropicModel = config('services.anthropic.model', 'claude-haiku-4-5-20251001');
        $this->geminiKey      = config('services.gemini.key', '');
        $this->geminiModel    = config('services.gemini.model', 'gemini-2.5-flash');
        $this->minimaxKey     = config('services.minimax.key', '');
        $this->minimaxModel   = config('services.minimax.model', 'MiniMax-Text-01');
        $this->minimaxBaseUrl = config('services.minimax.base_url', 'https://api.minimaxi.chat/v1');
    }

    public function streamChat(User $user, array $history, ?string $workoutContext = null): Generator
    {
        $systemPrompt = $this->buildSystemPrompt($user, $workoutContext);

        if ($this->minimaxKey) {
            try {
                yield from $this->streamMiniMax($systemPrompt, $history);
                return;
            } catch (\Throwable $e) {
                Log::warning('MiniMax streaming failed, falling back to Claude', ['error' => $e->getMessage()]);
            }
        }

        if ($this->anthropicKey) {
            try {
                yield from $this->streamClaude($systemPrompt, $history);
                return;
            } catch (\Throwable $e) {
                Log::warning('Claude Haiku failed, falling back to Gemini', ['error' => $e->getMessage()]);
            }
        }

        if ($this->geminiKey) {
            try {
                yield from $this->streamGemini($systemPrompt, $history);
                return;
            } catch (\Throwable $e) {
                Log::error('All AI providers failed', ['error' => $e->getMessage()]);
                yield 'Lo siento, ningún servicio de IA está disponible en este momento.';
                return;
            }
        }

        yield 'Lo siento, no hay ningún proveedor de IA configurado. Añade tu API key de MiniMax, Anthropic o Google Gemini en el archivo .env';
    }

    private function buildSystemPrompt(User $user, ?string $workoutContext = null): string
    {
        $levelLabel = match ($user->level) {
            'beginner'     => 'principiante (0-6 meses)',
            'intermediate' => 'intermedio (6 meses - 2 años)',
            'advanced'     => 'avanzado (+2 años)',
            default        => 'sin especificar',
        };

        $goalLabel = match ($user->goal) {
            'fat_loss'    => 'pérdida de grasa',
            'muscle_gain' => 'ganancia muscular',
            'strength'    => 'aumentar fuerza',
            'maintain'    => 'mantener forma física',
            'cardio'      => 'resistencia cardiovascular',
            'body_recomposition' => 'recomposición corporal (ganar músculo y perder grasa)',
            'flexibility' => 'mejorar flexibilidad/movilidad',
            default       => 'mejorar condición física general',
        };

        $equipment = implode(', ', $user->equipment ?? ['ninguno']);
        $injuriesList = collect($user->injuries ?? [])
            ->filter(fn ($i) => is_array($i) ? ($i['zone'] ?? '') !== 'none' : $i !== 'none')
            ->map(fn ($i) => is_array($i) ? ($i['zone'] ?? '') : $i)
            ->implode(', ');
        $injuriesStr = $injuriesList ?: 'ninguna';

        $age = $user->age ?? 30;
        $activityLabel = match ($user->activity_level ?? 'lightly_active') {
            'sedentary' => 'sedentario (oficina, <5k pasos)',
            'lightly_active' => 'poco activo (5k-8k pasos)',
            'active' => 'activo (8k-12k pasos)',
            'very_active' => 'muy activo (>12k pasos o trabajo físico)',
            default => 'poco activo',
        };

        $bmr = $user->weight_kg && $user->height_cm
            ? round((10 * $user->weight_kg) + (6.25 * $user->height_cm) - (5 * $age) + 5)
            : null;

        $tdee = $bmr
            ? round($bmr * match ($user->activity_level ?? 'lightly_active') {
                'sedentary' => 1.2,
                'lightly_active' => 1.375,
                'active' => 1.55,
                'very_active' => 1.725,
                default => 1.375,
            })
            : null;

        $macroInfo = '';
        if ($tdee) {
            $targetKcal = match ($user->goal) {
                'fat_loss' => round($tdee * 0.80),
                'muscle_gain', 'body_recomposition' => round($tdee * 1.15),
                default => $tdee,
            };
            $proteinG = round(($user->weight_kg ?? 70) * 2.0);
            $fatG = round(($user->weight_kg ?? 70) * 0.8);
            $carbsG = round(($targetKcal - ($proteinG * 4) - ($fatG * 9)) / 4);
            $macroInfo = "METAS NUTRICIONALES: {$targetKcal} kcal/día | Proteína: {$proteinG}g | Grasa: {$fatG}g | Carbos: {$carbsG}g";
        }

        $routineInfo = '';
        $activeRoutine = $user->routines()->where('is_active', true)->with('days.exercises')->first();
        if ($activeRoutine) {
            $todayDay = $activeRoutine->days->first();
            if ($todayDay) {
                $exerciseNames = $todayDay->exercises->take(5)->map(fn ($e) => $e->exercise->name ?? 'ejercicio')->join(', ');
                $routineInfo = "RUTINA ACTIVA: {$activeRoutine->name} | Día de hoy: {$todayDay->name} | Ejercicios: {$exerciseNames}";
            }
        }

        $prompt = <<<PROMPT
Eres el Coach IA de "Tu Mejor Versión", entrenador personal certificado con base científica NSCA/ACSM.
Tu misión: dar consejos ESPECÍFICOS y ACCIONABLES, nunca genéricos.

PERFIL DEL USUARIO:
- Nombre: {$user->name}
- Edad: {$age} años
- Nivel: {$levelLabel}
- Objetivo: {$goalLabel}
- Actividad diaria: {$activityLabel}
- Equipamiento disponible: {$equipment}
- Lesiones/limitaciones: {$injuriesStr}
{$macroInfo}
{$routineInfo}

PRINCIPIOS CIENTÍFICOS (aplica siempre):

VOLUMEN DE ENTRENAMIENTO (Schoenfeld et al. 2017):
- Mantenimiento: 4-6 series/semana por grupo
- Mínimo efectivo: 8-10 series/semana
- Máximo adaptativo: 12-20 series/semana (zona óptima de crecimiento)
- Máximo recuperable: 20-25 series/semana (techo antes de sobreentrenar)

FRECUENCIA (Schoenfeld 2016/2019):
- Mínimo 2 sesiones/semana por grupo muscular (nunca menos)
- A mayor volumen, repartir en más sesiones mejora la calidad

RANGOS DE REPETICIONES POR OBJETIVO:
- Hipertrofia: 6-20 reps · RIR 1-3 · descanso 60-120s
- Fuerza máxima: 1-5 reps · RIR 0-2 · descanso 3-5 min
- Resistencia: 15+ reps · RIR 3+ · descanso <60s

PROGRESIÓN (doble progresión):
→ Completó TODAS las series en el tope del rango → subir 2.5-5 kg la próxima sesión
→ No llegó al piso del rango → mantener o bajar 5-10%
→ Máximo +10% de carga en una semana

LESIONES → RECOMENDAR, no excluir:
- Rodilla: reducir profundidad, tempo 3-0-3, preferir cadena cerrada
- Lumbar: preferir bisagra con torso vertical, core anti-extensión
- Hombro: rango sin dolor, press neutro en vez de tras nuca
- Muñeca: agarre neutro con mancuernas
- Cadera: evitar flexión extrema con carga

REGLAS DE RESPUESTA — OBLIGATORIAS:
1. Máximo 3 párrafos O una lista de 5 puntos. Nunca más largo.
2. Usa los datos REALES del usuario. "Sube a 58.5 kg" es mejor que "sube gradualmente".
3. NUNCA repitas la rutina completa — el usuario ya la tiene en pantalla.
4. Si hay sesión activa: comenta ESA sesión específica (pesos, reps, ejercicios concretos).
5. Si pregunta por un ejercicio → responde ese ejercicio solamente con técnica y errores comunes.
6. Máximo 2 emojis por respuesta. Responde siempre en español.
7. Si algo está fuera del fitness/salud, redirige amablemente en 1 frase.
8. Para preguntas de nutrición, usa los macros del usuario (kcal, proteína, etc.) para dar respuestas personalizadas.
PROMPT;

        if ($workoutContext) {
            $prompt .= "\n\n" . $workoutContext;
        }

        return $prompt;
    }

    private function streamClaude(string $system, array $history): Generator
    {
        $response = Http::withHeaders([
            'x-api-key'         => $this->anthropicKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type'      => 'application/json',
        ])->withOptions(['stream' => true])
          ->timeout(60)
          ->post('https://api.anthropic.com/v1/messages', [
              'model'      => $this->anthropicModel,
              'max_tokens' => 1024,
              'system'     => $system,
              'stream'     => true,
              'messages'   => $history,
          ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Claude API error: ' . $response->status());
        }

        yield from $this->parseSSEStream(
            $response->toPsrResponse()->getBody(),
            function (array $data): ?string {
                if (($data['type'] ?? '') === 'content_block_delta') {
                    return $data['delta']['text'] ?? null;
                }
                return null;
            }
        );
    }

    private function streamGemini(string $system, array $history): Generator
    {
        $contents = [];
        foreach ($history as $msg) {
            $contents[] = [
                'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:streamGenerateContent?key={$this->geminiKey}&alt=sse";

        $response = Http::withOptions(['stream' => true])
            ->timeout(60)
            ->post($url, [
                'system_instruction' => ['parts' => [['text' => $system]]],
                'contents'           => $contents,
                'generationConfig'   => ['maxOutputTokens' => 1024],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Gemini API error: ' . $response->status());
        }

        yield from $this->parseSSEStream(
            $response->toPsrResponse()->getBody(),
            function (array $data): ?string {
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }
        );
    }

    private function streamMiniMax(string $system, array $history): Generator
    {
        $contents = [];
        foreach ($history as $msg) {
            $contents[] = [
                'role'    => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content'],
            ];
        }

        $url = "{$this->minimaxBaseUrl}/text/chatcompletion_v2";

        $payload = [
            'model'       => $this->minimaxModel,
            'messages'    => array_merge(
                [['role' => 'system', 'content' => $system]],
                $contents
            ),
            'stream'      => true,
            'max_tokens'  => 1024,
            'temperature' => 0.7,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->minimaxKey}",
            'Content-Type'  => 'application/json',
        ])->withOptions(['stream' => true])
          ->timeout(90)
          ->post($url, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('MiniMax API error: ' . $response->status() . ' ' . $response->body());
        }

        yield from $this->filterThinkBlocks(
            $this->parseSSEStream(
                $response->toPsrResponse()->getBody(),
                function (array $data): ?string {
                    foreach ($data['choices'] ?? [] as $choice) {
                        $content = $choice['delta']['content'] ?? null;
                        if ($content) return $content;
                    }
                    return null;
                }
            )
        );
    }

    /**
     * Filtra bloques <think>...</think> que MiniMax-M2.7 emite como razonamiento interno.
     */
    private function filterThinkBlocks(\Generator $stream): \Generator
    {
        $buffer   = '';
        $inThink  = false;

        foreach ($stream as $chunk) {
            $buffer .= $chunk;
            $output  = '';

            while ($buffer !== '') {
                if ($inThink) {
                    $end = strpos($buffer, '</think>');
                    if ($end !== false) {
                        $buffer  = substr($buffer, $end + 8);
                        $inThink = false;
                    } else {
                        break;
                    }
                } else {
                    $start = strpos($buffer, '<think>');
                    if ($start !== false) {
                        $output .= substr($buffer, 0, $start);
                        $buffer  = substr($buffer, $start + 7);
                        $inThink = true;
                    } else {
                        // Guarda los últimos 7 chars por si llega un <think> partido entre chunks
                        if (strlen($buffer) > 7) {
                            $safe    = strlen($buffer) - 7;
                            $output .= substr($buffer, 0, $safe);
                            $buffer  = substr($buffer, $safe);
                        }
                        break;
                    }
                }
            }

            if ($output !== '') yield $output;
        }

        if (! $inThink && $buffer !== '') yield $buffer;
    }

    /**
     * Lee un stream SSE en chunks reales y llama al extractor por cada evento.
     * Reemplaza el anti-patrón ->body() que bufferizaba la respuesta completa.
     */
    private function parseSSEStream(\Psr\Http\Message\StreamInterface $stream, callable $extractor): Generator
    {
        $buffer = '';

        while (! $stream->eof()) {
            $buffer .= $stream->read(1024);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line   = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if (! $line || ! str_starts_with($line, 'data: ')) continue;

                $json = substr($line, 6);
                if ($json === '[DONE]' || $json === 'null') return;

                $data = json_decode($json, true);
                if (! $data) continue;

                $text = $extractor($data);
                if ($text) yield $text;
            }
        }
    }
}
