// Mapea slugs/nombres de ejercicios de la BD a los 6 patrones de PosturePanel.
// Claves: fragmentos del nombre o slug (lowercase). Valor: id del patrón.

export type PostureExerciseId = 'squat' | 'pushup' | 'lunge' | 'glute_bridge' | 'overhead_press' | 'bicep_curl'

const POSTURE_MAP: Record<string, PostureExerciseId> = {
  // Sentadillas / Squats
  'sentadilla':    'squat',
  'squat':         'squat',
  'goblet':        'squat',
  'hack squat':    'squat',
  'piernas':       'squat',

  // Flexiones / Push-ups
  'flexion':       'pushup',
  'flexión':       'pushup',
  'push':          'pushup',
  'fondos':        'pushup',

  // Zancadas / Lunges
  'zancada':       'lunge',
  'lunge':         'lunge',
  'estocada':      'lunge',
  'split':         'lunge',

  // Puente de glúteo
  'glute':         'glute_bridge',
  'gluteo':        'glute_bridge',
  'glúteo':        'glute_bridge',
  'hip thrust':    'glute_bridge',
  'puente':        'glute_bridge',

  // Press militar / Overhead
  'press':         'overhead_press',
  'militar':       'overhead_press',
  'overhead':      'overhead_press',
  'hombro':        'overhead_press',
  'shoulder':      'overhead_press',

  // Curl de bíceps
  'curl':          'bicep_curl',
  'bicep':         'bicep_curl',
  'bícep':         'bicep_curl',
  'biceps':        'bicep_curl',
}

/**
 * Devuelve el id de patrón de PosturePanel que mejor coincide con el nombre
 * del ejercicio, o `undefined` si no hay coincidencia.
 */
export function resolvePostureExercise(exerciseName: string): PostureExerciseId | undefined {
  const lower = exerciseName.toLowerCase()
  for (const [keyword, id] of Object.entries(POSTURE_MAP)) {
    if (lower.includes(keyword)) return id
  }
  return undefined
}
