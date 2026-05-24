// Evalúa los ángulos calculados contra los umbrales científicos de exerciseKnowledge.ts
// y devuelve feedback en tiempo real + puntuación + historial de sesión.

import { ref } from 'vue'
import { EXERCISE_KNOWLEDGE, type ExerciseThreshold } from '@/data/exerciseKnowledge'
import { useAngleCalculator, type Landmark } from '@/composables/useAngleCalculator'

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

export function usePostureFeedback() {
  const calc = useAngleCalculator()

  const currentFeedback = ref<FeedbackItem[]>([])
  const score = ref(100)
  const repCount = ref(0)

  // Estado para conteo de repeticiones (detección de fase por ángulo de rodilla)
  let phase: 'up' | 'down' = 'up'
  const goodFrames = ref(0)
  const totalFrames = ref(0)

  // Acumula cuántos frames ocurrió cada error/warning durante la sesión
  const feedbackHistory = new Map<string, { count: number; severity: 'warning' | 'error'; joint: string }>()

  function getAngleForJoint(joint: string, lm: Landmark[], side: 'left' | 'right'): number | null {
    switch (joint) {
      case 'knee':        return calc.kneeAngle(lm, side)
      case 'knee_valgus': return calc.kneeValgus(lm, side)
      case 'elbow':       return calc.elbowAngle(lm, side)
      case 'hip':         return calc.hipAngle(lm, side)
      case 'back':        return calc.backTilt(lm)
      default:            return null
    }
  }

  function evaluateThreshold(t: ExerciseThreshold, angle: number): FeedbackItem | null {
    let isBad = false

    if (t.angle_min !== undefined && angle < t.angle_min) isBad = true
    if (t.angle_max !== undefined && angle > t.angle_max) isBad = true

    if (isBad) {
      return { joint: t.joint, message: t.feedback_bad, severity: t.severity }
    }
    return { joint: t.joint, message: t.feedback_good, severity: 'good' }
  }

  /**
   * Procesa un frame de landmarks para un ejercicio dado.
   * @param exerciseId clave en EXERCISE_KNOWLEDGE (ej. 'squat', 'pushup')
   */
  function processFrame(exerciseId: string, lm: Landmark[]) {
    const knowledge = EXERCISE_KNOWLEDGE[exerciseId]
    if (!knowledge || !lm || lm.length < 33) return

    const side = calc.bestSide(lm)
    const feedback: FeedbackItem[] = []
    let frameHasError = false

    for (const threshold of knowledge.posture_thresholds) {
      const angle = getAngleForJoint(threshold.joint, lm, side)
      if (angle === null) continue

      const result = evaluateThreshold(threshold, angle)
      if (result) {
        feedback.push(result)

        // Acumular en historial solo errores/warnings (no los buenos)
        if (result.severity !== 'good') {
          const entry = feedbackHistory.get(result.message)
          if (entry) {
            entry.count++
          } else {
            feedbackHistory.set(result.message, {
              count: 1,
              severity: result.severity,
              joint: result.joint,
            })
          }
          if (result.severity === 'error') frameHasError = true
        }
      }
    }

    // Conteo de repeticiones basado en ángulo de rodilla (para ejercicios de pierna)
    const kneeA = calc.kneeAngle(lm, side)
    if (kneeA < 100 && phase === 'up') {
      phase = 'down'
    } else if (kneeA > 160 && phase === 'down') {
      phase = 'up'
      repCount.value++
    }

    // Puntuación acumulada
    totalFrames.value++
    if (!frameHasError) goodFrames.value++
    score.value = totalFrames.value > 0
      ? Math.round((goodFrames.value / totalFrames.value) * 100)
      : 100

    currentFeedback.value = feedback
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
    phase = 'up'
    feedbackHistory.clear()
  }

  return {
    currentFeedback,
    score,
    repCount,
    processFrame,
    reset,
    getSummary,
  }
}
