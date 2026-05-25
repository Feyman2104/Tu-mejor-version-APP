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

    public function __construct()
    {
        $this->anthropicKey   = config('services.anthropic.key', '');
        $this->anthropicModel = config('services.anthropic.model', 'claude-haiku-4-5-20251001');
        $this->geminiKey      = config('services.gemini.key', '');
        $this->geminiModel    = config('services.gemini.model', 'gemini-2.5-flash');
    }

    public function streamChat(User $user, array $history, ?string $workoutContext = null): Generator
    {
        $systemPrompt = $this->buildSystemPrompt($user, $workoutContext);

        // Intentar Claude Haiku primero; usar Gemini como alternativa si falla
        if ($this->anthropicKey) {
            try {
                yield from $this->streamClaude($systemPrompt, $history);
                return;
            } catch (\Throwable $e) {
                Log::warning('Claude Haiku falló, cambiando a Gemini', ['error' => $e->getMessage()]);
            }
        }

        if ($this->geminiKey) {
            try {
                yield from $this->streamGemini($systemPrompt, $history);
                return;
            } catch (\Throwable $e) {
                Log::error('Gemini falló', ['error' => $e->getMessage()]);
                yield 'Lo siento, hubo un problema al conectar con el asistente. Intenta de nuevo en un momento.';
                return;
            }
        }

        yield 'Lo siento, no hay ningún proveedor de IA configurado en este momento. Por favor, añade tu API key de Anthropic o Google Gemini en el archivo .env.';
    }

    private function buildSystemPrompt(User $user, ?string $workoutContext = null): string
    {
        $levelLabel = match ($user->level) {
            'beginner'     => 'principiante',
            'intermediate' => 'intermedio',
            'advanced'     => 'avanzado',
            default        => 'sin especificar',
        };

        $goalLabel = match ($user->goal) {
            'fat_loss'    => 'perder grasa',
            'muscle_gain' => 'ganar músculo',
            'strength'    => 'aumentar fuerza',
            'maintain'    => 'mantener forma física',
            'flexibility' => 'mejorar flexibilidad',
            'cardio'      => 'mejorar resistencia cardiovascular',
            default       => 'mejorar condición física general',
        };

        $equipment = implode(', ', $user->equipment ?? ['ninguno']);
        $injuries  = implode(', ', array_filter($user->injuries ?? [], fn ($i) => $i !== 'none'));
        $injuriesStr = $injuries ?: 'ninguna';

        $prompt = <<<PROMPT
Eres el Coach IA de "Tu Mejor Versión", entrenador personal con base científica NSCA/ACSM.
Tu misión: dar consejos ESPECÍFICOS y ACCIONABLES, nunca genéricos.

PERFIL DEL USUARIO:
- Nombre: {$user->name}
- Nivel: {$levelLabel}
- Objetivo: {$goalLabel}
- Equipamiento: {$equipment}
- Lesiones/limitaciones: {$injuriesStr}

BASE CIENTÍFICA — aplica siempre (NSCA Evidence-Based Guidelines):

RANGOS DE REPETICIONES:
• Fuerza máxima:        1–5 reps  · 85–100% 1RM · RIR 0–1 · descanso 3–5 min
• Fuerza-hipertrofia:   4–8 reps  · 75–85%  1RM · RIR 1–2 · descanso 2–3 min
• Hipertrofia:          6–20 reps · 60–80%  1RM · RIR 1–3 · descanso 60–120 s
• Resistencia muscular: 15+ reps  · <65%   1RM · RIR 3+  · descanso <60 s

PROGRESIÓN (doble progresión):
→ Completó TODAS las series en el tope del rango → sube 2.5–5 kg la próxima sesión
→ No llegó al piso del rango → mantén o baja 5–10%
→ Máximo +10% de carga en una semana

FALLO MUSCULAR (evidencia 2023–2024):
- Series de trabajo: RIR 1–2 (1–2 reps antes del fallo técnico)
- Fallo real: solo en la última serie del último ejercicio del grupo, máximo
- RIR > 3 en hipertrofia = demasiado conservador, suboptimal

REGLAS DE RESPUESTA — OBLIGATORIAS:
1. Máximo 3 párrafos O una lista de 5 puntos. Nunca más largo.
2. Usa los datos REALES del usuario. "Sube a 58.5 kg" es mejor que "sube gradualmente".
3. NUNCA repitas la rutina completa — el usuario ya la ve en pantalla.
4. Si hay sesión activa: comenta ESA sesión específica (pesos, reps, ejercicios concretos).
5. Si pregunta por un ejercicio → responde ese ejercicio solamente.
6. Máximo 2 emojis por respuesta.
7. Responde siempre en español. Adapta el vocabulario al nivel del usuario.
8. Si algo está fuera del fitness/salud, redirige amablemente en 1 frase.
PROMPT;

        // Inyectar el contexto de sesión activa si está disponible
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

        $body = $response->body();
        foreach (explode("\n", $body) as $line) {
            if (str_starts_with($line, 'data: ')) {
                $json = substr($line, 6);
                if ($json === '[DONE]') break;
                $data = json_decode($json, true);
                if (($data['type'] ?? '') === 'content_block_delta') {
                    $text = $data['delta']['text'] ?? '';
                    if ($text) yield $text;
                }
            }
        }
    }

    private function streamGemini(string $system, array $history): Generator
    {
        // Convertir el historial de formato Anthropic al formato de Gemini
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

        $body = $response->body();
        foreach (explode("\n", $body) as $line) {
            if (str_starts_with($line, 'data: ')) {
                $json = substr($line, 6);
                if ($json === '[DONE]') break;
                $data = json_decode($json, true);
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if ($text) yield $text;
            }
        }
    }
}
