// Evalúa los ángulos calculados contra los umbrales científicos de exerciseKnowledge.ts
// y devuelve feedback en tiempo real + puntuación.

import { ref } from 'vue'
import { EXERCISE_KNOWLEDGE, type ExerciseThreshold } from '@/data/exerciseKnowledge'
import { useAngleCalculator, type Landmark } from '@/composables/useAngleCalculator'

export interface FeedbackItem {
  joint: string
  message: string
  severity: 'warning' | 'error' | 'good'
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
        if (result.severity === 'error') frameHasError = true
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

  function reset() {
    currentFeedback.value = []
    score.value = 100
    repCount.value = 0
    goodFrames.value = 0
    totalFrames.value = 0
    phase = 'up'
  }

  return {
    currentFeedback,
    score,
    repCount,
    processFrame,
    reset,
  }
}
