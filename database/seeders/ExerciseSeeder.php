<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\ExerciseContraindication;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = $this->getExercises();

        foreach ($exercises as $data) {
            $contraindications = $data['contraindications'] ?? [];
            unset($data['contraindications']);

            $exercise = Exercise::create($data);

            foreach ($contraindications as $c) {
                ExerciseContraindication::create([
                    'exercise_id'  => $exercise->id,
                    'body_zone'    => $c['zone'],
                    'injury_phase' => $c['phase'],
                    'recommendation' => $c['recommendation'],
                ]);
            }
        }
    }

    private function getExercises(): array
    {
        return [

            // =============================
            // CASA — SIN EQUIPO (20 ejercicios)
            // =============================

            [
                'name' => 'Flexión de brazos', 'slug' => 'flexion-brazos',
                'muscle_group' => 'pecho', 'movement_pattern' => 'push',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'strength', 'fat_loss'],
                'description' => 'Ejercicio fundamental de empuje que trabaja pectoral, tríceps y deltoides anterior.',
                'instructions' => [
                    'Colócate en posición de plancha con las manos a la anchura de hombros.',
                    'Baja el pecho hasta casi tocar el suelo manteniendo el cuerpo recto.',
                    'Empuja el suelo para volver a la posición inicial.',
                    'Mantén el core activado durante todo el movimiento.',
                ],
                'common_errors' => [
                    'Cadera caída o elevada: mantén el cuerpo en línea recta.',
                    'Codos perpendiculares: ciérralos a 45° del torso.',
                    'Rango incompleto: el pecho debe rozar el suelo.',
                ],
                'met_value' => 8.00, 'knowledge_key' => 'pushup',
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar. Riesgo de impingement.'],
                    ['zone' => 'muneca', 'phase' => 'aguda', 'recommendation' => 'Usar puños o mancuernas con agarre neutro.'],
                ],
            ],

            [
                'name' => 'Sentadilla con peso corporal', 'slug' => 'sentadilla-peso-corporal',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['fat_loss', 'strength', 'endurance'],
                'description' => 'Patrón de sentadilla fundamental que trabaja toda la cadena posterior e inferior.',
                'instructions' => [
                    'Pies a la anchura de hombros, puntas ligeramente hacia afuera.',
                    'Baja como si fueras a sentarte, rodillas en línea con los pies.',
                    'Lleva las caderas por debajo de la línea de rodillas.',
                    'Empuja el suelo para volver arriba manteniendo el pecho erguido.',
                ],
                'common_errors' => [
                    'Rodillas hacia adentro (valgo): activa glúteos y empújalas hacia afuera.',
                    'Talones levantados: trabaja movilidad de tobillo.',
                    'No alcanzar profundidad: progresa gradualmente.',
                ],
                'met_value' => 5.00, 'knowledge_key' => 'squat',
                'contraindications' => [
                    ['zone' => 'rodilla', 'phase' => 'aguda', 'recommendation' => 'Reposo completo. No realizar.'],
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar. Riesgo de compresión discal.'],
                ],
            ],

            [
                'name' => 'Plancha frontal', 'slug' => 'plancha-frontal',
                'muscle_group' => 'core', 'movement_pattern' => 'core',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['strength', 'endurance', 'mobility'],
                'description' => 'Ejercicio isométrico fundamental para el core y estabilidad lumbar.',
                'instructions' => [
                    'Apoya codos y pies. Cuerpo recto de cabeza a talones.',
                    'Activa el core como si fuera a recibir un golpe.',
                    'Mantén la posición sin que la cadera suba ni baje.',
                    'Respira de forma controlada durante todo el tiempo.',
                ],
                'common_errors' => [
                    'Cadera elevada: bájala hasta alinear el cuerpo.',
                    'Cadera caída: activa más el core y glúteos.',
                    'Retener el aliento: respira de forma controlada.',
                ],
                'met_value' => 3.50, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar. Usa bird-dog en su lugar.'],
                ],
            ],

            [
                'name' => 'Sentadilla búlgara en casa', 'slug' => 'sentadilla-bulgara-casa',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'intermediate', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Sentadilla unilateral con pie elevado en silla o cama. Alta demanda de cuádriceps y glúteo.',
                'instructions' => [
                    'Apoya el pie trasero en una silla a la altura de la rodilla.',
                    'Baja el cuerpo de forma controlada hasta que la rodilla trasera casi toque el suelo.',
                    'Mantén el torso recto y la rodilla delantera alineada.',
                    'Empuja con el talón delantero para volver arriba.',
                ],
                'common_errors' => [
                    'Rodilla delantera que pasa la punta del pie: retrocede más el pie trasero.',
                    'Torso inclinado hacia adelante: activa el core y eleva el pecho.',
                ],
                'met_value' => 6.00, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'rodilla', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'rodilla', 'phase' => 'subaguda', 'recommendation' => 'Rango parcial sin carga.'],
                ],
            ],

            [
                'name' => 'Glute Bridge', 'slug' => 'glute-bridge',
                'muscle_group' => 'glúteos', 'movement_pattern' => 'hinge',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'mobility', 'endurance'],
                'description' => 'Puente de glúteos. Activa glúteo mayor, isquiotibiales y core.',
                'instructions' => [
                    'Tumbado boca arriba, rodillas dobladas, pies apoyados.',
                    'Eleva las caderas hasta que el cuerpo forme una línea diagonal.',
                    'Aprieta los glúteos en la parte alta durante 2 segundos.',
                    'Baja de forma controlada sin dejar caer la cadera.',
                ],
                'common_errors' => [
                    'Hiperextensión lumbar en el tope: activa más el core.',
                    'Pies demasiado lejos o cerca: ajusta hasta sentir el glúteo.',
                ],
                'met_value' => 3.00, 'knowledge_key' => 'glute_bridge',
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar. Riesgo de extensión lumbar.'],
                ],
            ],

            [
                'name' => 'Tríceps en silla', 'slug' => 'triceps-silla',
                'muscle_group' => 'tríceps', 'movement_pattern' => 'push',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Fondos de tríceps usando una silla o cama. Trabaja tríceps y deltoides anterior.',
                'instructions' => [
                    'Siéntate en el borde de la silla con las manos agarrando el asiento.',
                    'Desplaza el cuerpo hacia adelante y baja doblando los codos a 90°.',
                    'Empuja para volver arriba sin usar impulso de las piernas.',
                    'Mantén los hombros hacia atrás y el pecho elevado.',
                ],
                'common_errors' => [
                    'Hombros elevados hacia las orejas: baja los hombros activamente.',
                    'Codos abiertos hacia los lados: mantenlos paralelos al torso.',
                ],
                'met_value' => 4.00, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'muneca', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Mountain Climber', 'slug' => 'mountain-climber',
                'muscle_group' => 'core', 'movement_pattern' => 'core',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['fat_loss', 'endurance'],
                'description' => 'Ejercicio cardiovascular que trabaja core, hombros y cadera de forma dinámica.',
                'instructions' => [
                    'Posición de plancha con brazos extendidos.',
                    'Lleva una rodilla hacia el pecho de forma alternada.',
                    'Mantén las caderas niveladas durante todo el movimiento.',
                    'Aumenta la velocidad para mayor intensidad cardiovascular.',
                ],
                'common_errors' => [
                    'Caderas que suben: activa el core y mantén la línea.',
                    'Pasos demasiado cortos: lleva la rodilla bien hacia el pecho.',
                ],
                'met_value' => 8.00, 'knowledge_key' => 'mountain_climber',
            ],

            [
                'name' => 'Burpee', 'slug' => 'burpee',
                'muscle_group' => 'cuerpo completo', 'movement_pattern' => 'core',
                'level' => 'intermediate', 'environment' => 'home',
                'goal_tags' => ['fat_loss', 'endurance'],
                'description' => 'Ejercicio de cuerpo completo de alta intensidad. Excelente para quema de grasa.',
                'instructions' => [
                    'De pie, baja en cuclillas y apoya las manos en el suelo.',
                    'Lleva los pies atrás a posición de plancha.',
                    'Haz una flexión (opcional) y vuelve a cuclillas.',
                    'Salta y eleva los brazos por encima de la cabeza.',
                ],
                'common_errors' => [
                    'Cadera caída en plancha: activa el core.',
                    'Impacto excesivo en el salto: aterriza con rodillas ligeramente dobladas.',
                ],
                'met_value' => 10.00, 'knowledge_key' => 'burpee',
                'contraindications' => [
                    ['zone' => 'rodilla', 'phase' => 'aguda', 'recommendation' => 'No realizar. Alta carga articular.'],
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Jumping Jack', 'slug' => 'jumping-jack',
                'muscle_group' => 'cuerpo completo', 'movement_pattern' => 'core',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['fat_loss', 'endurance'],
                'description' => 'Ejercicio de calentamiento y cardio de bajo impacto.',
                'instructions' => [
                    'De pie con pies juntos y brazos a los lados.',
                    'Salta abriendo piernas y elevando brazos sobre la cabeza.',
                    'Regresa a la posición inicial con otro salto.',
                    'Mantén un ritmo constante y controlado.',
                ],
                'common_errors' => [
                    'Rodillas que colapsan hacia adentro al aterrizar.',
                    'Brazos que no llegan hasta arriba: completa el rango.',
                ],
                'met_value' => 7.00, 'knowledge_key' => 'jumping_jack',
            ],

            [
                'name' => 'Zancada en casa', 'slug' => 'zancada-casa',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'fat_loss'],
                'description' => 'Zancada frontal alternada para piernas y glúteos sin equipo.',
                'instructions' => [
                    'De pie, da un paso adelante largo con una pierna.',
                    'Baja la rodilla trasera casi hasta el suelo.',
                    'Empuja con el pie delantero para volver a la posición inicial.',
                    'Alterna las piernas en cada repetición.',
                ],
                'common_errors' => [
                    'Rodilla delantera que pasa la punta del pie: da un paso más largo.',
                    'Torso inclinado hacia adelante: mantén el pecho erguido.',
                ],
                'met_value' => 5.00, 'knowledge_key' => 'lunge',
                'contraindications' => [
                    ['zone' => 'rodilla', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Superman', 'slug' => 'superman',
                'muscle_group' => 'espalda', 'movement_pattern' => 'hinge',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['strength', 'mobility'],
                'description' => 'Extensión de espalda boca abajo. Trabaja erectores, glúteos e isquiotibiales.',
                'instructions' => [
                    'Tumbado boca abajo con brazos extendidos hacia adelante.',
                    'Levanta simultáneamente brazos y piernas del suelo.',
                    'Mantén la posición 2-3 segundos apretando los glúteos.',
                    'Baja de forma controlada y repite.',
                ],
                'common_errors' => [
                    'Doblar las rodillas: mantén las piernas extendidas.',
                    'Subir demasiado alto: movimiento controlado y sin hiperextensión.',
                ],
                'met_value' => 3.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Crunch abdominal', 'slug' => 'crunch-abdominal',
                'muscle_group' => 'core', 'movement_pattern' => 'core',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Ejercicio básico de flexión de tronco para el recto abdominal.',
                'instructions' => [
                    'Tumbado boca arriba, rodillas dobladas, manos detrás de la cabeza.',
                    'Eleva los hombros del suelo usando el abdomen.',
                    'No jales del cuello. El movimiento sale del core.',
                    'Baja de forma controlada sin llegar a apoyar completamente.',
                ],
                'common_errors' => [
                    'Tirar del cuello: la mirada al techo, manos sin forzar.',
                    'Rango excesivo: solo se elevan los hombros, no la zona lumbar.',
                ],
                'met_value' => 3.50, 'knowledge_key' => 'crunch',
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar. Usar dead bug en su lugar.'],
                ],
            ],

            [
                'name' => 'Plancha lateral', 'slug' => 'plancha-lateral',
                'muscle_group' => 'core', 'movement_pattern' => 'core',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['strength', 'endurance'],
                'description' => 'Variante lateral de la plancha. Trabaja oblicuos y cuadrado lumbar.',
                'instructions' => [
                    'Apoya el codo y el borde del pie. Cuerpo en línea.',
                    'Eleva las caderas hasta alinear cuerpo de cabeza a pies.',
                    'Mantén la posición sin que la cadera caiga.',
                    'Repite al otro lado.',
                ],
                'common_errors' => [
                    'Cadera que cae hacia el suelo: activa más el oblicuo.',
                    'Hombro adelantado: mantén el cuerpo en el mismo plano.',
                ],
                'met_value' => 3.50, 'knowledge_key' => null,
            ],

            [
                'name' => 'Elevación de gemelos en casa', 'slug' => 'elevacion-gemelos-casa',
                'muscle_group' => 'gemelos', 'movement_pattern' => 'squat',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'endurance'],
                'description' => 'Elevación en puntillas para gemelos usando un escalón o el borde del suelo.',
                'instructions' => [
                    'De pie con los talones al borde de un escalón.',
                    'Baja los talones por debajo del nivel del escalón.',
                    'Elévate en puntillas lo más alto posible.',
                    'Mantén 1 segundo arriba y baja de forma controlada.',
                ],
                'common_errors' => [
                    'Movimiento demasiado rápido: trabaja el rango completo lentamente.',
                    'Rodillas bloqueadas: ligera flexión para mayor activación.',
                ],
                'met_value' => 3.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Bird Dog', 'slug' => 'bird-dog',
                'muscle_group' => 'core', 'movement_pattern' => 'core',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['mobility', 'strength'],
                'description' => 'Ejercicio de estabilidad lumbar y coordinación. Ideal para rehabilitación.',
                'instructions' => [
                    'A cuatro patas, columna neutral, manos bajo hombros.',
                    'Extiende simultáneamente el brazo derecho y la pierna izquierda.',
                    'Mantén 2 segundos sin rotar la cadera.',
                    'Regresa y repite al otro lado.',
                ],
                'common_errors' => [
                    'Rotación de cadera al elevar la pierna: hazlo más despacio.',
                    'Columna no neutral: no arquees ni flexiones en exceso.',
                ],
                'met_value' => 2.50, 'knowledge_key' => null,
            ],

            [
                'name' => 'Flexión de brazos en rodillas', 'slug' => 'flexion-rodillas',
                'muscle_group' => 'pecho', 'movement_pattern' => 'push',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Versión modificada de la flexión estándar. Ideal para principiantes.',
                'instructions' => [
                    'Apoya rodillas y manos en el suelo.',
                    'Mantén el cuerpo recto desde rodillas hasta la cabeza.',
                    'Baja el pecho al suelo y empuja hacia arriba.',
                    'Progresa a flexiones completas cuando domines 15 reps.',
                ],
                'common_errors' => [
                    'Cadera hacia atrás: mantén la línea diagonal.',
                    'Rango incompleto: baja hasta tocar el suelo.',
                ],
                'met_value' => 5.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Sentadilla sumo en casa', 'slug' => 'sentadilla-sumo-casa',
                'muscle_group' => 'glúteos', 'movement_pattern' => 'squat',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'mobility'],
                'description' => 'Sentadilla con stance amplio. Mayor énfasis en aductores y glúteos.',
                'instructions' => [
                    'Pies más anchos que los hombros, puntas hacia afuera 45°.',
                    'Baja manteniendo las rodillas alineadas con los pies.',
                    'El torso más vertical que en la sentadilla convencional.',
                    'Empuja el suelo para subir activando glúteos.',
                ],
                'common_errors' => [
                    'Rodillas colapsando hacia adentro: empújalas en línea con los pies.',
                    'Inclinación excesiva del torso: más vertical.',
                ],
                'met_value' => 5.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Hip Thrust sin equipo', 'slug' => 'hip-thrust-sin-equipo',
                'muscle_group' => 'glúteos', 'movement_pattern' => 'hinge',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Empuje de cadera usando el borde de la cama o sofá.',
                'instructions' => [
                    'Apoya la parte alta de la espalda en el sofá, pies en el suelo.',
                    'Baja las caderas hasta casi tocar el suelo.',
                    'Empuja las caderas hacia arriba hasta extensión completa.',
                    'Aprieta los glúteos al máximo en el punto alto.',
                ],
                'common_errors' => [
                    'Hiperextensión lumbar: activa el core al subir.',
                    'Pies demasiado lejos: ajusta hasta que las tibias estén verticales arriba.',
                ],
                'met_value' => 4.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Saltos al cajón (step-up)', 'slug' => 'step-up',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['fat_loss', 'endurance', 'strength'],
                'description' => 'Subida a un escalón o plataforma estable. Trabaja cuádriceps, glúteos y equilibrio.',
                'instructions' => [
                    'Coloca un pie en el escalón (altura de rodilla aprox).',
                    'Empuja con ese pie para subir el cuerpo completamente.',
                    'Lleva el otro pie junto al primero arriba.',
                    'Baja de forma controlada y alterna el pie de inicio.',
                ],
                'common_errors' => [
                    'Impulso con la pierna trasera: todo el trabajo con la pierna de delante.',
                    'Escalón demasiado alto: empieza con uno bajo y progresa.',
                ],
                'met_value' => 5.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Curl de bíceps con mochila', 'slug' => 'curl-biceps-mochila',
                'muscle_group' => 'bíceps', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'home',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Curl de bíceps usando una mochila cargada como resistencia.',
                'instructions' => [
                    'De pie, mochila sostenida con ambas manos frente al cuerpo.',
                    'Dobla los codos llevando la mochila hacia los hombros.',
                    'Mantén los codos pegados al torso durante todo el movimiento.',
                    'Baja de forma controlada en 2-3 segundos.',
                ],
                'common_errors' => [
                    'Codos que se mueven hacia adelante: fijalos al costado.',
                    'Balanceo del torso para ayudar: peso apropiado sin impulso.',
                ],
                'met_value' => 3.50, 'knowledge_key' => null,
            ],

            // =============================
            // GIMNASIO — PRINCIPIANTE (20 ejercicios)
            // =============================

            [
                'name' => 'Sentadilla con barra', 'slug' => 'sentadilla-barra',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength', 'fat_loss'],
                'description' => 'La reina de los ejercicios. Trabaja cuádriceps, glúteos, isquiotibiales y core.',
                'instructions' => [
                    'Barra apoyada en la parte alta de los trapecios (high bar).',
                    'Pies a la anchura de hombros, puntas ligeramente hacia afuera.',
                    'Inhala antes de bajar, bracing de core.',
                    'Baja hasta que las caderas estén por debajo de las rodillas.',
                    'Empuja el suelo para subir manteniendo espalda neutral.',
                ],
                'common_errors' => [
                    'Rodillas hacia adentro: activa glúteos y empújalas hacia afuera.',
                    'Talones levantados: mejora movilidad de tobillo.',
                    'Sin bracing de core: practica la maniobra de Valsalva.',
                ],
                'met_value' => 6.00, 'knowledge_key' => 'squat',
                'contraindications' => [
                    ['zone' => 'rodilla', 'phase' => 'aguda', 'recommendation' => 'No realizar. Alto riesgo.'],
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'lumbar', 'phase' => 'cronica', 'recommendation' => 'Sentadilla goblet o con trap bar.'],
                ],
            ],

            [
                'name' => 'Press de banca plano', 'slug' => 'press-banca-plano',
                'muscle_group' => 'pecho', 'movement_pattern' => 'push',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Ejercicio principal de empuje horizontal. Pectoral mayor, tríceps, deltoides anterior.',
                'instructions' => [
                    'Tumbado en el banco, pies apoyados en el suelo.',
                    'Agarre ligeramente más ancho que los hombros.',
                    'Baja la barra al pecho de forma controlada.',
                    'Empuja la barra hacia arriba y ligeramente hacia atrás.',
                ],
                'common_errors' => [
                    'Rebote en el pecho: pausa de 1 segundo abajo.',
                    'Codos perpendiculares: baja a 45-75° del torso.',
                    'Pies levantados: apóyalos en el suelo para más estabilidad.',
                ],
                'met_value' => 5.00, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'hombro', 'phase' => 'subaguda', 'recommendation' => 'Press en mancuernas con rango parcial.'],
                ],
            ],

            [
                'name' => 'Peso muerto convencional', 'slug' => 'peso-muerto-convencional',
                'muscle_group' => 'espalda', 'movement_pattern' => 'hinge',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['strength', 'hypertrophy'],
                'description' => 'El ejercicio de fuerza más completo. Trabaja toda la cadena posterior.',
                'instructions' => [
                    'Pies a la anchura de cadera, barra sobre el mediopié.',
                    'Agarre justo por fuera de las piernas, espalda neutral.',
                    'Empuja el suelo (leg drive), barra pegada al cuerpo.',
                    'Lockout completo en la cadera al llegar arriba.',
                ],
                'common_errors' => [
                    'Redondeo lumbar: el error más peligroso. Para y reduce carga.',
                    'Barra separada del cuerpo: aumenta tensión en la columna.',
                    'Hiperextensión en el lockout: usa glúteos, no lumbar.',
                ],
                'met_value' => 6.00, 'knowledge_key' => 'deadlift',
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'Prohibido. Alto riesgo de hernia discal.'],
                    ['zone' => 'lumbar', 'phase' => 'subaguda', 'recommendation' => 'Peso muerto rumano con poco peso.'],
                ],
            ],

            [
                'name' => 'Jalón al pecho en polea', 'slug' => 'jalon-pecho-polea',
                'muscle_group' => 'espalda', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Jalón desde polea alta hacia el pecho. Excelente para dorsales y bíceps.',
                'instructions' => [
                    'Siéntate en la máquina, agarre pronado más ancho que los hombros.',
                    'Lleva la barra al pecho superior inclinando ligeramente el torso.',
                    'Aprieta los codos hacia las costillas en el punto bajo.',
                    'Sube de forma controlada sin dejar que los hombros suban.',
                ],
                'common_errors' => [
                    'Jalar al cuello: lleva al pecho, no a la nuca.',
                    'Cuerpo que se balancea: movimiento controlado.',
                ],
                'met_value' => 4.50, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Remo en máquina', 'slug' => 'remo-maquina',
                'muscle_group' => 'espalda', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Remo horizontal en máquina. Trabaja dorsales, romboides y bíceps.',
                'instructions' => [
                    'Siéntate con el pecho apoyado en el respaldo.',
                    'Agarra las asas y lleva los codos hacia atrás.',
                    'Aprieta la escápula en el punto de máxima contracción.',
                    'Extiende los brazos de forma controlada para volver.',
                ],
                'common_errors' => [
                    'Tirón con los brazos: inicia el movimiento con los codos.',
                    'Hombros que suben: mantenlos hacia atrás y abajo.',
                ],
                'met_value' => 4.00, 'knowledge_key' => 'row',
            ],

            [
                'name' => 'Press de hombros con mancuernas', 'slug' => 'press-hombros-mancuernas',
                'muscle_group' => 'hombros', 'movement_pattern' => 'push',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Press vertical con mancuernas. Deltoides, trapecios, tríceps.',
                'instructions' => [
                    'Sentado, mancuernas a la altura de los hombros, palmas al frente.',
                    'Empuja las mancuernas hacia arriba hasta extender los brazos.',
                    'Baja de forma controlada hasta la posición inicial.',
                    'No bloquees los codos al llegar arriba.',
                ],
                'common_errors' => [
                    'Arco lumbar excesivo al empujar: activa el core.',
                    'Cabeza hacia adelante al subir: neutro siempre.',
                ],
                'met_value' => 4.00, 'knowledge_key' => 'overhead_press',
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'hombro', 'phase' => 'subaguda', 'recommendation' => 'Press neutro con mancuernas en rango parcial.'],
                ],
            ],

            [
                'name' => 'Curl de bíceps con mancuernas', 'slug' => 'curl-biceps-mancuernas',
                'muscle_group' => 'bíceps', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy'],
                'description' => 'Curl alternado con mancuernas. Bíceps braquial y braquiorradial.',
                'instructions' => [
                    'De pie, mancuernas en cada mano con agarre supino.',
                    'Dobla un codo llevando la mancuerna al hombro.',
                    'Mantén el codo fijo al costado durante todo el movimiento.',
                    'Baja de forma controlada y alterna.',
                ],
                'common_errors' => [
                    'Balanceo del cuerpo para ayudar: reduce el peso.',
                    'Bajar demasiado rápido: tiempo bajo tensión es clave.',
                ],
                'met_value' => 3.50, 'knowledge_key' => 'bicep_curl',
            ],

            [
                'name' => 'Extensión de tríceps en polea', 'slug' => 'extension-triceps-polea',
                'muscle_group' => 'tríceps', 'movement_pattern' => 'push',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy'],
                'description' => 'Press down en polea alta para tríceps. Las tres cabezas.',
                'instructions' => [
                    'De pie frente a la polea alta, cuerpo ligeramente inclinado.',
                    'Codos fijos a los costados del cuerpo.',
                    'Extiende los brazos hacia abajo hasta bloquear los codos.',
                    'Sube de forma controlada hasta 90°.',
                ],
                'common_errors' => [
                    'Codos que se mueven hacia adelante al bajar: fíjalos.',
                    'Peso excesivo con codos elevados: reduce y fija la posición.',
                ],
                'met_value' => 3.50, 'knowledge_key' => 'tricep_extension',
            ],

            [
                'name' => 'Elevación lateral de hombros', 'slug' => 'elevacion-lateral-hombros',
                'muscle_group' => 'hombros', 'movement_pattern' => 'push',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy'],
                'description' => 'Elevación lateral con mancuernas. Deltoides lateral principalmente.',
                'instructions' => [
                    'De pie, mancuernas a los costados, codos ligeramente doblados.',
                    'Eleva los brazos hacia los lados hasta la altura del hombro.',
                    'Ligeramente inclinado hacia adelante para mayor activación.',
                    'Baja de forma controlada en 2-3 segundos.',
                ],
                'common_errors' => [
                    'Elevar más allá de los hombros: máximo hasta la horizontal.',
                    'Tirón con trapecio: mantén los hombros hacia abajo.',
                ],
                'met_value' => 3.00, 'knowledge_key' => 'lateral_raise',
            ],

            [
                'name' => 'Leg Press', 'slug' => 'leg-press',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Prensa de piernas. Cuádriceps, glúteos, isquiotibiales.',
                'instructions' => [
                    'Pies en la plataforma a la anchura de hombros.',
                    'Baja la plataforma hasta que las rodillas lleguen a 90°.',
                    'Empuja con los talones para extender las piernas.',
                    'No bloquees las rodillas completamente.',
                ],
                'common_errors' => [
                    'Pies demasiado bajos: riesgo de rodilla. Ponlos más arriba.',
                    'Rodillas que colapsan: activa el abductor.',
                ],
                'met_value' => 5.00, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'rodilla', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar. Compresión lumbar en el punto bajo.'],
                ],
            ],

            [
                'name' => 'Extensión de cuádriceps en máquina', 'slug' => 'extension-cuadriceps-maquina',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy'],
                'description' => 'Aislamiento de cuádriceps en máquina de extensión.',
                'instructions' => [
                    'Siéntate con la espinilla apoyada en el rodillo.',
                    'Extiende las rodillas hasta bloqueo completo.',
                    'Baja de forma controlada en 3 segundos.',
                    'No uses impulso en la subida.',
                ],
                'common_errors' => [
                    'Impulso al subir: movimiento lento y controlado.',
                    'Rodillo muy arriba en el tobillo: colócalo en la parte baja de la espinilla.',
                ],
                'met_value' => 3.50, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'rodilla', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'rodilla', 'phase' => 'subaguda', 'recommendation' => 'Rango parcial (90° a 60°) sin carga alta.'],
                ],
            ],

            [
                'name' => 'Curl femoral en máquina', 'slug' => 'curl-femoral-maquina',
                'muscle_group' => 'isquiotibiales', 'movement_pattern' => 'hinge',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Curl de isquiotibiales tumbado en máquina.',
                'instructions' => [
                    'Tumbado boca abajo, rodillo bajo los tobillos.',
                    'Dobla las rodillas llevando los talones hacia los glúteos.',
                    'Aprieta los isquios en el punto de máxima flexión.',
                    'Baja de forma controlada sin dejar caer el peso.',
                ],
                'common_errors' => [
                    'Caderas que se levantan al subir: activa el core y glúteos.',
                    'Movimiento demasiado rápido: tiempo bajo tensión.',
                ],
                'met_value' => 3.50, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'rodilla', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Remo con mancuerna', 'slug' => 'remo-mancuerna',
                'muscle_group' => 'espalda', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Remo unilateral apoyado en el banco. Dorsal, romboides, bíceps.',
                'instructions' => [
                    'Apoya rodilla y mano en el banco, espalda paralela al suelo.',
                    'Cuelga la mancuerna con el brazo extendido.',
                    'Lleva la mancuerna al costado del pecho con el codo alto.',
                    'Baja de forma controlada hasta extensión completa.',
                ],
                'common_errors' => [
                    'Rotación del torso para ayudar: movimiento limpio y estable.',
                    'No llegar al pecho: aumenta el rango de movimiento.',
                ],
                'met_value' => 4.50, 'knowledge_key' => 'row',
            ],

            [
                'name' => 'Press de pecho con mancuernas', 'slug' => 'press-pecho-mancuernas',
                'muscle_group' => 'pecho', 'movement_pattern' => 'push',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Press de pecho con mancuernas. Mayor rango de movimiento que la barra.',
                'instructions' => [
                    'Tumbado en banco plano, mancuernas a la altura del pecho.',
                    'Empuja hacia arriba hasta casi extender los brazos.',
                    'Baja abriendo los codos a 45-75°.',
                    'Mayor rango de movimiento que la barra — aprovéchalo.',
                ],
                'common_errors' => [
                    'Codos perpendiculares: ciérralos a 45-75°.',
                    'No bajar suficiente: deja que el pecho haga stretch.',
                ],
                'met_value' => 5.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Fondos en paralelas', 'slug' => 'fondos-paralelas',
                'muscle_group' => 'pecho', 'movement_pattern' => 'push',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Fondos en paralelas. Pectoral, tríceps, deltoides anterior.',
                'instructions' => [
                    'Agarra las paralelas y soporta tu peso en los brazos.',
                    'Inclínate ligeramente hacia adelante para mayor activación pectoral.',
                    'Baja hasta que los codos formen 90°.',
                    'Empuja para volver arriba sin bloquear los codos.',
                ],
                'common_errors' => [
                    'Sin inclinación: trabajas más tríceps que pecho.',
                    'Bajar demasiado con lesión de hombro: rango parcial.',
                ],
                'met_value' => 6.00, 'knowledge_key' => 'dips',
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'hombro', 'phase' => 'subaguda', 'recommendation' => 'Rango muy parcial o evitar.'],
                ],
            ],

            [
                'name' => 'Abducción de cadera en máquina', 'slug' => 'abduccion-cadera-maquina',
                'muscle_group' => 'glúteos', 'movement_pattern' => 'core',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'mobility'],
                'description' => 'Abducción de cadera en máquina. Glúteo medio y menor.',
                'instructions' => [
                    'Siéntate en la máquina con los muslos contra las almohadillas.',
                    'Abre las piernas hacia afuera de forma controlada.',
                    'Mantén la posición 1 segundo en el punto máximo.',
                    'Regresa de forma controlada sin soltar el peso.',
                ],
                'common_errors' => [
                    'Impulso con el torso: el movimiento es solo de cadera.',
                    'Rango muy limitado: abre lo que el cuerpo permita cómodamente.',
                ],
                'met_value' => 3.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Elevación de gemelos en máquina', 'slug' => 'elevacion-gemelos-maquina',
                'muscle_group' => 'gemelos', 'movement_pattern' => 'squat',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'endurance'],
                'description' => 'Standing calf raise en máquina. Gemelo y sóleo.',
                'instructions' => [
                    'Hombros bajo las almohadillas, pies en la plataforma.',
                    'Baja los talones por debajo del borde de la plataforma.',
                    'Elévate en puntillas lo más alto posible.',
                    'Mantén 2 segundos arriba y baja de forma controlada.',
                ],
                'common_errors' => [
                    'Rango incompleto: trabaja el estiramiento completo.',
                    'Velocidad excesiva: más lento, más activación.',
                ],
                'met_value' => 3.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Face Pull en polea', 'slug' => 'face-pull-polea',
                'muscle_group' => 'hombros', 'movement_pattern' => 'pull',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['mobility', 'strength'],
                'description' => 'Face pull con cuerda en polea alta. Salud del manguito rotador y deltoides posterior.',
                'instructions' => [
                    'Polea a la altura de los ojos, cuerda con agarre neutral.',
                    'Tira hacia la cara separando las manos al final del movimiento.',
                    'Codos hacia afuera y arriba al llegar a la cara.',
                    'Extiende los brazos de forma controlada.',
                ],
                'common_errors' => [
                    'Tirar por debajo del mentón: el pull debe ser hacia la frente.',
                    'Sin separar las manos al final: la separación activa el rotador externo.',
                ],
                'met_value' => 3.00, 'knowledge_key' => 'face_pull',
            ],

            [
                'name' => 'Plancha en máquina abdominal', 'slug' => 'crunch-maquina',
                'muscle_group' => 'core', 'movement_pattern' => 'core',
                'level' => 'beginner', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy'],
                'description' => 'Crunch en máquina con resistencia ajustable. Recto abdominal.',
                'instructions' => [
                    'Ajusta el asiento para que el pivote quede a nivel del ombligo.',
                    'Agarra las asas y dobla el torso hacia adelante.',
                    'Mantén la contracción 1 segundo en el punto bajo.',
                    'Sube de forma controlada sin soltar el peso.',
                ],
                'common_errors' => [
                    'Usar los brazos para tirar: el movimiento es del abdomen.',
                    'Rango muy corto: lleva el torso hasta la máxima flexión.',
                ],
                'met_value' => 3.00, 'knowledge_key' => null,
            ],

            // =============================
            // GIMNASIO — INTERMEDIO (20 ejercicios)
            // =============================

            [
                'name' => 'Sentadilla frontal', 'slug' => 'sentadilla-frontal',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['strength', 'hypertrophy'],
                'description' => 'Sentadilla con barra en la parte delantera del cuello. Mayor demanda de cuádriceps y core.',
                'instructions' => [
                    'Barra apoyada en los deltoides, codos altos hacia adelante.',
                    'Torso más vertical que en sentadilla trasera.',
                    'Baja manteniendo los codos arriba durante todo el recorrido.',
                    'La movilidad de muñecas y hombros es clave.',
                ],
                'common_errors' => [
                    'Codos que caen: todo el movimiento se descompone. Muñecas y mobilidad.',
                    'Torso que se inclina adelante: trabaja más movilidad de tobillo.',
                ],
                'met_value' => 7.00, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar. Posición de rack extrema.'],
                    ['zone' => 'muneca', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Peso muerto rumano', 'slug' => 'peso-muerto-rumano',
                'muscle_group' => 'isquiotibiales', 'movement_pattern' => 'hinge',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'RDL para isquiotibiales y glúteos. Menor carga en columna que el peso muerto convencional.',
                'instructions' => [
                    'De pie, barra o mancuernas frente a los muslos.',
                    'Empuja la cadera hacia atrás manteniendo las rodillas ligeramente dobladas.',
                    'Baja hasta sentir el estiramiento de los isquios.',
                    'Vuelve arriba empujando las caderas hacia adelante.',
                ],
                'common_errors' => [
                    'Redondeo lumbar al bajar: mantén espalda neutral siempre.',
                    'Bajar demasiado con falta de movilidad: solo hasta donde la espalda sea neutral.',
                ],
                'met_value' => 5.50, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'lumbar', 'phase' => 'cronica', 'recommendation' => 'Con mancuernas, carga mínima. Control estricto.'],
                ],
            ],

            [
                'name' => 'Pull-up (dominada)', 'slug' => 'dominada-pull-up',
                'muscle_group' => 'espalda', 'movement_pattern' => 'pull',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['strength', 'hypertrophy'],
                'description' => 'La dominada clásica. Uno de los mejores ejercicios de tracción. Dorsales, bíceps, trapecio.',
                'instructions' => [
                    'Agarra la barra con pronación, más ancho que los hombros.',
                    'Parte de un hang muerto con escápulas deprimidas.',
                    'Tira del cuerpo hacia arriba hasta que la barbilla pase la barra.',
                    'Baja de forma controlada hasta extensión completa.',
                ],
                'common_errors' => [
                    'Sin depresión de escápula al inicio: protege el hombro.',
                    'Piernas que oscilan para ayudar: movimiento limpio.',
                    'No extensión completa abajo: rango completo.',
                ],
                'met_value' => 8.00, 'knowledge_key' => 'pull_up',
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'hombro', 'phase' => 'subaguda', 'recommendation' => 'Jalón en polea en su lugar.'],
                ],
            ],

            [
                'name' => 'Hip Thrust con barra', 'slug' => 'hip-thrust-barra',
                'muscle_group' => 'glúteos', 'movement_pattern' => 'hinge',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'El ejercicio más efectivo para glúteo mayor. Usa un banco y barra con peso.',
                'instructions' => [
                    'Apoya la parte alta de la espalda en el banco, barra sobre las caderas.',
                    'Pies planos en el suelo a la anchura de caderas.',
                    'Empuja las caderas hacia arriba hasta extensión completa.',
                    'Aprieta los glúteos 2 segundos en el tope.',
                ],
                'common_errors' => [
                    'Hiperextensión lumbar: activa el core al subir.',
                    'Chin al pecho: mira al frente para mantener la columna neutral.',
                ],
                'met_value' => 5.50, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Press militar con barra', 'slug' => 'press-militar-barra',
                'muscle_group' => 'hombros', 'movement_pattern' => 'push',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['strength', 'hypertrophy'],
                'description' => 'Press vertical con barra de pie. Deltoides, trapecio, tríceps.',
                'instructions' => [
                    'Barra a la altura del pecho en el rack, agarre a la anchura de hombros.',
                    'Bracing de core antes de empujar.',
                    'Empuja la barra hacia arriba mientras mueves la cabeza hacia atrás.',
                    'Lleva la barra al frente de la frente al bajar.',
                ],
                'common_errors' => [
                    'Inclinarse hacia atrás para ayudar: convertirías en push press.',
                    'Sin bracing: riesgo lumbar con pesos altos.',
                ],
                'met_value' => 5.50, 'knowledge_key' => 'overhead_press',
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'lumbar', 'phase' => 'cronica', 'recommendation' => 'Versión sentado con respaldo.'],
                ],
            ],

            [
                'name' => 'Remo con barra', 'slug' => 'remo-barra',
                'muscle_group' => 'espalda', 'movement_pattern' => 'pull',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['strength', 'hypertrophy'],
                'description' => 'Remo inclinado con barra. Excelente para grosor de espalda. Dorsales, romboides, trapecios.',
                'instructions' => [
                    'Inclinado a 45° con la espalda recta, barra colgada.',
                    'Tira la barra hacia el ombligo con los codos pegados al cuerpo.',
                    'Aprieta la escápula en el punto de máxima contracción.',
                    'Baja de forma controlada hasta extensión completa.',
                ],
                'common_errors' => [
                    'Torso que sube al tirar: mantén la inclinación constante.',
                    'Redondeo lumbar: espalda neutra con bracing.',
                ],
                'met_value' => 5.50, 'knowledge_key' => 'row',
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar. Alta carga en posición inclinada.'],
                ],
            ],

            [
                'name' => 'Press de banca inclinado', 'slug' => 'press-banca-inclinado',
                'muscle_group' => 'pecho', 'movement_pattern' => 'push',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Press inclinado 30-45°. Mayor énfasis en el pectoral mayor clavicular.',
                'instructions' => [
                    'Banco a 30-45°, barra o mancuernas a la altura del pecho superior.',
                    'Empuja hacia arriba y ligeramente al frente.',
                    'Baja hasta que la barra toque el pecho superior.',
                    'Codos a 45-60° del torso durante el movimiento.',
                ],
                'common_errors' => [
                    'Banco demasiado inclinado (>45°): convierte en press de hombros.',
                    'Codos perpendiculares: riesgo de hombro.',
                ],
                'met_value' => 5.00, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Zancada con barra', 'slug' => 'zancada-barra',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Zancada con barra en los trapecios. Mayor carga que con peso corporal.',
                'instructions' => [
                    'Barra en los trapecios como en sentadilla trasera.',
                    'Paso largo hacia adelante, torso erguido.',
                    'Baja la rodilla trasera hasta casi tocar el suelo.',
                    'Empuja con el talón delantero para volver.',
                ],
                'common_errors' => [
                    'Paso demasiado corto: rodilla pasa la punta del pie.',
                    'Torso inclinado: core activado, pecho arriba.',
                ],
                'met_value' => 6.00, 'knowledge_key' => 'lunge',
            ],

            [
                'name' => 'Curl martillo con mancuernas', 'slug' => 'curl-martillo',
                'muscle_group' => 'bíceps', 'movement_pattern' => 'pull',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy'],
                'description' => 'Curl con agarre neutro. Braquiorradial y bíceps braquial.',
                'instructions' => [
                    'De pie, mancuernas con agarre neutro (palmas hacia el torso).',
                    'Dobla los codos llevando las mancuernas hacia los hombros.',
                    'Codos fijos al costado durante todo el movimiento.',
                    'Baja de forma controlada.',
                ],
                'common_errors' => [
                    'Balanceo: reduce el peso.',
                    'Supinación al subir: mantén el agarre neutro.',
                ],
                'met_value' => 3.50, 'knowledge_key' => null,
            ],

            [
                'name' => 'Fondos asistidos', 'slug' => 'fondos-asistidos',
                'muscle_group' => 'pecho', 'movement_pattern' => 'push',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Fondos en paralelas con asistencia de máquina. Progresión hacia fondos libres.',
                'instructions' => [
                    'Selecciona el contrapeso apropiado en la máquina asistida.',
                    'Agarra las paralelas e inclínate ligeramente hacia adelante.',
                    'Baja hasta 90° de flexión de codo.',
                    'Empuja hasta extensión de brazos.',
                ],
                'common_errors' => [
                    'Sin inclinación: más tríceps que pecho.',
                    'Bajar demasiado con lesión de hombro: rango parcial.',
                ],
                'met_value' => 5.00, 'knowledge_key' => 'dips',
            ],

            [
                'name' => 'Peso muerto sumo', 'slug' => 'peso-muerto-sumo',
                'muscle_group' => 'glúteos', 'movement_pattern' => 'hinge',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['strength', 'hypertrophy'],
                'description' => 'Deadlift con stance amplio. Mayor énfasis en aductores y glúteos, menor en espalda.',
                'instructions' => [
                    'Pies muy anchos, puntas hacia afuera 45°.',
                    'Agarre por dentro de las piernas a la anchura de caderas.',
                    'Columna neutral, empuja el suelo hacia afuera con los pies.',
                    'Lockout de cadera al llegar arriba.',
                ],
                'common_errors' => [
                    'Rodillas que colapsan: empújalas hacia afuera durante todo el recorrido.',
                    'Redondeo lumbar: bracing antes de cada rep.',
                ],
                'met_value' => 6.50, 'knowledge_key' => null,
            ],

            [
                'name' => 'Aperturas con mancuernas', 'slug' => 'aperturas-mancuernas',
                'muscle_group' => 'pecho', 'movement_pattern' => 'push',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy'],
                'description' => 'Fly con mancuernas en banco plano. Máximo estiramiento del pectoral.',
                'instructions' => [
                    'Tumbado en banco, mancuernas sobre el pecho con codos ligeramente doblados.',
                    'Abre los brazos hacia los lados bajando las mancuernas.',
                    'Siente el estiramiento del pectoral en el punto bajo.',
                    'Cierra los brazos como si abrazaras un árbol.',
                ],
                'common_errors' => [
                    'Codos bloqueados: mantén la ligera flexión siempre.',
                    'Demasiado peso: riesgo de lesión en el estiramiento.',
                ],
                'met_value' => 4.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Good Morning', 'slug' => 'good-morning',
                'muscle_group' => 'isquiotibiales', 'movement_pattern' => 'hinge',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['strength', 'mobility'],
                'description' => 'Inclinación hacia adelante con barra. Isquiotibiales, glúteos y erectores.',
                'instructions' => [
                    'Barra en trapecios como en sentadilla trasera.',
                    'Rodillas ligeramente dobladas, espalda neutral.',
                    'Empuja la cadera hacia atrás inclinando el torso.',
                    'Hasta que el torso esté casi paralelo al suelo.',
                ],
                'common_errors' => [
                    'Redondeo lumbar: el error más peligroso con barra.',
                    'Peso excesivo para principiantes en este ejercicio.',
                ],
                'met_value' => 4.50, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar absolutamente.'],
                    ['zone' => 'lumbar', 'phase' => 'cronica', 'recommendation' => 'No recomendado. Usar RDL en su lugar.'],
                ],
            ],

            [
                'name' => 'Curl predicador', 'slug' => 'curl-predicador',
                'muscle_group' => 'bíceps', 'movement_pattern' => 'pull',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy'],
                'description' => 'Curl en banco predicador. Aislamiento completo del bíceps braquial.',
                'instructions' => [
                    'Apoya la parte trasera del brazo en el banco predicador.',
                    'Baja la barra EZ o mancuerna de forma controlada.',
                    'Sube sin impulso hasta plena contracción.',
                    'No bloquees completamente el codo abajo.',
                ],
                'common_errors' => [
                    'Bloqueo del codo abajo: riesgo de lesión del bíceps distal.',
                    'Impulso al subir: el predicador elimina el impulso, aprovéchalo.',
                ],
                'met_value' => 3.00, 'knowledge_key' => 'bicep_curl',
            ],

            [
                'name' => 'Press francés (Skull Crusher)', 'slug' => 'press-frances',
                'muscle_group' => 'tríceps', 'movement_pattern' => 'push',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy'],
                'description' => 'Extensión de tríceps tumbado con barra EZ. Las tres cabezas del tríceps.',
                'instructions' => [
                    'Tumbado en banco, barra EZ sobre el pecho con agarre cerrado.',
                    'Baja la barra hacia la frente doblando solo los codos.',
                    'Codos fijos y apuntando al techo durante todo el movimiento.',
                    'Extiende hacia arriba.',
                ],
                'common_errors' => [
                    'Codos que se abren hacia afuera: mantenlos paralelos.',
                    'Bajada detrás de la cabeza: riesgo de lesión de codo.',
                ],
                'met_value' => 4.00, 'knowledge_key' => 'tricep_extension',
                'contraindications' => [
                    ['zone' => 'muneca', 'phase' => 'aguda', 'recommendation' => 'Usar extensión de tríceps en polea.'],
                ],
            ],

            [
                'name' => 'Cable crossover', 'slug' => 'cable-crossover',
                'muscle_group' => 'pecho', 'movement_pattern' => 'push',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy'],
                'description' => 'Cruce de poleas alto para pectoral. Excelente para definición y estrés constante.',
                'instructions' => [
                    'Poleas altas, un cable en cada mano.',
                    'Inclínate ligeramente hacia adelante con codos semiflexionados.',
                    'Cruza los brazos hacia adelante y abajo.',
                    'Mantén tensión al regresar, no abras demasiado.',
                ],
                'common_errors' => [
                    'Abrir demasiado en el estiramiento: controla el rango.',
                    'Brazos completamente extendidos: mantén la curva del codo.',
                ],
                'met_value' => 4.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Peso muerto con trap bar', 'slug' => 'peso-muerto-trap-bar',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'hinge',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['strength', 'hypertrophy'],
                'description' => 'Deadlift con barra hexagonal. Menor estrés lumbar, mayor activación de cuádriceps.',
                'instructions' => [
                    'Ponte dentro de la trap bar, agarra las asas.',
                    'Caderas más altas que en sentadilla, espalda neutral.',
                    'Empuja el suelo hacia abajo con ambos pies.',
                    'Lockout completo de rodillas y caderas al llegar arriba.',
                ],
                'common_errors' => [
                    'Redondeo lumbar: aunque sea más seguro, mantén la espalda neutral.',
                    'Bloqueo de rodillas al llegar arriba: no hiperextender.',
                ],
                'met_value' => 6.00, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'cronica', 'recommendation' => 'Opción más segura que peso muerto convencional. Consultar.'],
                ],
            ],

            [
                'name' => 'Sentadilla goblet', 'slug' => 'sentadilla-goblet',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'intermediate', 'environment' => 'gym',
                'goal_tags' => ['strength', 'mobility'],
                'description' => 'Sentadilla goblet con kettlebell o mancuerna. Excelente para técnica y core.',
                'instructions' => [
                    'Sostén la mancuerna verticalmente frente al pecho.',
                    'Pies a la anchura de caderas, puntas hacia afuera.',
                    'Baja manteniendo el torso muy vertical.',
                    'La mancuerna ayuda a mantener el contrapeso.',
                ],
                'common_errors' => [
                    'Dejar caer los codos: sostén con fuerza.',
                    'Talones levantados: trabaja movilidad de tobillo.',
                ],
                'met_value' => 5.00, 'knowledge_key' => 'squat',
            ],

            // =============================
            // GIMNASIO — AVANZADO (10 ejercicios)
            // =============================

            [
                'name' => 'Sentadilla búlgara con barra', 'slug' => 'sentadilla-bulgara-barra',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'advanced', 'environment' => 'gym',
                'goal_tags' => ['hypertrophy', 'strength'],
                'description' => 'Split squat búlgaro con barra. Máxima demanda unilateral de cuádriceps y glúteo.',
                'instructions' => [
                    'Pie trasero en banco a la altura de la rodilla, barra en trapecios.',
                    'Baja la rodilla trasera hasta casi tocar el suelo.',
                    'Rodilla delantera en línea con el pie.',
                    'Empuja con el talón delantero para volver.',
                ],
                'common_errors' => [
                    'Torso inclinado hacia adelante: activa el core, pecho arriba.',
                    'Paso demasiado corto: rodilla delantera pasa los dedos.',
                ],
                'met_value' => 7.00, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'rodilla', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Peso muerto rumano con déficit', 'slug' => 'deficit-deadlift',
                'muscle_group' => 'isquiotibiales', 'movement_pattern' => 'hinge',
                'level' => 'advanced', 'environment' => 'gym',
                'goal_tags' => ['strength'],
                'description' => 'Deadlift desde plataforma elevada para mayor rango de movimiento.',
                'instructions' => [
                    'Párate sobre plataforma de 5-10cm, barra en el suelo.',
                    'Inicio más bajo que el deadlift convencional.',
                    'Técnica idéntica al peso muerto convencional.',
                    'Mayor demanda de isquiotibiales por el rango aumentado.',
                ],
                'common_errors' => [
                    'Redondeo lumbar: el déficit aumenta la demanda de movilidad.',
                    'Plataforma demasiado alta sin movilidad suficiente.',
                ],
                'met_value' => 7.00, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'Prohibido.'],
                    ['zone' => 'lumbar', 'phase' => 'cronica', 'recommendation' => 'No recomendado.'],
                ],
            ],

            [
                'name' => 'Dominada lastrada', 'slug' => 'dominada-lastrada',
                'muscle_group' => 'espalda', 'movement_pattern' => 'pull',
                'level' => 'advanced', 'environment' => 'gym',
                'goal_tags' => ['strength'],
                'description' => 'Dominada con cinturón de lastre. Para quien ya domina las dominadas libres.',
                'instructions' => [
                    'Cinturón de lastre en la cintura, agarre pronado.',
                    'Técnica idéntica a la dominada libre.',
                    'El peso adicional incrementa la demanda de fuerza.',
                    'Rango completo: extensión abajo, barbilla sobre la barra.',
                ],
                'common_errors' => [
                    'Peso excesivo sin dominar el movimiento libre primero.',
                    'Kipping para ayudar: estricto siempre con lastre.',
                ],
                'met_value' => 9.00, 'knowledge_key' => 'pull_up',
            ],

            [
                'name' => 'Clean & Jerk', 'slug' => 'clean-jerk',
                'muscle_group' => 'cuerpo completo', 'movement_pattern' => 'hinge',
                'level' => 'advanced', 'environment' => 'gym',
                'goal_tags' => ['strength', 'endurance'],
                'description' => 'Levantamiento olímpico complejo. Potencia, coordinación y fuerza total.',
                'instructions' => [
                    'Clean: jala la barra del suelo a los hombros en un movimiento.',
                    'Atrapar en rack position con codos altos.',
                    'Jerk: empuja la barra sobre la cabeza con piernas y brazos.',
                    'Requiere coaching presencial para aprender la técnica.',
                ],
                'common_errors' => [
                    'Sin coaching previo: este ejercicio necesita supervisión.'],
                'met_value' => 10.00, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'muneca', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Press de banca con agarre cerrado', 'slug' => 'press-banca-agarre-cerrado',
                'muscle_group' => 'tríceps', 'movement_pattern' => 'push',
                'level' => 'advanced', 'environment' => 'gym',
                'goal_tags' => ['strength', 'hypertrophy'],
                'description' => 'CGBP para tríceps y pectoral interno. Alta demanda de tríceps.',
                'instructions' => [
                    'Agarre a la anchura de hombros o ligeramente más cerrado.',
                    'Baja la barra al pecho inferior con codos a 45°.',
                    'Empuja hacia arriba manteniendo los codos cerca del cuerpo.',
                    'No uses agarre demasiado cerrado (riesgo de muñecas).',
                ],
                'common_errors' => [
                    'Agarre excesivamente cerrado: riesgo de muñecas y codos.',
                    'Codos muy abiertos: no hay beneficio sobre el press normal.',
                ],
                'met_value' => 5.50, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'muneca', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Snatch con mancuerna', 'slug' => 'snatch-mancuerna',
                'muscle_group' => 'cuerpo completo', 'movement_pattern' => 'hinge',
                'level' => 'advanced', 'environment' => 'gym',
                'goal_tags' => ['strength', 'fat_loss', 'endurance'],
                'description' => 'Arrancada con mancuerna. Potencia explosiva, coordinación y cardio.',
                'instructions' => [
                    'Mancuerna en el suelo entre los pies.',
                    'Jala la mancuerna hacia arriba con potencia de cadera.',
                    'Atrápala sobre la cabeza con el brazo extendido.',
                    'Regresa de forma controlada.',
                ],
                'common_errors' => [
                    'Sin potencia de cadera: el movimiento no es de brazo.',
                    'Muñeca débil al atrapar: trabaja primero la movilidad.',
                ],
                'met_value' => 9.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Sentadilla pausa', 'slug' => 'sentadilla-pausa',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'advanced', 'environment' => 'gym',
                'goal_tags' => ['strength'],
                'description' => 'Sentadilla con pausa de 3 segundos en el punto más bajo. Elimina el rebote.',
                'instructions' => [
                    'Técnica de sentadilla con barra convencional.',
                    'En el punto más bajo, mantén 3 segundos completos.',
                    'Sin rebote, sin impulso: levanta desde posición muerta.',
                    'Reduce el peso un 20-25% respecto a tu sentadilla normal.',
                ],
                'common_errors' => [
                    'Pausa falsa de menos de 1 segundo.',
                    'Perder la tensión en el core durante la pausa.',
                ],
                'met_value' => 7.00, 'knowledge_key' => 'squat',
            ],

            [
                'name' => 'Remo Pendlay', 'slug' => 'remo-pendlay',
                'muscle_group' => 'espalda', 'movement_pattern' => 'pull',
                'level' => 'advanced', 'environment' => 'gym',
                'goal_tags' => ['strength'],
                'description' => 'Remo con barra desde el suelo. Explosivo y estricto. Espalda completa.',
                'instructions' => [
                    'Barra en el suelo, torso paralelo al suelo, espalda neutral.',
                    'Jala la barra al abdomen de forma explosiva.',
                    'Deja la barra en el suelo completamente entre cada repetición.',
                    'Cada rep parte del suelo: no hay momentum acumulado.',
                ],
                'common_errors' => [
                    'No llegar al suelo entre reps: pierde el propósito.',
                    'Torso que sube al jalar: mantén paralelo.',
                ],
                'met_value' => 6.00, 'knowledge_key' => 'row',
                'contraindications' => [
                    ['zone' => 'lumbar', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                ],
            ],

            [
                'name' => 'Muscle Up en barra', 'slug' => 'muscle-up-barra',
                'muscle_group' => 'espalda', 'movement_pattern' => 'pull',
                'level' => 'advanced', 'environment' => 'gym',
                'goal_tags' => ['strength'],
                'description' => 'Muscle up en barra. Combinación de dominada y fondo. Fuerza y coordinación.',
                'instructions' => [
                    'Domina primero dominadas y fondos por separado.',
                    'Jala explosivo pasando el pecho por encima de la barra.',
                    'Transición: gira las muñecas en el punto más alto de la dominada.',
                    'Completa el fondo hasta extensión de brazos.',
                ],
                'common_errors' => [
                    'Sin dominadas al menos x10: base insuficiente.',
                    'Kipping excesivo: trabaja la versión estricta.',
                ],
                'met_value' => 10.00, 'knowledge_key' => null,
                'contraindications' => [
                    ['zone' => 'hombro', 'phase' => 'aguda', 'recommendation' => 'No realizar.'],
                    ['zone' => 'hombro', 'phase' => 'cronica', 'recommendation' => 'No recomendado.'],
                ],
            ],

            [
                'name' => 'Zercher Squat', 'slug' => 'zercher-squat',
                'muscle_group' => 'cuádriceps', 'movement_pattern' => 'squat',
                'level' => 'advanced', 'environment' => 'gym',
                'goal_tags' => ['strength', 'hypertrophy'],
                'description' => 'Sentadilla con barra sostenida en los codos. Altísima demanda de core y bíceps.',
                'instructions' => [
                    'Barra sostenida en el pliegue de los codos a la altura del pecho.',
                    'Pies a la anchura de caderas, torso muy vertical.',
                    'Baja manteniendo los codos altos.',
                    'La barra en los brazos ayuda al contrapeso natural.',
                ],
                'common_errors' => [
                    'Dejar caer los codos: la barra se va al suelo.',
                    'Peso excesivo: empieza con barras ligeras.',
                ],
                'met_value' => 7.50, 'knowledge_key' => null,
            ],

            // =============================
            // CORRECTIVOS Y MOVILIDAD (10 ejercicios)
            // =============================

            [
                'name' => 'Hip 90/90', 'slug' => 'hip-90-90',
                'muscle_group' => 'cadera', 'movement_pattern' => 'rotation',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['mobility'],
                'description' => 'Ejercicio de movilidad de cadera en rotación interna y externa.',
                'instructions' => [
                    'Siéntate con ambas piernas en ángulo de 90° (una adelante, una atrás).',
                    'Mantén la columna erguida sin inclinar el torso.',
                    'Aprieta el glúteo de la pierna trasera para mayor estiramiento.',
                    'Mantén 30-60 segundos y cambia de lado.',
                ],
                'common_errors' => [
                    'Inclinación del torso para compensar falta de movilidad.',
                    'Dolor en la rodilla: reduce el ángulo hasta que sea cómodo.',
                ],
                'met_value' => 2.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Estiramiento de cadera con paloma', 'slug' => 'estiramiento-paloma',
                'muscle_group' => 'glúteos', 'movement_pattern' => 'rotation',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['mobility'],
                'description' => 'Pigeon pose para movilidad de cadera y apertura de glúteo piriforme.',
                'instructions' => [
                    'Desde cuatro patas, lleva una rodilla hacia adelante y al costado.',
                    'La pierna trasera extendida hacia atrás.',
                    'Baja el torso hacia el suelo para mayor intensidad.',
                    'Mantén 30-90 segundos.',
                ],
                'common_errors' => [
                    'Cadera elevada de un lado: mantén simétrica.',
                    'Dolor en la rodilla: usa la versión en espalda (figure-4).',
                ],
                'met_value' => 2.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Cat-Cow', 'slug' => 'cat-cow',
                'muscle_group' => 'core', 'movement_pattern' => 'rotation',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['mobility'],
                'description' => 'Movilidad de columna vertebral en flexión y extensión. Ideal para el calentamiento.',
                'instructions' => [
                    'A cuatro patas, columna neutral.',
                    'Inhala arqueando la espalda hacia abajo (vaca).',
                    'Exhala redondeando la espalda hacia arriba (gato).',
                    'Movimiento fluido y sincronizado con la respiración.',
                ],
                'common_errors' => [
                    'Rango limitado: maximiza el arco en ambas direcciones.',
                    'Sin respiración: sincroniza inhala/exhala con el movimiento.',
                ],
                'met_value' => 2.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Movilidad de tobillo', 'slug' => 'movilidad-tobillo',
                'muscle_group' => 'gemelos', 'movement_pattern' => 'core',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['mobility'],
                'description' => 'Ejercicio de movilidad de dorsiflexión de tobillo. Clave para la sentadilla.',
                'instructions' => [
                    'De rodillas, apoya el pie adelante con la rodilla sobre los dedos.',
                    'Lleva la rodilla hacia adelante más allá de los dedos del pie.',
                    'Mantén el talón en el suelo durante todo el movimiento.',
                    '10-15 reps por lado, 3 series.',
                ],
                'common_errors' => [
                    'Talón que se levanta: reduce el rango hasta mantenerlo abajo.',
                    'Sin llevar la rodilla suficientemente adelante: el progreso requiere superar el rango actual.',
                ],
                'met_value' => 2.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Rotación torácica', 'slug' => 'rotacion-toracica',
                'muscle_group' => 'espalda', 'movement_pattern' => 'rotation',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['mobility'],
                'description' => 'Movilidad de rotación torácica. Esencial para press militar y sentadilla frontal.',
                'instructions' => [
                    'En posición de 90/90 o sentado, mano detrás de la cabeza.',
                    'Rota el codo hacia arriba llevando el torso en rotación.',
                    'Mantén la cadera quieta, el movimiento es solo del torso.',
                    '10 reps por lado.',
                ],
                'common_errors' => [
                    'La cadera rota también: fija la cadera y mueve solo el torso.',
                    'Rango muy limitado: es normal al inicio, progresa con constancia.',
                ],
                'met_value' => 2.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Stretch de flexores de cadera', 'slug' => 'stretch-flexores-cadera',
                'muscle_group' => 'cadera', 'movement_pattern' => 'rotation',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['mobility'],
                'description' => 'Estiramiento del psoas e ilíaco. Fundamental para quienes pasan muchas horas sentados.',
                'instructions' => [
                    'En zancada, rodilla trasera en el suelo.',
                    'Empuja la cadera hacia adelante y abajo.',
                    'Eleva el brazo del mismo lado para mayor estiramiento.',
                    'Mantén 45-60 segundos por lado.',
                ],
                'common_errors' => [
                    'Arco lumbar excesivo: activa el glúteo trasero para controlarlo.',
                    'Tiempo insuficiente: el mínimo efectivo es 30 segundos.',
                ],
                'met_value' => 2.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Dead Bug', 'slug' => 'dead-bug',
                'muscle_group' => 'core', 'movement_pattern' => 'core',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['mobility', 'strength'],
                'description' => 'Activación de core profundo sin carga lumbar. Ideal para rehabilitación.',
                'instructions' => [
                    'Tumbado boca arriba, brazos al techo, rodillas a 90°.',
                    'Extiende simultáneamente el brazo derecho y la pierna izquierda.',
                    'Mantén la zona lumbar pegada al suelo siempre.',
                    'Regresa y repite al otro lado.',
                ],
                'common_errors' => [
                    'Zona lumbar que se separa del suelo: el error principal. Reduce el rango.',
                    'Retener el aliento: exhala al extender.',
                ],
                'met_value' => 2.50, 'knowledge_key' => null,
            ],

            [
                'name' => 'Foam roller de espalda', 'slug' => 'foam-roller-espalda',
                'muscle_group' => 'espalda', 'movement_pattern' => 'rotation',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['mobility'],
                'description' => 'Automasaje con foam roller en la columna torácica para mejorar extensión.',
                'instructions' => [
                    'Foam roller perpendicular a la columna, a la altura de los omóplatos.',
                    'Brazos cruzados sobre el pecho o manos detrás de la cabeza.',
                    'Rueda lentamente hacia arriba y abajo de la columna torácica.',
                    'Pausa en los puntos más tensos 20-30 segundos.',
                ],
                'common_errors' => [
                    'Rodar sobre la zona lumbar: evita rodar más abajo de las costillas.',
                    'Presión excesiva al inicio: comienza suave.',
                ],
                'met_value' => 2.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'World\'s Greatest Stretch', 'slug' => 'worlds-greatest-stretch',
                'muscle_group' => 'cadera', 'movement_pattern' => 'rotation',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['mobility'],
                'description' => 'Estiramiento dinámico completo para calentamiento. Trabaja cadera, columna y hombros.',
                'instructions' => [
                    'En zancada con la mano del mismo lado apoyada en el suelo.',
                    'Lleva el codo del mismo lado hacia el pie de la zancada.',
                    'Rota abriendo el torso y el brazo hacia el techo.',
                    '5 reps lentas por lado como calentamiento.',
                ],
                'common_errors' => [
                    'Velocidad excesiva: ejercicio dinámico pero controlado.',
                    'Sin rotación completa: lleva el brazo hasta apuntar al techo.',
                ],
                'met_value' => 3.00, 'knowledge_key' => null,
            ],

            [
                'name' => 'Caminata de glúteo medio', 'slug' => 'caminata-gluteo-medio',
                'muscle_group' => 'glúteos', 'movement_pattern' => 'carry',
                'level' => 'beginner', 'environment' => 'both',
                'goal_tags' => ['mobility', 'strength'],
                'description' => 'Activación de glúteo medio con banda. Previene el valgo de rodilla en sentadilla.',
                'instructions' => [
                    'Banda de resistencia alrededor de las rodillas o tobillos.',
                    'Ligeramente en cuclillas, caminata lateral manteniendo la tensión.',
                    'Rodillas separadas contra la banda durante todo el movimiento.',
                    '15-20 pasos por lado, 3 series.',
                ],
                'common_errors' => [
                    'Rodillas que colapsan hacia adentro: el punto es mantenerlas afuera.',
                    'Pasos muy pequeños: da pasos medianos para mayor activación.',
                ],
                'met_value' => 3.00, 'knowledge_key' => null,
            ],

        ];
    }
}
