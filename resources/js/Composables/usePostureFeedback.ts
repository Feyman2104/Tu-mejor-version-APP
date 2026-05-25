// Evalúa los ángulos calculados contra los umbrales científicos de exerciseKnowledge.ts
// y devuelve feedback en tiempo real + puntuación + historial de sesión.
//
// Diseño:
// - Las reglas `throughout` se evalúan cada frame (valgo, línea de cuerpo, ancho de pies…).
// - Las reglas `bottom_position` / `top_position` se evalúan SOLO en el extremo del movimiento
//   (detectado por reversiones de la trayectoria del ángulo primario) y se "fijan" hasta el
//   siguiente extremo. Así "baja más a 90°" no aparece estando de pie.
// - El conteo de reps usa el ángulo primario del ejercicio; para la sentadilla (vista frontal)
//   usa el desplazamiento vertical de la cadera, más fiable que el ángulo de rodilla de frente.

import { ref, readonly } from 'vue'
import { EXERCISE_KNOWLEDGE, type ExerciseThreshold } from '@/data/exerciseKnowledge'
import { useAngleCalculator, POSE_LANDMARKS, type Landmark } from '@/composables/useAngleCalculator'

export interface FeedbackItem {
  joint: string
  message: string
  severity: 'warning' | 'error' | 'good'
}

export interface ErrorSummaryItem {
  message: string
  joint: string
  severity: 'warning' | 'error'
  count: number
  frequency: number // 0-100: % de frames en que ocurrió
}

export interface SessionSummary {
  finalScore: number
  reps: number
  totalFrames: number
  goodFrames: number
  topErrors: ErrorSummaryItem[]
}

// Cómo detectar fase y reps de cada ejercicio.
//  - mode 'vertical': la señal es la profundidad (desplazamiento de cadera); para vista frontal.
//  - mode 'angle': la señal es el ángulo de una articulación.
//  - restAt: posición de descanso/inicio (donde se completa la rep).
//  - bottomIs: qué posición articular corresponde al "bottom_position" del ejercicio.
interface RepConfig {
  mode: 'angle' | 'vertical'
  joint?: 'knee' | 'elbow' | 'hip'
  restAt: 'flexed' | 'extended'
  bottomIs: 'flexed' | 'extended'
}

const REP_CONFIG: Record<string, RepConfig> = {
  squat:          { mode: 'vertical',               restAt: 'extended', bottomIs: 'flexed' },
  lunge:          { mode: 'angle', joint: 'knee',   restAt: 'extended', bottomIs: 'flexed' },
  pushup:         { mode: 'angle', joint: 'elbow',  restAt: 'extended', bottomIs: 'flexed' },
  dips:           { mode: 'angle', joint: 'elbow',  restAt: 'extended', bottomIs: 'flexed' },
  glute_bridge:   { mode: 'angle', joint: 'hip',    restAt: 'flexed',   bottomIs: 'flexed' },
  overhead_press: { mode: 'angle', joint: 'elbow',  restAt: 'flexed',   bottomIs: 'flexed' },
  bicep_curl:     { mode: 'angle', joint: 'elbow',  restAt: 'extended', bottomIs: 'extended' },
}

const MIN_VIS = 0.5

export function usePostureFeedback() {
  const calc = useAngleCalculator()

  const currentFeedback = ref<FeedbackItem[]>([])
  const score = ref(100)
  const repCount = ref(0)
  const goodFrames = ref(0)
  const totalFrames = ref(0)
  const isPersonDetected = ref(false)

  // Estado de la trayectoria del ángulo primario (para fases y conteo de reps).
  let trajState: 'asc' | 'desc' = 'asc'
  let trajInit = false
  let minSoFar = Infinity
  let maxSoFar = -Infinity
  let lastTopVal = 0
  let lastBotVal = 0
  // Feedback de posición (bottom/top) fijado hasta el siguiente extremo.
  let phasicFeedback: FeedbackItem[] = []

  // Acumula cuántos frames ocurrió cada error/warning durante la sesión.
  const feedbackHistory = new Map<string, { count: number; severity: 'warning' | 'error'; joint: string }>()

  function getAngleForJoint(joint: string, lm: Landmark[], side: 'left' | 'right'): number | null {
    switch (joint) {
      case 'knee':
      case 'front_knee':     return calc.kneeAngle(lm, side)
      case 'knee_valgus':    return calc.kneeValgus(lm, side)
      case 'elbow':
      case 'elbow_press':
      case 'elbow_extension':
      case 'elbow_dip':
      case 'elbow_row':      return calc.elbowAngle(lm, side)
      case 'hip':
      case 'hip_extension':
      case 'hip_lockout':    return calc.hipAngle(lm, side)
      case 'back':
      case 'back_neutral':   return calc.backTilt(lm)
      case 'hip_alignment':  return calc.bodyLineAlignment(lm, side)
      case 'stance_width':   return calc.stanceWidthRatio(lm)
      case 'squat_depth':    return calc.squatDepth(lm, side)
      default:               return null
    }
  }

  function evaluateThreshold(t: ExerciseThreshold, angle: number): FeedbackItem {
    let isBad = false
    if (t.angle_min !== undefined && angle < t.angle_min) isBad = true
    if (t.angle_max !== undefined && angle > t.angle_max) isBad = true
    return isBad
      ? { joint: t.joint, message: t.feedback_bad, severity: t.severity }
      : { joint: t.joint, message: t.feedback_good, severity: 'good' }
  }

  /** ¿Están visibles los landmarks que necesita esta señal/articulación? */
  function jointVisible(lm: Landmark[], cfg: RepConfig, side: 'left' | 'right'): boolean {
    const L = POSE_LANDMARKS
    let idx: number[]
    if (cfg.mode === 'vertical' || cfg.joint === 'knee') {
      idx = side === 'left' ? [L.LEFT_HIP, L.LEFT_KNEE, L.LEFT_ANKLE] : [L.RIGHT_HIP, L.RIGHT_KNEE, L.RIGHT_ANKLE]
    } else if (cfg.joint === 'elbow') {
      idx = side === 'left' ? [L.LEFT_SHOULDER, L.LEFT_ELBOW, L.LEFT_WRIST] : [L.RIGHT_SHOULDER, L.RIGHT_ELBOW, L.RIGHT_WRIST]
    } else { // hip
      idx = side === 'left' ? [L.LEFT_SHOULDER, L.LEFT_HIP, L.LEFT_KNEE] : [L.RIGHT_SHOULDER, L.RIGHT_HIP, L.RIGHT_KNEE]
    }
    return idx.every((i) => (lm[i]?.visibility ?? 0) >= MIN_VIS)
  }

  /** ¿Hay una persona en cuadro? (evita feedback fantasma al mover la cámara) */
  function personVisible(lm: Landmark[], side: 'left' | 'right'): boolean {
    const L = POSE_LANDMARKS
    const sh = side === 'left' ? L.LEFT_SHOULDER : L.RIGHT_SHOULDER
    const hp = side === 'left' ? L.LEFT_HIP : L.RIGHT_HIP
    return (lm[sh]?.visibility ?? 0) >= MIN_VIS && (lm[hp]?.visibility ?? 0) >= MIN_VIS
  }

  function accumulate(item: FeedbackItem) {
    if (item.severity === 'good') return
    const entry = feedbackHistory.get(item.message)
    if (entry) entry.count++
    else feedbackHistory.set(item.message, { count: 1, severity: item.severity, joint: item.joint })
  }

  /** Evalúa las reglas de una posición (bottom/top) en el extremo del movimiento. */
  function evaluateAtExtreme(
    kind: 'min' | 'max',
    lm: Landmark[],
    side: 'left' | 'right',
    thresholds: ExerciseThreshold[],
    cfg: RepConfig,
  ) {
    // En la señal: mínimo = flexionado, máximo = extendido.
    const matchCond =
      kind === 'min'
        ? (cfg.bottomIs === 'flexed' ? 'bottom_position' : 'top_position')
        : (cfg.bottomIs === 'flexed' ? 'top_position' : 'bottom_position')

    const items: FeedbackItem[] = []
    for (const t of thresholds) {
      if (t.condition !== matchCond) continue
      const angle = getAngleForJoint(t.joint, lm, side)
      if (angle === null) continue
      items.push(evaluateThreshold(t, angle))
    }
    phasicFeedback = items
  }

  /**
   * Procesa un frame de landmarks para un ejercicio dado.
   * @param exerciseId clave en EXERCISE_KNOWLEDGE (ej. 'squat', 'pushup')
   */
  function processFrame(exerciseId: string, lm: Landmark[]) {
    const knowledge = EXERCISE_KNOWLEDGE[exerciseId]
    if (!knowledge || !lm || lm.length < 33) return

    const side = calc.bestSide(lm)

    // Sin persona en cuadro → no generes feedback ni acumules frames (anti-falsos-positivos).
    if (!personVisible(lm, side)) {
      currentFeedback.value = []
      isPersonDetected.value = false
      return
    }
    isPersonDetected.value = true

    // 1) Reglas `throughout` (correcciones en tiempo real).
    const live: FeedbackItem[] = []
    for (const t of knowledge.posture_thresholds) {
      if (t.condition !== 'throughout') continue
      const angle = getAngleForJoint(t.joint, lm, side)
      if (angle === null) continue
      live.push(evaluateThreshold(t, angle))
    }

    // 2) Detección de fase + reps + reglas de posición (bottom/top).
    const cfg = REP_CONFIG[exerciseId]
    if (cfg && jointVisible(lm, cfg, side)) {
      const s =
        cfg.mode === 'vertical'
          ? calc.squatDepth(lm, side)
          : cfg.joint === 'knee'
            ? calc.kneeAngle(lm, side)
            : cfg.joint === 'elbow'
              ? calc.elbowAngle(lm, side)
              : calc.hipAngle(lm, side)

      // H: histéresis para confirmar reversión de dirección.
      // Subida respecto a v1 para que micro-oscilaciones posturas (balanceo, cambio de peso)
      // no flipping el estado de trayectoria ni disparen evaluateAtExtreme.
      const H = cfg.mode === 'vertical' ? 12 : 18  // era 8/12
      // A: amplitud mínima del movimiento para que sea una rep válida.
      const A = cfg.mode === 'vertical' ? 15 : 28

      if (!trajInit) {
        trajInit = true
        minSoFar = maxSoFar = lastTopVal = lastBotVal = s
        trajState = 'asc'
      } else if (trajState === 'desc') {
        if (s < minSoFar) minSoFar = s
        if (s > minSoFar + H) {
          // Mínimo local (posición flexionada) alcanzado.
          // SOLO disparamos feedback de posición y contamos rep si el movimiento fue suficientemente
          // amplio — así no generamos feedback-fantasma por simple balanceo o cambio de peso.
          const amp = lastTopVal - minSoFar
          if (amp >= A) {
            evaluateAtExtreme('min', lm, side, knowledge.posture_thresholds, cfg)
            if (cfg.restAt === 'flexed') repCount.value++
            lastBotVal = minSoFar
          }
          trajState = 'asc'
          maxSoFar = s
        }
      } else {
        if (s > maxSoFar) maxSoFar = s
        if (s < maxSoFar - H) {
          // Máximo local (posición extendida) alcanzado.
          const amp = maxSoFar - lastBotVal
          if (amp >= A) {
            evaluateAtExtreme('max', lm, side, knowledge.posture_thresholds, cfg)
            if (cfg.restAt === 'extended') repCount.value++
            lastTopVal = maxSoFar
          }
          trajState = 'desc'
          minSoFar = s
        }
      }
    }

    // 3) Combinar throughout + posición fijada, deduplicando por mensaje.
    const combined: FeedbackItem[] = []
    const seen = new Set<string>()
    for (const item of [...live, ...phasicFeedback]) {
      if (seen.has(item.message)) continue
      seen.add(item.message)
      combined.push(item)
    }

    // 4) Historial + puntuación.
    let frameHasError = false
    for (const item of combined) {
      accumulate(item)
      if (item.severity === 'error') frameHasError = true
    }
    totalFrames.value++
    if (!frameHasError) goodFrames.value++
    score.value = totalFrames.value > 0
      ? Math.round((goodFrames.value / totalFrames.value) * 100)
      : 100

    currentFeedback.value = combined
  }

  /**
   * Devuelve el resumen de la sesión completa.
   * Llamar ANTES de reset() para capturar los datos.
   */
  function getSummary(): SessionSummary {
    const total = totalFrames.value

    const topErrors: ErrorSummaryItem[] = [...feedbackHistory.entries()]
      .map(([message, data]) => ({
        message,
        joint: data.joint,
        severity: data.severity,
        count: data.count,
        frequency: total > 0 ? Math.round((data.count / total) * 100) : 0,
      }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 4)

    return {
      finalScore: score.value,
      reps: repCount.value,
      totalFrames: total,
      goodFrames: goodFrames.value,
      topErrors,
    }
  }

  function reset() {
    currentFeedback.value = []
    score.value = 100
    repCount.value = 0
    goodFrames.value = 0
    totalFrames.value = 0
    isPersonDetected.value = false
    trajState = 'asc'
    trajInit = false
    minSoFar = Infinity
    maxSoFar = -Infinity
    lastTopVal = 0
    lastBotVal = 0
    phasicFeedback = []
    feedbackHistory.clear()
  }

  return {
    currentFeedback,
    score,
    repCount,
    totalFrames,
    isPersonDetected: readonly(isPersonDetected),
    processFrame,
    reset,
    getSummary,
  }
}
