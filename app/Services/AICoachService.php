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
        $this->geminiModel    = config('services.gemini.model', 'gemini-1.5-flash');
    }

    public function streamChat(User $user, array $history): Generator
    {
        $systemPrompt = $this->buildSystemPrompt($user);

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
            yield from $this->streamGemini($systemPrompt, $history);
            return;
        }

        yield 'Lo siento, no hay ningún proveedor de IA configurado en este momento. Por favor, añade tu API key de Anthropic o Google Gemini en el archivo .env.';
    }

    private function buildSystemPrompt(User $user): string
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

        return <<<PROMPT
Eres el Coach IA de "Tu Mejor Versión", una plataforma de entrenamiento personalizado para estudiantes universitarios. Tu rol es el de un entrenador personal experto, motivador y empático.

PERFIL DEL USUARIO:
- Nombre: {$user->name}
- Nivel de fitness: {$levelLabel}
- Objetivo principal: {$goalLabel}
- Equipamiento disponible: {$equipment}
- Lesiones o limitaciones: {$injuriesStr}

DIRECTRICES:
1. Responde SIEMPRE en español, de forma clara y directa.
2. Adapta TODOS tus consejos al nivel, objetivo y equipamiento del usuario.
3. Evita ejercicios o técnicas contraindicadas por sus lesiones.
4. Sé motivador pero realista. No prometas resultados imposibles.
5. Cuando des rutinas o planes, sé específico: series, repeticiones, descansos.
6. Si el usuario pregunta algo fuera del ámbito fitness/salud, redirige amablemente.
7. Usa emojis con moderación para dar energía al mensaje.
8. Mantén respuestas concisas pero completas (máx. 4 párrafos).

ESPECIALIDADES:
- Diseño de rutinas personalizadas
- Técnica y ejecución de ejercicios
- Nutrición básica deportiva
- Recuperación y prevención de lesiones
- Motivación y adherencia al entrenamiento
PROMPT;
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
