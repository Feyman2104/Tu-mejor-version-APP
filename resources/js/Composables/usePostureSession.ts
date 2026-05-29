// Singleton que guarda el último análisis de postura completado.
// PosturePanel escribe en él cuando termina una sesión.
// ChatPanel lo lee para enriquecer el system prompt del Coach IA.

import { ref, readonly, computed } from 'vue'
import type { AspectSummary } from '@/Composables/usePostureFeedback'

export interface PostureSessionContext {
  active: boolean
  exercise: string          // ej. "Press militar"
  exerciseId: string        // ej. "overhead_press"
  score: number             // 0-100
  reps: number
  goodReps: number
  aspects: AspectSummary[]  // desglose por punto técnico
}

const EMPTY: PostureSessionContext = {
  active: false,
  exercise: '',
  exerciseId: '',
  score: 0,
  reps: 0,
  goodReps: 0,
  aspects: [],
}

// Estado module-level (singleton) — persiste durante toda la navegación SPA.
const _ctx = ref<PostureSessionContext>({ ...EMPTY })

export function usePostureSession() {
  function update(ctx: Omit<PostureSessionContext, 'active'>) {
    _ctx.value = { ...ctx, active: true }
  }

  function clear() {
    _ctx.value = { ...EMPTY }
  }

  /**
   * Texto compacto que se inyecta en el system prompt del Coach IA.
   * Devuelve null cuando no hay análisis reciente.
   */
  const contextString = computed<string | null>(() => {
    if (!_ctx.value.active) return null
    const ctx = _ctx.value

    const aspectLines = ctx.aspects.length > 0
      ? ctx.aspects.map(a => {
          const status = a.goodPct >= 85 ? '✓' : a.goodPct >= 50 ? '⚠' : '✗'
          const tip = a.goodPct < 85 ? ` — ${a.tip}` : ''
          return `  ${status} ${a.label}: ${a.goodPct}% correcto${tip}`
        }).join('\n')
      : '  (sin puntos registrados)'

    return [
      '════ ÚLTIMO ANÁLISIS DE POSTURA ════',
      `Ejercicio: ${ctx.exercise}`,
      `Score: ${ctx.score}/100 | Reps: ${ctx.reps} (${ctx.goodReps} limpias)`,
      '',
      'Desglose técnico:',
      aspectLines,
      '════════════════════════════════════',
      'El usuario acaba de analizar su técnica. Usa estos datos para dar orientación específica: señala los puntos a mejorar con su % real, felicita los puntos bien ejecutados, y sugiere correcciones concretas para los puntos bajos.',
    ].join('\n')
  })

  return {
    postureContext: readonly(_ctx),
    contextString,
    update,
    clear,
  }
}
