<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportExerciseGifs extends Command
{
    protected $signature   = 'exercises:import-gifs {--force : Sobreescribir GIFs ya asignados}';
    protected $description = 'Importa GIFs desde ExerciseDB API y los mapea a los ejercicios existentes';

    /**
     * Mapeo: slug del ejercicio en BD → nombre en inglés que usa ExerciseDB.
     * Se pueden agregar más entradas si se añaden ejercicios nuevos a la BD.
     */
    private array $slugToEnglish = [
        // Casa / sin equipo
        'flexion-brazos'              => 'push-up',
        'sentadilla-peso-corporal'    => 'bodyweight squat',
        'plancha-frontal'             => 'plank',
        'sentadilla-bulgara-casa'     => 'bulgarian split squat',
        'glute-bridge'                => 'glute bridge',
        'triceps-silla'               => 'bench dips',
        'mountain-climber'            => 'mountain climber',
        'burpee'                      => null, // no disponible en dataset
        'jumping-jack'                => null, // no disponible en dataset
        'zancada-casa'                => 'lunge',
        'superman'                    => 'superman',
        'crunch-abdominal'            => 'crunch',
        'plancha-lateral'             => 'side plank',
        'elevacion-gemelos-casa'      => 'standing calf raise',
        'bird-dog'                    => null, // no disponible en dataset
        'flexion-rodillas'            => 'push-up (knees on floor)',
        'sentadilla-sumo-casa'        => 'sumo deadlift',
        'hip-thrust-sin-equipo'       => 'glute bridge',
        'step-up'                     => 'step-up',
        'curl-biceps-mochila'         => 'bicep curl',
        // Principiante gym
        'sentadilla-barra'            => 'barbell squat',
        'press-banca-plano'           => 'barbell bench press',
        'peso-muerto-convencional'    => 'deadlift',
        'jalon-pecho-polea'           => 'lat pulldown',
        'remo-maquina'                => 'cable seated row',
        'press-hombros-mancuernas'    => 'dumbbell shoulder press',
        'curl-biceps-mancuernas'      => 'dumbbell bicep curl',
        'extension-triceps-polea'     => 'triceps pushdown',
        'elevacion-lateral-hombros'   => 'dumbbell lateral raise',
        'leg-press'                   => 'leg press',
        'extension-cuadriceps-maquina'=> 'leg extension',
        'curl-femoral-maquina'        => 'leg curl',
        'remo-mancuerna'              => 'dumbbell bent over row',
        'press-pecho-mancuernas'      => 'dumbbell bench press',
        'fondos-paralelas'            => 'dips',
        'abduccion-cadera-maquina'    => 'thigh abductor',
        'elevacion-gemelos-maquina'   => 'calf raise',
        'face-pull-polea'             => 'face pull',
        'crunch-maquina'              => 'cable crunch',
        // Intermedio gym
        'sentadilla-frontal'          => 'barbell front squat',
        'peso-muerto-rumano'          => 'romanian deadlift',
        'dominada-pull-up'            => 'pull-up',
        'hip-thrust-barra'            => 'barbell hip thrust',
        'press-militar-barra'         => 'barbell overhead press',
        'remo-barra'                  => 'barbell bent over row',
        'press-banca-inclinado'       => 'incline barbell bench press',
        'zancada-barra'               => 'barbell lunge',
        'curl-martillo'               => 'hammer curl',
        'fondos-asistidos'            => 'band assisted pull-up',
        'peso-muerto-sumo'            => 'sumo deadlift',
        'aperturas-mancuernas'        => 'dumbbell fly',
        'good-morning'                => 'good morning',
        'curl-predicador'             => 'preacher curl',
        'press-frances'               => 'skull crusher',
        'cable-crossover'             => 'cable fly',
        'peso-muerto-trap-bar'        => 'trap bar deadlift',
        'sentadilla-goblet'           => 'goblet squat',
        'sentadilla-bulgara-barra'    => 'bulgarian split squat',
        // Avanzado
        'deficit-deadlift'            => 'deficit deadlift',
        'dominada-lastrada'           => 'weighted pull ups',
        'clean-jerk'                  => 'clean and jerk',
        'press-banca-agarre-cerrado'  => 'close grip bench press',
        'snatch-mancuerna'            => 'one-arm kettlebell snatch',
        'sentadilla-pausa'            => null, // no disponible en dataset
        'remo-pendlay'                => null, // no disponible en dataset
        'muscle-up-barra'             => 'muscle up',
        'zercher-squat'               => 'zercher squat',
        // Movilidad — sin GIF en ExerciseDB, se omiten
        'hip-90-90'                   => null,
        'estiramiento-paloma'         => null,
        'cat-cow'                     => null,
        'movilidad-tobillo'           => null,
        'rotacion-toracica'           => null,
        'stretch-flexores-cadera'     => null,
        'dead-bug'                    => 'dead bug',
        'foam-roller-espalda'         => null,
        'worlds-greatest-stretch'     => null,
        'caminata-gluteo-medio'       => 'lateral band walk',
    ];

    /**
     * Base URL del dataset open-source de ejercicios (sin API key, sin límites).
     * Repo: https://github.com/yuhonas/free-exercise-db
     * Imágenes: https://raw.githubusercontent.com/yuhonas/free-exercise-db/main/exercises/{id}/0.jpg
     */
    private string $datasetUrl = 'https://raw.githubusercontent.com/yuhonas/free-exercise-db/main/dist/exercises.json';
    private string $imageBase  = 'https://raw.githubusercontent.com/yuhonas/free-exercise-db/main/exercises';

    public function handle(): int
    {
        $force = $this->option('force');

        $this->info('Descargando dataset open-source de ejercicios (GitHub)...');

        try {
            $response = Http::timeout(30)->get($this->datasetUrl);
            if (! $response->successful()) {
                $this->error('No se pudo descargar el dataset. Código: ' . $response->status());
                return self::FAILURE;
            }
            $allApiExercises = $response->json();
        } catch (\Exception $e) {
            $this->error('Error de conexión: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Ejercicios en dataset: ' . count($allApiExercises));

        // Indexar por nombre en minúsculas → id del ejercicio (para construir URL de imagen)
        $apiIndex = [];
        foreach ($allApiExercises as $ex) {
            $name            = strtolower(trim($ex['name'] ?? ''));
            $id              = $ex['id'] ?? null;
            $hasImage        = ! empty($ex['images']);
            $apiIndex[$name] = ($id && $hasImage)
                ? "{$this->imageBase}/{$id}/0.jpg"
                : null;
        }

        $exercises = Exercise::all();
        $updated   = 0;
        $skipped   = 0;
        $notFound  = 0;

        $this->withProgressBar($exercises, function (Exercise $exercise) use (
            $apiIndex, $force, &$updated, &$skipped, &$notFound
        ) {
            if ($exercise->gif_url && ! $force) {
                $skipped++;
                return;
            }

            $englishName = $this->slugToEnglish[$exercise->slug] ?? null;

            // null explícito = ejercicio de movilidad sin imagen esperada
            if ($englishName === null) {
                $skipped++;
                return;
            }

            $imageUrl = $apiIndex[strtolower($englishName)] ?? null;

            // Búsqueda difusa si no hay coincidencia exacta
            if (! $imageUrl) {
                $parts = explode(' ', strtolower($englishName));
                foreach ($apiIndex as $apiName => $url) {
                    $matches = array_filter($parts, fn($p) => str_contains($apiName, $p));
                    if (count($matches) >= min(2, count($parts))) {
                        $imageUrl = $url;
                        break;
                    }
                }
            }

            if ($imageUrl) {
                $exercise->update(['gif_url' => $imageUrl]);
                $updated++;
            } else {
                $notFound++;
            }
        });

        $this->newLine(2);
        $this->table(
            ['Estado', 'Cantidad'],
            [
                ['✅ Actualizados con imagen', $updated],
                ['⏭  Omitidos (ya tenían / movilidad)', $skipped],
                ['❌ No encontrados en dataset', $notFound],
            ]
        );

        if ($notFound > 0) {
            $this->warn('Ejercicios sin imagen (revisar mapeo en slugToEnglish):');
            Exercise::whereNull('gif_url')->each(function (Exercise $e) {
                $this->line("  - [{$e->slug}] {$e->name}");
            });
        }

        $total = Exercise::whereNotNull('gif_url')->count();
        $this->info("Total con imagen en BD: {$total} / " . Exercise::count());
        $this->info('Importación completada.');
        return self::SUCCESS;
    }
}
