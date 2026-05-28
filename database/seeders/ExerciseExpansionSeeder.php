<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\ExerciseContraindication;
use Illuminate\Database\Seeder;

/**
 * Amplía el catálogo de ejercicios con grupos musculares poco cubiertos o ausentes:
 * trapecios, antebrazos, gemelos/pantorrillas e isquiotibiales.
 *
 * IDEMPOTENTE: usa updateOrCreate por `slug`, por lo que puede ejecutarse varias veces
 * sin duplicar ni alterar los IDs existentes. No toca los 77 ejercicios originales.
 *
 *   php artisan db:seed --class=ExerciseExpansionSeeder
 */
class ExerciseExpansionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->getExercises() as $data) {
            $contraindications = $data['contraindications'] ?? [];
            unset($data['contraindications']);

            $exercise = Exercise::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );

            foreach ($contraindications as $c) {
                ExerciseContraindication::firstOrCreate(
                    [
                        'exercise_id'  => $exercise->id,
                        'body_zone'    => $c['zone'],
                        'injury_phase' => $c['phase'],
                    ],
                    ['recommendation' => $c['recommendation']],
                );
            }
        }
    }

    private function getExercises(): array
    {
        return [
            // =============================
            // TRAPECIOS (6)
            // =============================
            [
                'name' => 'Encogimientos con mancuernas', 'slug' => 'encogimientos-mancuernas',
                'muscle_group' => 'trapecios', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Aislamiento del trapecio superior mediante elevación escapular con mancuernas.',
                'instructions' => [
                    'De pie, una mancuerna en cada mano a los lados del cuerpo.',
                    'Eleva los hombros hacia las orejas sin doblar los codos.',
                    'Mantén la contracción arriba 1 segundo.',
                    'Baja de forma controlada hasta el estiramiento completo.',
                ],
                'common_errors' => [
                    'Rotar los hombros: el movimiento es solo vertical.',
                    'Usar impulso del cuerpo: mantén el torso firme.',
                    'Doblar los codos: el trabajo es escapular, no de bíceps.',
                ],
                'met_value' => 4.00, 'knowledge_key' => 'shrug_db',
                'contraindications' => [],
            ],
            [
                'name' => 'Encogimientos con barra', 'slug' => 'encogimientos-barra',
                'muscle_group' => 'trapecios', 'movement_pattern' => 'pull',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Variante con barra que permite mayor carga para el trapecio superior.',
                'instructions' => [
                    'Sujeta la barra con agarre prono a la anchura de hombros.',
                    'Eleva los hombros lo más alto posible.',
                    'Pausa arriba y aprieta los trapecios.',
                    'Desciende controlando el peso.',
                ],
                'common_errors' => [
                    'Encorvar la espalda: mantén el pecho alto.',
                    'Rango incompleto: busca la máxima elevación.',
                ],
                'met_value' => 4.50, 'knowledge_key' => 'shrug_bar',
                'contraindications' => [],
            ],
            [
                'name' => 'Face pull en polea', 'slug' => 'face-pull-polea',
                'muscle_group' => 'trapecios', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'mobility'],
                'description' => 'Trabaja trapecio medio/inferior y deltoides posterior; clave para salud del hombro.',
                'instructions' => [
                    'Coloca la polea a la altura de la cara con cuerda.',
                    'Tira de la cuerda hacia la frente separando las manos.',
                    'Lleva los codos altos y abre hacia atrás.',
                    'Vuelve controlando sin perder la postura.',
                ],
                'common_errors' => [
                    'Codos caídos: mantenlos a la altura de los hombros.',
                    'Usar demasiado peso: prioriza la técnica.',
                ],
                'met_value' => 4.00, 'knowledge_key' => 'face_pull',
                'contraindications' => [],
            ],
            [
                'name' => 'Remo al mentón con mancuernas', 'slug' => 'remo-menton-mancuernas',
                'muscle_group' => 'trapecios', 'movement_pattern' => 'pull',
                'level' => 'intermediate', 'environment' => 'both',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Eleva la carga hacia el mentón trabajando trapecio y deltoides.',
                'instructions' => [
                    'De pie, mancuernas frente a los muslos, agarre prono.',
                    'Eleva los codos por encima hacia el mentón.',
                    'Mantén las mancuernas cerca del cuerpo.',
                    'Baja de forma controlada.',
                ],
                'common_errors' => [
                    'Subir demasiado alto con dolor de hombro: limita el rango.',
                    'Alejar el peso del cuerpo: mantenlo pegado.',
                ],
                'met_value' => 4.50, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No elevar por encima de los 90°. Sustituir por face pull.'],
                ],
            ],
            [
                'name' => 'Encogimientos con banda elástica', 'slug' => 'encogimientos-banda',
                'muscle_group' => 'trapecios', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'endurance'],
                'description' => 'Versión en casa con banda para el trapecio superior.',
                'instructions' => [
                    'Pisa la banda con ambos pies, sujeta los extremos.',
                    'Eleva los hombros hacia las orejas.',
                    'Aprieta arriba y baja lento.',
                    'Mantén los brazos extendidos.',
                ],
                'common_errors' => [
                    'Doblar los codos: mantén los brazos rectos.',
                    'Banda con poca tensión: ajusta el agarre.',
                ],
                'met_value' => 3.50, 'knowledge_key' => 'shrug_band',
                'contraindications' => [],
            ],
            [
                'name' => 'Elevación en Y boca abajo', 'slug' => 'elevacion-y-boca-abajo',
                'muscle_group' => 'trapecios', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['mobility', 'endurance'],
                'description' => 'Activa trapecio inferior y mejora la postura escapular.',
                'instructions' => [
                    'Boca abajo en el suelo o banco, brazos formando una Y.',
                    'Eleva los brazos manteniendo los pulgares arriba.',
                    'Aprieta entre los omóplatos.',
                    'Baja sin tocar el suelo.',
                ],
                'common_errors' => [
                    'Encoger el cuello: mantén la mirada al suelo.',
                    'Usar impulso: movimiento lento y controlado.',
                ],
                'met_value' => 3.00, 'knowledge_key' => null,
                'contraindications' => [],
            ],

            // =============================
            // ANTEBRAZOS (6)
            // =============================
            [
                'name' => 'Curl de muñeca con mancuernas', 'slug' => 'curl-muneca-mancuernas',
                'muscle_group' => 'antebrazos', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Flexión de muñeca que trabaja los flexores del antebrazo.',
                'instructions' => [
                    'Sentado, antebrazos sobre los muslos, palmas hacia arriba.',
                    'Deja caer las muñecas y luego flexiónalas hacia arriba.',
                    'Aprieta en la parte alta.',
                    'Baja lentamente hasta el estiramiento.',
                ],
                'common_errors' => [
                    'Mover el codo: solo trabaja la muñeca.',
                    'Rango corto: busca el recorrido completo.',
                ],
                'met_value' => 3.00, 'knowledge_key' => 'wrist_curl',
                'contraindications' => [
                    ['zone' => 'muneca', 'phase' => 'aguda', 'recommendation' => 'Reducir carga y rango; trabajar sin dolor.'],
                ],
            ],
            [
                'name' => 'Curl de muñeca invertido', 'slug' => 'curl-muneca-invertido',
                'muscle_group' => 'antebrazos', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Extensión de muñeca que trabaja los extensores del antebrazo.',
                'instructions' => [
                    'Sentado, antebrazos sobre los muslos, palmas hacia abajo.',
                    'Eleva el dorso de la mano flexionando la muñeca hacia arriba.',
                    'Aprieta arriba 1 segundo.',
                    'Baja de forma controlada.',
                ],
                'common_errors' => [
                    'Usar demasiado peso: los extensores son más débiles, empieza ligero.',
                    'Mover el antebrazo: aíslalo apoyándolo bien.',
                ],
                'met_value' => 3.00, 'knowledge_key' => 'reverse_wrist_curl',
                'contraindications' => [
                    ['zone' => 'muneca', 'phase' => 'aguda', 'recommendation' => 'Reducir carga y rango; trabajar sin dolor.'],
                ],
            ],
            [
                'name' => 'Curl tipo martillo', 'slug' => 'curl-martillo',
                'muscle_group' => 'antebrazos', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Agarre neutro que enfatiza braquiorradial (antebrazo) y braquial.',
                'instructions' => [
                    'De pie, mancuernas con agarre neutro (palmas enfrentadas).',
                    'Flexiona el codo subiendo la mancuerna hacia el hombro.',
                    'Mantén el agarre neutro durante todo el recorrido.',
                    'Baja controlando el peso.',
                ],
                'common_errors' => [
                    'Balancear el cuerpo: mantén los codos pegados al torso.',
                    'Rotar la muñeca: el agarre se mantiene neutro.',
                ],
                'met_value' => 3.50, 'knowledge_key' => 'hammer_curl',
                'contraindications' => [],
            ],
            [
                'name' => 'Caminata del granjero', 'slug' => 'caminata-granjero',
                'muscle_group' => 'antebrazos', 'movement_pattern' => 'carry',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['strength', 'endurance', 'fat_loss'],
                'description' => 'Transporte de carga que desarrolla agarre, antebrazos y estabilidad global.',
                'instructions' => [
                    'Sujeta una carga pesada en cada mano.',
                    'Camina con el torso erguido y core activo.',
                    'Pasos cortos y firmes.',
                    'Mantén la distancia o el tiempo objetivo.',
                ],
                'common_errors' => [
                    'Encorvar la espalda: mantén el pecho alto.',
                    'Soltar el agarre antes de tiempo: aprieta con fuerza.',
                ],
                'met_value' => 5.00, 'knowledge_key' => 'farmers_walk',
                'contraindications' => [],
            ],
            [
                'name' => 'Suspensión en barra', 'slug' => 'suspension-barra-dead-hang',
                'muscle_group' => 'antebrazos', 'movement_pattern' => 'carry',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['strength', 'mobility'],
                'description' => 'Colgarse de la barra para fortalecer el agarre y descomprimir hombros.',
                'instructions' => [
                    'Cuélgate de una barra con agarre prono.',
                    'Mantén los hombros ligeramente activos.',
                    'Aguanta el tiempo objetivo.',
                    'Baja con control.',
                ],
                'common_errors' => [
                    'Hombros totalmente relajados: mantén algo de activación.',
                    'Balancearse: mantén el cuerpo quieto.',
                ],
                'met_value' => 3.50, 'knowledge_key' => 'dead_hang',
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'Evitar suspensión pasiva total; usar apoyo parcial de pies.'],
                ],
            ],
            [
                'name' => 'Rotación de muñeca con mancuerna', 'slug' => 'rotacion-muneca-mancuerna',
                'muscle_group' => 'antebrazos', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['hypertrophy', 'mobility'],
                'description' => 'Pronosupinación con mancuerna para fortalecer el antebrazo en rotación.',
                'instructions' => [
                    'Apoya el antebrazo en el muslo con la mano fuera de la rodilla.',
                    'Sujeta una mancuerna por un extremo.',
                    'Rota lentamente la muñeca de palma arriba a palma abajo.',
                    'Controla el movimiento en ambas direcciones.',
                ],
                'common_errors' => [
                    'Mover el codo: aísla la muñeca.',
                    'Peso excesivo: empieza muy ligero.',
                ],
                'met_value' => 2.50, 'knowledge_key' => 'wrist_rotation',
                'contraindications' => [
                    ['zone' => 'muneca', 'phase' => 'aguda', 'recommendation' => 'Realizar sin carga, solo movilidad.'],
                ],
            ],

            // =============================
            // GEMELOS / PANTORRILLAS (+3)
            // =============================
            [
                'name' => 'Elevación de talones de pie con mancuernas', 'slug' => 'elevacion-talones-pie-mancuernas',
                'muscle_group' => 'gemelos', 'movement_pattern' => 'squat',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Flexión plantar de pie que trabaja el gastrocnemio.',
                'instructions' => [
                    'De pie con una mancuerna en cada mano.',
                    'Eleva los talones lo más alto posible sobre las puntas.',
                    'Aprieta los gemelos arriba 1 segundo.',
                    'Baja lento hasta el estiramiento completo.',
                ],
                'common_errors' => [
                    'Rango corto: busca máxima elevación y estiramiento.',
                    'Rebotar: controla la fase excéntrica.',
                ],
                'met_value' => 4.00, 'knowledge_key' => 'calf_raise',
                'contraindications' => [],
            ],
            [
                'name' => 'Elevación de talones sentado', 'slug' => 'elevacion-talones-sentado',
                'muscle_group' => 'gemelos', 'movement_pattern' => 'squat',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy'],
                'description' => 'Flexión plantar sentado que enfatiza el sóleo (rodilla flexionada).',
                'instructions' => [
                    'Sentado con el peso apoyado sobre las rodillas.',
                    'Apoya las puntas de los pies en la plataforma.',
                    'Eleva los talones contrayendo la pantorrilla.',
                    'Baja controlando del todo.',
                ],
                'common_errors' => [
                    'Rango incompleto: recorrido completo arriba y abajo.',
                    'Velocidad excesiva: trabaja lento.',
                ],
                'met_value' => 3.50, 'knowledge_key' => 'calf_raise_seated',
                'contraindications' => [],
            ],
            [
                'name' => 'Elevación de talón a una pierna', 'slug' => 'elevacion-talon-una-pierna',
                'muscle_group' => 'gemelos', 'movement_pattern' => 'squat',
                'level' => 'intermediate', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Versión unilateral con peso corporal para mayor sobrecarga del gemelo.',
                'instructions' => [
                    'Apóyate en una pared para equilibrarte.',
                    'Sobre una pierna, eleva el talón al máximo.',
                    'Pausa arriba y baja lento.',
                    'Completa las reps y cambia de pierna.',
                ],
                'common_errors' => [
                    'Apoyarte demasiado en la pared: úsala solo para equilibrio.',
                    'No bajar del todo: estira en cada repetición.',
                ],
                'met_value' => 4.00, 'knowledge_key' => 'single_leg_calf_raise',
                'contraindications' => [],
            ],

            // =============================
            // ISQUIOTIBIALES (+4)
            // =============================
            [
                'name' => 'Curl femoral tumbado en máquina', 'slug' => 'curl-femoral-tumbado-maquina',
                'muscle_group' => 'isquiotibiales', 'movement_pattern' => 'hinge',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Aislamiento de isquiotibiales mediante flexión de rodilla.',
                'instructions' => [
                    'Tumbado boca abajo con el rodillo sobre los talones.',
                    'Flexiona las rodillas llevando los talones a los glúteos.',
                    'Aprieta arriba 1 segundo.',
                    'Baja de forma controlada sin soltar la carga.',
                ],
                'common_errors' => [
                    'Levantar la cadera: mantén la pelvis pegada al banco.',
                    'Rango parcial: completa la flexión.',
                ],
                'met_value' => 4.00, 'knowledge_key' => 'lying_leg_curl',
                'contraindications' => [],
            ],
            [
                'name' => 'Peso muerto rumano con mancuernas', 'slug' => 'peso-muerto-rumano-mancuernas',
                'muscle_group' => 'isquiotibiales', 'movement_pattern' => 'hinge',
                'level' => 'intermediate', 'environment' => 'both',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Bisagra de cadera que estira y carga los isquiotibiales y glúteos.',
                'instructions' => [
                    'De pie con mancuernas frente a los muslos.',
                    'Empuja la cadera hacia atrás bajando el peso pegado a las piernas.',
                    'Mantén la espalda neutra y las rodillas ligeramente flexionadas.',
                    'Sube apretando glúteos al extender la cadera.',
                ],
                'common_errors' => [
                    'Redondear la espalda: mantén el pecho alto y la columna neutra.',
                    'Flexionar mucho las rodillas: el movimiento es de cadera.',
                ],
                'met_value' => 5.00, 'knowledge_key' => 'romanian_deadlift',
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'Reducir rango, mantener torso más vertical y carga ligera.'],
                ],
            ],
            [
                'name' => 'Curl femoral nórdico', 'slug' => 'curl-femoral-nordico',
                'muscle_group' => 'isquiotibiales', 'movement_pattern' => 'hinge',
                'level' => 'advanced', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Ejercicio excéntrico avanzado de isquiotibiales con peso corporal.',
                'instructions' => [
                    'Arrodíllate con los tobillos fijados (alguien o un soporte).',
                    'Baja el torso al frente lo más lento posible.',
                    'Frena con los isquiotibiales hasta donde controles.',
                    'Ayúdate con las manos para volver al inicio.',
                ],
                'common_errors' => [
                    'Doblar la cadera: mantén el cuerpo recto de rodillas a hombros.',
                    'Caer sin control: progresa el rango poco a poco.',
                ],
                'met_value' => 5.00, 'knowledge_key' => 'nordic_curl',
                'contraindications' => [
                    ['zone' => 'rodilla', 'phase' => 'aguda', 'recommendation' => 'No realizar; sustituir por curl femoral o peso muerto rumano ligero.'],
                ],
            ],
            [
                'name' => 'Buenos días con barra', 'slug' => 'buenos-dias-barra',
                'muscle_group' => 'isquiotibiales', 'movement_pattern' => 'hinge',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Bisagra de cadera con barra a la espalda para isquiotibiales y erectores.',
                'instructions' => [
                    'Barra sobre los trapecios, pies a la anchura de hombros.',
                    'Empuja la cadera atrás inclinando el torso al frente.',
                    'Mantén la espalda neutra y rodillas semiflexionadas.',
                    'Vuelve extendiendo la cadera.',
                ],
                'common_errors' => [
                    'Redondear la zona lumbar: mantén la columna neutra.',
                    'Bajar más allá del control: limita el rango.',
                ],
                'met_value' => 5.00, 'knowledge_key' => 'good_morning',
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar. Sustituir por curl femoral en máquina.'],
                ],
            ],
        ];
    }
}
