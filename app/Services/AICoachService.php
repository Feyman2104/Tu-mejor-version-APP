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

    public function streamChat(User $user, array $history, ?string $workoutContext = null, ?string $postureContext = null): Generator
    {
        $systemPrompt = $this->buildSystemPrompt($user, $workoutContext, $postureContext);

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

    private function buildSystemPrompt(User $user, ?string $workoutContext = null, ?string $postureContext = null): string
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

        // Mifflin-St Jeor: hombre +5, mujer -161, otro/desconocido promedio
        $bmrOffset = match ($user->sex ?? 'other') {
            'male'   => 5,
            'female' => -161,
            default  => -78,
        };
        $bmr = $user->weight_kg && $user->height_cm
            ? round((10 * $user->weight_kg) + (6.25 * $user->height_cm) - (5 * $age) + $bmrOffset)
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
        $activeRoutine = $user->routines()->where('is_active', true)->with('days.exercises.exercise')->first();
        if ($activeRoutine) {
            $todayDay = $activeRoutine->days->first();
            if ($todayDay) {
                $exerciseNames = $todayDay->exercises->take(5)->map(fn ($e) => $e->exercise?->name ?? 'ejercicio')->join(', ');
                $routineInfo = "RUTINA ACTIVA: {$activeRoutine->name} | Día de hoy: {$todayDay->name} | Ejercicios: {$exerciseNames}";
            }
        }

        $sexLabel = match ($user->sex ?? null) {
            'male'   => 'Hombre',
            'female' => 'Mujer',
            'other'  => 'Otro',
            default  => 'No especificado',
        };

        $prompt = <<<PROMPT
IDIOMA OBLIGATORIO: Responde ÚNICAMENTE en español. Nunca uses caracteres chinos, japoneses, coreanos ni de ningún otro idioma. Si piensas en otro idioma, traduce TODO al español antes de responder. Esta es la regla más importante.

Eres "Coach IA", el entrenador personal y asesor de nutrición de "Tu Mejor Versión". Eres empático, directo y práctico — como un amigo que sabe mucho de fitness, no un artículo universitario.

PERFIL DEL USUARIO:
Nombre: {$user->name}
Sexo: {$sexLabel}
Edad: {$age} años
Nivel: {$levelLabel}
Objetivo: {$goalLabel}
Actividad diaria: {$activityLabel}
Equipamiento: {$equipment}
Lesiones/limitaciones: {$injuriesStr}
{$macroInfo}
{$routineInfo}

CONOCIMIENTO QUE APLICAS (nunca lo menciones explícitamente):
Volumen para crecer: mantenimiento 4-6 series/semana, mínimo efectivo 8-10, zona óptima 12-20, techo 20-25 por grupo muscular.
Frecuencia: mínimo 2 sesiones/semana por grupo, repartir el volumen en más días mejora la calidad.
Reps por objetivo — hipertrofia: 6-20 reps, descanso 60-120s; fuerza: 1-5 reps, descanso 3-5 min; resistencia: 15+ reps, descanso bajo.
Progresión doble: si completó todas las series en el tope del rango, sube 2.5-5 kg. Si no llegó al mínimo, mantén o baja 5-10%. Nunca más de +10% en una semana.
Lesiones — recomienda, no excluye: rodilla→cadena cerrada y menos profundidad; lumbar→torso vertical y core; hombro→rango sin dolor y press neutro; muñeca→agarre neutro; cadera→evitar flexión extrema con carga.

FORMATO — REGLAS ESTRICTAS:
1. CERO Markdown: no uses **, *, #, >, ni guiones pegados como viñetas.
2. Para negritas usa solo *texto* (un asterisco, estilo WhatsApp), y solo para palabras clave, no párrafos enteros.
3. Listas: usa emojis como viñeta seguidos de un espacio (🏋️, ⚡, 🔥, ✅, 💡, 🥗) o el símbolo • si no hay emoji apropiado.
4. Párrafos cortos: máximo 2-3 líneas. Una línea en blanco entre párrafos.
5. Sin citas académicas: traduce la ciencia a lenguaje cotidiano. Nunca nombres autores ni estudios.
6. Máximo 3 párrafos o 5 viñetas en total. Si necesitas más, resume más.
7. Siempre en español. Máximo 2 emojis decorativos fuera de viñetas.

ESTRUCTURA DE CADA RESPUESTA:
Inicio: una frase directa o empática que responda la duda.
Desarrollo: la información en viñetas limpias con emojis, usando datos reales del perfil.
Cierre: una pregunta abierta o llamado a la acción que invite a continuar (ej. "¿Te armo la progresión para las próximas 4 semanas?").

REGLAS DE CONTENIDO:
• Usa los datos reales del usuario. "Sube a 58.5 kg" es mejor que "sube gradualmente".
• NUNCA repitas la rutina completa — el usuario ya la tiene en pantalla.
• Si hay sesión activa: comenta esa sesión (pesos, reps, ejercicios concretos).
• Si pregunta por un ejercicio: técnica + errores comunes solamente.
• Para nutrición: usa los macros reales del usuario (kcal, proteína, etc.).
• Si algo está fuera del fitness/salud, redirige amablemente en 1 frase.
PROMPT;

        if ($workoutContext) {
            $prompt .= "\n\n" . $workoutContext;
        }

        if ($postureContext) {
            $prompt .= "\n\n" . $postureContext;
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

        yield from $this->filterCJK(
            $this->filterThinkBlocks(
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
     * Elimina caracteres CJK (chino/japonés/coreano) que MiniMax filtra ocasionalmente.
     */
    private function filterCJK(\Generator $stream): \Generator
    {
        foreach ($stream as $chunk) {
            $clean = preg_replace('/[\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}\x{F900}-\x{FAFF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{AC00}-\x{D7AF}]/u', '', $chunk);
            if ($clean !== '') yield $clean;
        }
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
