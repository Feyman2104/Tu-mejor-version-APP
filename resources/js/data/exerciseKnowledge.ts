// Base de conocimiento científica — NSCA / ACSM / Fisioterapia deportiva
// Consumida por: AICoachService.php (chatbot) y useAngleCalculator.ts (postura)

export interface ExerciseThreshold {
  joint: string
  angle_min?: number
  angle_max?: number
  condition: 'throughout' | 'bottom_position' | 'top_position'
  feedback_bad: string
  feedback_good: string
  severity: 'warning' | 'error'
}

export interface ContraIndication {
  zone: 'lumbar' | 'rodilla' | 'hombro' | 'muneca' | 'cadera'
  phase: 'aguda' | 'subaguda' | 'cronica' | 'retorno'
  recommendation: string
}

export interface ExerciseKnowledge {
  id: string
  name_es: string
  muscle_primary: string[]
  muscle_secondary: string[]
  execution_cues: string[]
  common_errors: string[]
  contraindications: ContraIndication[]
  posture_thresholds: ExerciseThreshold[]
  // Orientación corporal recomendada frente a la cámara para que los errores clave
  // del ejercicio sean medibles: 'front' (de frente) o 'side' (de perfil).
  // Si se omite, el analizador asume 'side'.
  recommended_view?: 'front' | 'side'
  variants: {
    easier: string
    harder: string
    home_version: string
  }
}

export const EXERCISE_KNOWLEDGE: Record<string, ExerciseKnowledge> = {

  squat: {
    id: 'squat',
    name_es: 'Sentadilla',
    muscle_primary: ['cuádriceps', 'glúteo mayor'],
    muscle_secondary: ['isquiotibiales', 'gemelos', 'core'],
    execution_cues: [
      'Pies a la anchura de hombros, puntas ligeramente hacia afuera (15–30°)',
      'Inhala antes de bajar, exhala al subir (maniobra de Valsalva controlada)',
      'Rodillas en línea con los pies durante todo el movimiento',
      'Caderas por debajo de la línea de rodillas en el punto más bajo',
      'Talones en contacto con el suelo en todo momento',
      'Pecho arriba, mirada al frente, columna neutral',
    ],
    common_errors: [
      'Cave-in de rodillas (valgo): activa glúteos y empuja rodillas hacia afuera',
      'Talones levantados: trabajo de movilidad de tobillo con estiramientos de gemelo',
      'Inclinación excesiva del torso: fortalece core y mejora movilidad de cadera',
      'No alcanzar profundidad paralela: trabajo progresivo de movilidad de cadera y tobillo',
    ],
    contraindications: [
      { zone: 'rodilla', phase: 'aguda', recommendation: 'No realizar. Reposo y hielo. Derivar a fisio.' },
      { zone: 'rodilla', phase: 'subaguda', recommendation: 'Sentadilla parcial (0–45°) sin carga. Priorizar control neuromuscular.' },
      { zone: 'rodilla', phase: 'cronica', recommendation: 'Sentadilla goblet con apoyo. Evitar valgo. Vigilar dolor.' },
      { zone: 'lumbar', phase: 'aguda', recommendation: 'No realizar. Riesgo de compresión discal.' },
      { zone: 'lumbar', phase: 'cronica', recommendation: 'Sentadilla goblet o con caja. Core activado. Sin carga axial alta.' },
    ],
    recommended_view: 'front',
    posture_thresholds: [
      {
        joint: 'knee_valgus',
        angle_max: 10,
        condition: 'throughout',
        feedback_bad: 'Rodillas hacia adentro — empújalas en línea con los pies',
        feedback_good: 'Rodillas bien alineadas',
        severity: 'error',
      },
      {
        joint: 'stance_width',
        angle_min: 0.75,
        angle_max: 1.7,
        condition: 'throughout',
        feedback_bad: 'Ajusta los pies a la anchura de los hombros',
        feedback_good: 'Ancho de pies correcto',
        severity: 'warning',
      },
      {
        joint: 'squat_depth',
        angle_max: 25,
        condition: 'bottom_position',
        feedback_bad: 'Baja más — muslos paralelos al suelo',
        feedback_good: 'Profundidad correcta',
        severity: 'warning',
      },
    ],
    variants: {
      easier: 'Sentadilla con caja (box squat) o goblet squat con mancuerna',
      harder: 'Sentadilla búlgara o sentadilla frontal con barra',
      home_version: 'Sentadilla con peso corporal o con mochila cargada',
    },
  },

  pushup: {
    id: 'pushup',
    name_es: 'Flexión de brazos',
    muscle_primary: ['pectoral mayor', 'tríceps'],
    muscle_secondary: ['deltoides anterior', 'core', 'serrato anterior'],
    execution_cues: [
      'Manos a la anchura de hombros o ligeramente más abiertas',
      'Cuerpo recto de cabeza a talones — core activado durante todo el movimiento',
      'Codos a 45° del torso, no perpendiculares',
      'Pecho toca el suelo en el punto más bajo',
      'Exhala al empujar, inhala al bajar',
    ],
    common_errors: [
      'Cadera caída: contrae el core como si fuera una plancha',
      'Codos perpendiculares al torso: aumenta riesgo de hombro, ciérralos a 45°',
      'Rango incompleto: el pecho debe rozar el suelo en cada repetición',
    ],
    contraindications: [
      { zone: 'hombro', phase: 'aguda', recommendation: 'No realizar. Riesgo de impingement.' },
      { zone: 'hombro', phase: 'subaguda', recommendation: 'Flexión de pared o en rodillas con rango parcial.' },
      { zone: 'muneca', phase: 'aguda', recommendation: 'Sustituir por press en mancuernas con agarre neutro.' },
      { zone: 'lumbar', phase: 'cronica', recommendation: 'Versión en rodillas. Evitar hiperextensión lumbar.' },
    ],
    recommended_view: 'side',
    posture_thresholds: [
      {
        joint: 'elbow',
        angle_max: 100,
        condition: 'bottom_position',
        feedback_bad: 'Baja más — el pecho debe casi tocar el suelo',
        feedback_good: 'Rango completo correcto',
        severity: 'warning',
      },
      {
        joint: 'hip_alignment',
        angle_max: 12,
        condition: 'throughout',
        feedback_bad: 'Activa el core — la cadera no debe caer ni subir',
        feedback_good: 'Cuerpo recto, alineado correctamente',
        severity: 'error',
      },
    ],
    variants: {
      easier: 'Flexión en rodillas o flexión de pared',
      harder: 'Flexión con palmada (pliométrica) o con pies elevados',
      home_version: 'Mismo ejercicio — no requiere equipo',
    },
  },

  deadlift: {
    id: 'deadlift',
    name_es: 'Peso muerto',
    muscle_primary: ['isquiotibiales', 'glúteo mayor', 'erectores espinales'],
    muscle_secondary: ['trapecio', 'dorsales', 'core', 'cuádriceps'],
    execution_cues: [
      'Pies a la anchura de cadera, barra sobre el mediopié',
      'Agarre justo por fuera de las piernas, espalda neutral antes de iniciar',
      'Empuja el suelo hacia abajo (leg drive) — no "jales" la barra hacia arriba',
      'Barra pegada al cuerpo durante todo el recorrido',
      'Lockout completo en la cadera al llegar arriba — no hiperextender lumbar',
      'Inhala y bracing de core antes de cada repetición',
    ],
    common_errors: [
      'Redondeo lumbar: el error más peligroso. Para, reduce carga, trabaja movilidad',
      'Barra separada del cuerpo: aumenta el brazo de palanca sobre la columna',
      'Hiperextensión en el lockout: usa glúteos, no lumbar, para terminar el mov.',
    ],
    contraindications: [
      { zone: 'lumbar', phase: 'aguda', recommendation: 'Prohibido. Alto riesgo de hernia discal.' },
      { zone: 'lumbar', phase: 'subaguda', recommendation: 'Peso muerto rumano con poco peso. Control estricto de columna neutral.' },
      { zone: 'lumbar', phase: 'cronica', recommendation: 'Peso muerto con trap bar o rumano con banda. Evitar carga axial alta.' },
    ],
    posture_thresholds: [
      {
        joint: 'back_neutral',
        angle_max: 15,
        condition: 'throughout',
        feedback_bad: 'Espalda redondeada — para, reduce el peso y mantén columna neutral',
        feedback_good: 'Columna neutral correcta',
        severity: 'error',
      },
      {
        joint: 'hip_lockout',
        angle_min: 170,
        condition: 'top_position',
        feedback_bad: 'Completa la extensión de cadera al llegar arriba',
        feedback_good: 'Lockout completo',
        severity: 'warning',
      },
    ],
    variants: {
      easier: 'Peso muerto rumano con mancuernas o con banda de resistencia',
      harder: 'Peso muerto sumo con barra o deficit deadlift',
      home_version: 'Peso muerto rumano con mochila cargada o garrafas de agua',
    },
  },

  lunge: {
    id: 'lunge',
    name_es: 'Zancada',
    muscle_primary: ['cuádriceps', 'glúteo mayor'],
    muscle_secondary: ['isquiotibiales', 'gemelos', 'core'],
    execution_cues: [
      'Paso largo hacia adelante manteniendo el torso erguido',
      'Rodilla delantera alineada con el pie, no más adelante',
      'Rodilla trasera casi toca el suelo en el punto bajo',
      'Empuja con el talón delantero para volver',
      'Core activado para estabilidad lateral',
    ],
    common_errors: [
      'Paso muy corto: rodilla delantera pasa la punta del pie',
      'Torso inclinado: mantén el pecho arriba y el core activo',
      'Rodilla que colapsa hacia adentro: activa el glúteo',
    ],
    contraindications: [
      { zone: 'rodilla', phase: 'aguda', recommendation: 'No realizar.' },
      { zone: 'rodilla', phase: 'subaguda', recommendation: 'Zancada estática con rango muy parcial.' },
    ],
    recommended_view: 'side',
    posture_thresholds: [
      {
        joint: 'front_knee',
        angle_max: 105,
        condition: 'bottom_position',
        feedback_bad: 'Baja más — la rodilla delantera debe llegar a 90°',
        feedback_good: 'Profundidad de zancada correcta',
        severity: 'warning',
      },
      {
        joint: 'back',
        angle_max: 25,
        condition: 'throughout',
        feedback_bad: 'Mantén el torso erguido — pecho arriba',
        feedback_good: 'Torso erguido correcto',
        severity: 'warning',
      },
    ],
    variants: {
      easier: 'Zancada estática (sin paso)',
      harder: 'Zancada búlgara con barra',
      home_version: 'Zancada con peso corporal o mochila',
    },
  },

  glute_bridge: {
    id: 'glute_bridge',
    name_es: 'Puente de glúteos',
    muscle_primary: ['glúteo mayor'],
    muscle_secondary: ['isquiotibiales', 'core', 'erectores'],
    execution_cues: [
      'Tumbado boca arriba, rodillas dobladas, pies planos',
      'Eleva las caderas apretando los glúteos',
      'Columna neutral en el punto alto — no hiperextender',
      'Mantén 2 segundos arriba y baja de forma controlada',
    ],
    common_errors: [
      'Hiperextensión lumbar arriba: activa el core',
      'Pies demasiado lejos: ajusta hasta que las tibias estén verticales',
    ],
    contraindications: [
      { zone: 'lumbar', phase: 'aguda', recommendation: 'No realizar.' },
    ],
    recommended_view: 'side',
    posture_thresholds: [
      {
        joint: 'hip_extension',
        angle_min: 160,
        condition: 'top_position',
        feedback_bad: 'Sube más — extiende la cadera completamente',
        feedback_good: 'Extensión de cadera completa',
        severity: 'warning',
      },
    ],
    variants: {
      easier: 'Glute bridge con pies más cercanos',
      harder: 'Hip thrust con barra o unilateral',
      home_version: 'Mismo ejercicio en el suelo',
    },
  },

  pull_up: {
    id: 'pull_up',
    name_es: 'Dominada',
    muscle_primary: ['dorsal ancho', 'bíceps braquial'],
    muscle_secondary: ['trapecio', 'romboides', 'core'],
    execution_cues: [
      'Agarre pronado más ancho que los hombros',
      'Escápulas deprimidas antes de tirar',
      'Lleva el pecho a la barra, no el mentón',
      'Baja de forma controlada hasta extensión completa',
      'Core activado para evitar balanceo',
    ],
    common_errors: [
      'Sin depresión escapular: protege el manguito rotador',
      'Kipping para compensar: trabaja primero la fuerza estricta',
      'Sin extensión completa abajo: acorta el rango y los beneficios',
    ],
    contraindications: [
      { zone: 'hombro', phase: 'aguda', recommendation: 'No realizar.' },
      { zone: 'hombro', phase: 'subaguda', recommendation: 'Jalón en polea con carga progresiva.' },
    ],
    posture_thresholds: [
      {
        joint: 'elbow_extension',
        angle_min: 160,
        condition: 'bottom_position',
        feedback_bad: 'Extiende los brazos completamente abajo',
        feedback_good: 'Extensión completa — buen rango',
        severity: 'warning',
      },
    ],
    variants: {
      easier: 'Jalón en polea o dominada asistida',
      harder: 'Dominada lastrada',
      home_version: 'Jalón con toalla en puerta',
    },
  },

  row: {
    id: 'row',
    name_es: 'Remo',
    muscle_primary: ['dorsal ancho', 'romboides'],
    muscle_secondary: ['bíceps', 'trapecio medio', 'deltoides posterior'],
    execution_cues: [
      'Torso estable durante todo el movimiento',
      'Inicia con los codos, no con los bíceps',
      'Aprieta la escápula en el punto de máxima contracción',
      'Baja de forma controlada sin dejar caer el peso',
    ],
    common_errors: [
      'Tirón con los brazos: inicia el movimiento desde los codos y la escápula',
      'Torso que se balancea: reduce el peso',
    ],
    contraindications: [
      { zone: 'lumbar', phase: 'aguda', recommendation: 'No realizar remos inclinados.' },
    ],
    posture_thresholds: [
      {
        joint: 'elbow_row',
        angle_max: 45,
        condition: 'top_position',
        feedback_bad: 'Tira más — lleva el codo bien hacia atrás',
        feedback_good: 'Rango completo de remo correcto',
        severity: 'warning',
      },
    ],
    variants: {
      easier: 'Remo en máquina',
      harder: 'Remo Pendlay desde el suelo',
      home_version: 'Remo con toalla en puerta o mochila cargada',
    },
  },

  overhead_press: {
    id: 'overhead_press',
    name_es: 'Press de hombros',
    muscle_primary: ['deltoides anterior', 'deltoides lateral'],
    muscle_secondary: ['trapecio', 'tríceps', 'core'],
    execution_cues: [
      'Core activado antes de empujar',
      'Barra o mancuernas a la altura de los hombros',
      'Empuja en línea recta hacia arriba',
      'No inclinarte hacia atrás para compensar',
    ],
    common_errors: [
      'Arco lumbar excesivo: activa el core y glúteos',
      'Cabeza hacia adelante: neutro durante todo el movimiento',
    ],
    contraindications: [
      { zone: 'hombro', phase: 'aguda', recommendation: 'No realizar.' },
      { zone: 'hombro', phase: 'subaguda', recommendation: 'Press neutro con mancuernas en rango parcial.' },
      { zone: 'lumbar', phase: 'cronica', recommendation: 'Versión sentado con respaldo.' },
    ],
    recommended_view: 'side',
    posture_thresholds: [
      {
        joint: 'elbow_press',
        angle_min: 160,
        condition: 'top_position',
        feedback_bad: 'Extiende los brazos completamente arriba',
        feedback_good: 'Extensión completa en el press',
        severity: 'warning',
      },
      {
        joint: 'back',
        angle_max: 20,
        condition: 'throughout',
        feedback_bad: 'No te inclines hacia atrás — activa el core y los glúteos',
        feedback_good: 'Torso estable correcto',
        severity: 'warning',
      },
    ],
    variants: {
      easier: 'Press con mancuernas sentado con respaldo',
      harder: 'Press militar con barra de pie',
      home_version: 'Press con mochila o garrafas de agua',
    },
  },

  dips: {
    id: 'dips',
    name_es: 'Fondos en paralelas',
    muscle_primary: ['pectoral mayor', 'tríceps'],
    muscle_secondary: ['deltoides anterior', 'core'],
    execution_cues: [
      'Inclínate ligeramente hacia adelante para mayor activación pectoral',
      'Baja hasta que los codos formen 90°',
      'Mantén los hombros hacia atrás y abajo',
      'Empuja hasta extensión sin bloquear los codos',
    ],
    common_errors: [
      'Sin inclinación: trabajas más tríceps que pecho',
      'Bajada excesiva con lesión de hombro: rango parcial',
    ],
    contraindications: [
      { zone: 'hombro', phase: 'aguda', recommendation: 'No realizar.' },
      { zone: 'hombro', phase: 'subaguda', recommendation: 'Rango muy parcial o evitar.' },
    ],
    posture_thresholds: [
      {
        joint: 'elbow_dip',
        angle_min: 85,
        angle_max: 100,
        condition: 'bottom_position',
        feedback_bad: 'Baja más — llega a 90° en los codos',
        feedback_good: 'Profundidad de fondo correcta',
        severity: 'warning',
      },
    ],
    variants: {
      easier: 'Fondos en silla o asistidos en máquina',
      harder: 'Fondos lastrados con cinturón',
      home_version: 'Fondos entre dos sillas',
    },
  },

  lateral_raise: {
    id: 'lateral_raise',
    name_es: 'Elevación lateral',
    muscle_primary: ['deltoides lateral'],
    muscle_secondary: ['trapecio superior', 'supraespinoso'],
    execution_cues: [
      'Codos ligeramente doblados durante todo el movimiento',
      'Eleva hasta la horizontal — no más arriba',
      'Ligera inclinación hacia adelante para mayor activación',
      'Baja de forma controlada en 2-3 segundos',
    ],
    common_errors: [
      'Elevar por encima del hombro: activa el trapecio innecesariamente',
      'Tirón con el trapecio: mantén los hombros hacia abajo',
    ],
    contraindications: [
      { zone: 'hombro', phase: 'aguda', recommendation: 'No realizar.' },
    ],
    posture_thresholds: [],
    variants: {
      easier: 'Elevación lateral en cable para tensión constante',
      harder: 'Elevación lateral con pausa isométrica en el tope',
      home_version: 'Con botellas de agua o bandas de resistencia',
    },
  },

  bicep_curl: {
    id: 'bicep_curl',
    name_es: 'Curl de bíceps',
    muscle_primary: ['bíceps braquial'],
    muscle_secondary: ['braquiorradial', 'braquial'],
    execution_cues: [
      'Codos fijos al costado durante todo el movimiento',
      'Supinación de la muñeca al subir',
      'Baja de forma controlada en 2-3 segundos',
      'No uses impulso del torso',
    ],
    common_errors: [
      'Codos que se mueven hacia adelante: fíjalos al costado',
      'Balanceo del torso para ayudar: reduce el peso',
    ],
    contraindications: [
      { zone: 'muneca', phase: 'aguda', recommendation: 'Agarre neutro (curl martillo).' },
    ],
    recommended_view: 'side',
    posture_thresholds: [
      {
        joint: 'elbow',
        angle_max: 70,
        condition: 'top_position',
        feedback_bad: 'Sube más — contrae el bíceps por completo',
        feedback_good: 'Contracción completa',
        severity: 'warning',
      },
      {
        joint: 'elbow',
        angle_min: 150,
        condition: 'bottom_position',
        feedback_bad: 'Extiende del todo abajo — rango completo',
        feedback_good: 'Extensión completa',
        severity: 'warning',
      },
    ],
    variants: {
      easier: 'Curl con banda de resistencia',
      harder: 'Curl predicador para mayor aislamiento',
      home_version: 'Curl con mochila cargada o botellas',
    },
  },

  tricep_extension: {
    id: 'tricep_extension',
    name_es: 'Extensión de tríceps',
    muscle_primary: ['tríceps braquial (3 cabezas)'],
    muscle_secondary: ['ancóneo'],
    execution_cues: [
      'Codos fijos y apuntando al frente durante todo el movimiento',
      'Extiende completamente los codos en el punto final',
      'Baja de forma controlada — tiempo bajo tensión',
    ],
    common_errors: [
      'Codos que se abren: fíjalos paralelos',
      'Bajada detrás de la cabeza en skull crusher: riesgo de codo',
    ],
    contraindications: [
      { zone: 'muneca', phase: 'aguda', recommendation: 'Extensión en polea con cuerda.' },
    ],
    posture_thresholds: [],
    variants: {
      easier: 'Extensión en polea con cuerda',
      harder: 'Press francés (skull crusher) con barra',
      home_version: 'Fondos en silla para tríceps',
    },
  },

  face_pull: {
    id: 'face_pull',
    name_es: 'Face Pull',
    muscle_primary: ['deltoides posterior', 'manguito rotador'],
    muscle_secondary: ['romboides', 'trapecio medio'],
    execution_cues: [
      'Polea a la altura de los ojos',
      'Tira hacia la frente separando las manos al final',
      'Codos hacia afuera y arriba al llegar a la cara',
      'Movimiento lento y controlado',
    ],
    common_errors: [
      'Tirar por debajo del mentón: debe ser hacia la frente',
      'Sin separar las manos: la separación activa el rotador externo',
    ],
    contraindications: [],
    posture_thresholds: [],
    variants: {
      easier: 'Con banda de resistencia fijada en puerta',
      harder: 'Con mayor peso y pausa isométrica',
      home_version: 'Con banda en puerta a altura de ojos',
    },
  },

  mountain_climber: {
    id: 'mountain_climber',
    name_es: 'Mountain Climber',
    muscle_primary: ['core', 'hip flexors'],
    muscle_secondary: ['hombros', 'cuádriceps'],
    execution_cues: [
      'Posición de plancha estable antes de comenzar',
      'Lleva la rodilla bien hacia el pecho',
      'Caderas niveladas — no las eleves al mover la pierna',
      'Velocidad controlada o explosiva según el objetivo',
    ],
    common_errors: [
      'Caderas que suben: activa el core y mantén la línea',
      'Pasos cortos: lleva la rodilla hasta el pecho',
    ],
    contraindications: [
      { zone: 'lumbar', phase: 'aguda', recommendation: 'No realizar.' },
    ],
    posture_thresholds: [],
    variants: {
      easier: 'Mountain climber lento controlado',
      harder: 'Mountain climber con sliders para mayor estabilidad',
      home_version: 'Mismo ejercicio — no requiere equipo',
    },
  },

  burpee: {
    id: 'burpee',
    name_es: 'Burpee',
    muscle_primary: ['cuerpo completo'],
    muscle_secondary: ['cardiovascular'],
    execution_cues: [
      'Movimiento fluido y continuo',
      'Plancha correcta en la fase baja',
      'Salto explosivo arriba con brazos elevados',
      'Aterriza con rodillas ligeramente dobladas',
    ],
    common_errors: [
      'Cadera caída en la plancha: activa el core',
      'Impacto excesivo al aterrizar: rodillas semiflexionadas',
    ],
    contraindications: [
      { zone: 'rodilla', phase: 'aguda', recommendation: 'No realizar.' },
      { zone: 'lumbar', phase: 'aguda', recommendation: 'No realizar.' },
    ],
    posture_thresholds: [],
    variants: {
      easier: 'Burpee sin salto ni flexión',
      harder: 'Burpee con dominada o box jump',
      home_version: 'Mismo ejercicio — no requiere equipo',
    },
  },

  jumping_jack: {
    id: 'jumping_jack',
    name_es: 'Jumping Jack',
    muscle_primary: ['cuerpo completo (cardio)'],
    muscle_secondary: ['gemelos', 'abductores'],
    execution_cues: [
      'Movimiento coordinado de brazos y piernas',
      'Aterriza con rodillas ligeramente dobladas',
      'Mantén un ritmo constante',
    ],
    common_errors: [
      'Rodillas que colapsan al aterrizar',
      'Brazos que no llegan arriba: completa el rango',
    ],
    contraindications: [],
    posture_thresholds: [],
    variants: {
      easier: 'Step jack (sin salto)',
      harder: 'Jumping jack con resistencia',
      home_version: 'Mismo ejercicio',
    },
  },

  crunch: {
    id: 'crunch',
    name_es: 'Crunch abdominal',
    muscle_primary: ['recto abdominal'],
    muscle_secondary: ['oblicuos externos'],
    execution_cues: [
      'El movimiento parte del abdomen, no del cuello',
      'Solo se elevan los hombros, no la zona lumbar',
      'Mantén 1 segundo en el punto alto',
      'Baja de forma controlada',
    ],
    common_errors: [
      'Tirar del cuello: manos solo como apoyo',
      'Rango excesivo: solo hombros del suelo',
    ],
    contraindications: [
      { zone: 'lumbar', phase: 'aguda', recommendation: 'Usar dead bug en su lugar.' },
    ],
    posture_thresholds: [],
    variants: {
      easier: 'Dead bug (menor carga lumbar)',
      harder: 'Crunch en máquina con resistencia',
      home_version: 'Mismo ejercicio',
    },
  },

  hip_hinge: {
    id: 'hip_hinge',
    name_es: 'Hip Hinge',
    muscle_primary: ['glúteo mayor', 'isquiotibiales'],
    muscle_secondary: ['erectores espinales', 'core'],
    execution_cues: [
      'Empuja la cadera hacia atrás como si cerraras una puerta con ella',
      'Mantén la espalda neutral — no flexiones la columna',
      'Rodillas ligeramente dobladas',
      'Vuelve empujando las caderas hacia adelante',
    ],
    common_errors: [
      'Flexión de rodillas excesiva: convierte en sentadilla',
      'Redondeo lumbar: el patrón hinge requiere espalda neutral',
    ],
    contraindications: [
      { zone: 'lumbar', phase: 'aguda', recommendation: 'No realizar.' },
    ],
    posture_thresholds: [],
    variants: {
      easier: 'Hip hinge con palo/dowel para feedback de postura',
      harder: 'Peso muerto con carga progresiva',
      home_version: 'Hip hinge con peso corporal o mochila',
    },
  },

}
