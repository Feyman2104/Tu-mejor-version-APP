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

/** Un punto técnico evaluado (ej. "Rango de brazos") con su % de acierto. */
export interface AspectSummary {
  label: string                      // etiqueta corta legible
  goodPct: number                    // 0-100 % de evaluaciones correctas
  severity: 'warning' | 'error'      // gravedad del fallo asociado
  tip: string                        // consejo (feedback_bad) cuando goodPct < 85
}

export interface SessionSummary {
  finalScore: number
  reps: number
  goodReps: number
  totalFrames: number
  goodFrames: number
  topErrors: ErrorSummaryItem[]
  aspects: AspectSummary[]
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

// Etiqueta corta y legible por articulación, para el desglose del resumen.
const ASPECT_LABELS: Record<string, string> = {
  knee_valgus: 'Alineación de rodillas',
  stance_width: 'Ancho de pies',
  squat_depth: 'Profundidad',
  front_knee: 'Profundidad',
  knee: 'Profundidad',
  elbow: 'Rango de brazos',
  elbow_press: 'Rango de brazos',
  elbow_extension: 'Rango de brazos',
  elbow_dip: 'Rango de brazos',
  elbow_row: 'Rango de brazos',
  hip_alignment: 'Línea del cuerpo',
  back: 'Postura del torso',
  back_neutral: 'Postura del torso',
  hip: 'Extensión de cadera',
  hip_extension: 'Extensión de cadera',
  hip_lockout: 'Extensión de cadera',
}

export function usePostureFeedback() {
  const calc = useAngleCalculator()

  const currentFeedback = ref<FeedbackItem[]>([])
  const score = ref(100)
  const repCount = ref(0)
  const goodReps = ref(0)
  const goodFrames = ref(0)
  const totalFrames = ref(0)
  const isPersonDetected = ref(false)

  // Calidad acumulada para el Score ponderado: error=0, advertencia=0.5, limpio=1.
  let qualitySum = 0
  // ¿La repetición en curso ya tuvo algún fallo? (para contar "reps buenas")
  let repHadIssue = false

  // Estado de la trayectoria del ángulo primario (para fases y conteo de reps).
  let trajState: 'asc' | 'desc' = 'asc'
  let trajInit = false
  let minSoFar = Infinity
  let maxSoFar = -Infinity
  let lastTopVal = 0
  let lastBotVal = 0
  // baseline = valor de la señal en reposo (antes de empezar la técnica).
  let baseline = 0
  // active = la técnica YA se está ejecutando (el movimiento se alejó del reposo).
  // Hasta entonces NO se puntúa ni se acumula historial: el "setup" no debe penalizar.
  let active = false
  // Feedback de posición (bottom/top) fijado hasta el siguiente extremo.
  let phasicFeedback: FeedbackItem[] = []

  // Lado "pegajoso": una vez elegido el lado más visible, solo cambia si el otro
  // es claramente mejor. Evita el parpadeo izquierda/derecha que salta los ángulos.
  let stickySide: 'left' | 'right' | null = null
  const SIDE_SWITCH_MARGIN = 0.4

  // Suavizado exponencial (EMA) de ángulos y de la señal de trayectoria.
  // Reduce el ruido frame-a-frame de MediaPipe sin retrasar demasiado la respuesta.
  const EMA_ALPHA = 0.4
  const emaStore = new Map<string, number>()
  function ema(key: string, raw: number): number {
    const prev = emaStore.get(key)
    const v = prev === undefined ? raw : EMA_ALPHA * raw + (1 - EMA_ALPHA) * prev
    emaStore.set(key, v)
    return v
  }

  // Racha de frames consecutivos en que un mismo fallo está presente.
  // Un fallo solo penaliza/acumula tras DEBOUNCE frames → ignora parpadeos de 1-2 frames.
  const DEBOUNCE_FRAMES = 3
  const badStreak = new Map<string, number>()

  // Estadística por punto técnico (checkpoint): aciertos vs. total de evaluaciones.
  // Alimenta el desglose "Cómo fue tu ejecución" del resumen.
  interface CheckpointStat {
    label: string
    severity: 'warning' | 'error'
    tip: string
    good: number
    total: number
  }
  const checkpointStats = new Map<string, CheckpointStat>()

  /** Registra el resultado de evaluar un umbral (solo durante la ejecución activa). */
  function recordCheckpoint(t: ExerciseThreshold, item: FeedbackItem) {
    const key = `${t.joint}|${t.condition}`
    let stat = checkpointStats.get(key)
    if (!stat) {
      stat = {
        label: ASPECT_LABELS[t.joint] ?? t.feedback_good,
        severity: t.severity,
        tip: t.feedback_bad,
        good: 0,
        total: 0,
      }
      checkpointStats.set(key, stat)
    }
    stat.total++
    if (item.severity === 'good') stat.good++
  }

  /** Suma de visibilidad de hombro+cadera+rodilla de un lado. */
  function sideVisibility(lm: Landmark[], side: 'left' | 'right'): number {
    const L = POSE_LANDMARKS
    const ids = side === 'left'
      ? [L.LEFT_HIP, L.LEFT_KNEE, L.LEFT_SHOULDER]
      : [L.RIGHT_HIP, L.RIGHT_KNEE, L.RIGHT_SHOULDER]
    return ids.reduce((acc, i) => acc + (lm[i]?.visibility ?? 0), 0)
  }

  /** Elige el lado a analizar con histéresis para no parpadear entre frames. */
  function chooseSide(lm: Landmark[]): 'left' | 'right' {
    const l = sideVisibility(lm, 'left')
    const r = sideVisibility(lm, 'right')
    if (stickySide === null) {
      stickySide = l >= r ? 'left' : 'right'
    } else if (stickySide === 'left' && r > l + SIDE_SWITCH_MARGIN) {
      stickySide = 'right'
    } else if (stickySide === 'right' && l > r + SIDE_SWITCH_MARGIN) {
      stickySide = 'left'
    }
    return stickySide
  }

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
      const raw = getAngleForJoint(t.joint, lm, side)
      if (raw === null) continue
      const angle = ema('ex:' + t.joint, raw)
      const item = evaluateThreshold(t, angle)
      recordCheckpoint(t, item)   // 1 evaluación por extremo (≈ por repetición)
      items.push(item)
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

    const side = chooseSide(lm)

    // Sin persona en cuadro → no generes feedback ni acumules frames (anti-falsos-positivos).
    if (!personVisible(lm, side)) {
      currentFeedback.value = []
      isPersonDetected.value = false
      return
    }
    isPersonDetected.value = true

    // 1) Reglas `throughout` (correcciones en tiempo real), con ángulos suavizados.
    //    Guardamos también el umbral para registrar la estadística tras conocer `active`.
    const live: FeedbackItem[] = []
    const liveRules: { t: ExerciseThreshold; item: FeedbackItem }[] = []
    for (const t of knowledge.posture_thresholds) {
      if (t.condition !== 'throughout') continue
      const raw = getAngleForJoint(t.joint, lm, side)
      if (raw === null) continue
      const angle = ema('th:' + t.joint, raw)
      const item = evaluateThreshold(t, angle)
      live.push(item)
      liveRules.push({ t, item })
    }

    // 2) Detección de fase + reps + reglas de posición (bottom/top).
    const cfg = REP_CONFIG[exerciseId]
    if (cfg && jointVisible(lm, cfg, side)) {
      const rawS =
        cfg.mode === 'vertical'
          ? calc.squatDepth(lm, side)
          : cfg.joint === 'knee'
            ? calc.kneeAngle(lm, side)
            : cfg.joint === 'elbow'
              ? calc.elbowAngle(lm, side)
              : calc.hipAngle(lm, side)
      const s = ema('traj', rawS) // señal suavizada → fases/reps más estables

      // H: histéresis para confirmar reversión de dirección (filtra balanceo/cambio de peso).
      const H = cfg.mode === 'vertical' ? 12 : 18
      // A: amplitud mínima del movimiento para que sea una rep válida.
      const A = cfg.mode === 'vertical' ? 15 : 28
      // MOVE: cuánto debe alejarse la señal del reposo para confirmar que la técnica EMPEZÓ.
      const MOVE = A * 0.45

      if (!trajInit) {
        // Primer frame con la articulación visible: fijamos el reposo.
        trajInit = true
        baseline = minSoFar = maxSoFar = lastTopVal = lastBotVal = s
        trajState = 'asc'
      } else if (!active) {
        // Aún en setup: esperamos a que el movimiento se aleje del reposo.
        // La PRIMERA dirección real fija la fase (así no perdemos la 1ª repetición).
        if (Math.abs(s - baseline) > MOVE) {
          active = true
          // Descartamos cualquier estadística del "setup": el análisis empieza AQUÍ.
          checkpointStats.clear()
          repHadIssue = false
          if (s < baseline) {
            trajState = 'desc'; minSoFar = s; lastTopVal = baseline
          } else {
            trajState = 'asc'; maxSoFar = s; lastBotVal = baseline
          }
        } else {
          // Refinamos el reposo mientras la persona se coloca (promedio lento).
          baseline = baseline * 0.9 + s * 0.1
          minSoFar = maxSoFar = lastTopVal = lastBotVal = baseline
        }
      } else if (trajState === 'desc') {
        if (s < minSoFar) minSoFar = s
        if (s > minSoFar + H) {
          // Mínimo local (posición flexionada) alcanzado.
          const amp = lastTopVal - minSoFar
          if (amp >= A) {
            evaluateAtExtreme('min', lm, side, knowledge.posture_thresholds, cfg)
            if (cfg.restAt === 'flexed') {
              repCount.value++
              if (!repHadIssue) goodReps.value++
              repHadIssue = false
            }
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
            if (cfg.restAt === 'extended') {
              repCount.value++
              if (!repHadIssue) goodReps.value++
              repHadIssue = false
            }
            lastTopVal = maxSoFar
          }
          trajState = 'desc'
          minSoFar = s
        }
      }
    }

    // Ejercicios sin patrón de reps: se puntúan siempre (no hay "inicio" que detectar).
    if (!cfg) active = true

    // 3) Combinar throughout + posición fijada, deduplicando por mensaje.
    const combined: FeedbackItem[] = []
    const seen = new Set<string>()
    for (const item of [...live, ...phasicFeedback]) {
      if (seen.has(item.message)) continue
      seen.add(item.message)
      combined.push(item)
    }

    // 4) Debounce: un fallo solo "cuenta" tras varios frames consecutivos presente.
    const presentBad = new Set<string>()
    for (const item of combined) {
      if (item.severity !== 'good') presentBad.add(item.message)
    }
    for (const msg of presentBad) badStreak.set(msg, (badStreak.get(msg) ?? 0) + 1)
    for (const msg of [...badStreak.keys()]) {
      if (!presentBad.has(msg)) badStreak.set(msg, 0)
    }

    // 5) Historial + puntuación — SOLO cuando la técnica ya se está ejecutando.
    //    Los frames de "colócate / setup" no penalizan la nota.
    if (active) {
      // Estadística por punto técnico de las reglas `throughout` de este frame.
      for (const { t, item } of liveRules) recordCheckpoint(t, item)

      let frameHasError = false
      let frameHasWarning = false
      for (const item of combined) {
        if (item.severity === 'good') continue
        if ((badStreak.get(item.message) ?? 0) < DEBOUNCE_FRAMES) continue
        accumulate(item)
        repHadIssue = true   // la repetición en curso ya no es "limpia"
        if (item.severity === 'error') frameHasError = true
        else frameHasWarning = true
      }
      totalFrames.value++
      // Frame totalmente limpio (ni error ni advertencia) → para "Técnica %".
      if (!frameHasError && !frameHasWarning) goodFrames.value++
      // Score ponderado: error penaliza completo, advertencia la mitad.
      qualitySum += frameHasError ? 0 : frameHasWarning ? 0.5 : 1
      score.value = totalFrames.value > 0
        ? Math.round((qualitySum / totalFrames.value) * 100)
        : 100
    }

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

    // Desglose por punto técnico (peor → mejor), uno por checkpoint evaluado.
    const aspects: AspectSummary[] = [...checkpointStats.values()]
      .filter((s) => s.total > 0)
      .map((s) => ({
        label: s.label,
        goodPct: Math.round((s.good / s.total) * 100),
        severity: s.severity,
        tip: s.tip,
      }))
      .sort((a, b) => a.goodPct - b.goodPct)

    return {
      finalScore: score.value,
      reps: repCount.value,
      goodReps: goodReps.value,
      totalFrames: total,
      goodFrames: goodFrames.value,
      topErrors,
      aspects,
    }
  }

  function reset() {
    currentFeedback.value = []
    score.value = 100
    repCount.value = 0
    goodReps.value = 0
    goodFrames.value = 0
    totalFrames.value = 0
    qualitySum = 0
    repHadIssue = false
    isPersonDetected.value = false
    trajState = 'asc'
    trajInit = false
    minSoFar = Infinity
    maxSoFar = -Infinity
    lastTopVal = 0
    lastBotVal = 0
    baseline = 0
    active = false
    stickySide = null
    phasicFeedback = []
    feedbackHistory.clear()
    emaStore.clear()
    badStreak.clear()
    checkpointStats.clear()
  }

  return {
    currentFeedback,
    score,
    repCount,
    goodReps,
    totalFrames,
    isPersonDetected: readonly(isPersonDetected),
    processFrame,
    reset,
    getSummary,
  }
}
