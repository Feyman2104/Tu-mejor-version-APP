// Singleton que mantiene el contexto de la sesión de entrenamiento activa.
// Today.vue escribe en él; ChatPanel.vue lo lee para enriquecer el system prompt.
// Al ser module-level, el estado persiste durante toda la navegación SPA.

import { ref, readonly, computed } from 'vue'

export interface SessionExercise {
  name: string
  setsDone: number
  setsTotal: number
  lastSet: string | null   // "70 kg × 8 reps" | null si no ha completado ninguna
}

export interface WorkoutSessionContext {
  active: boolean
  dayFocus: string
  exercises: SessionExercise[]
  completedSets: number
  totalSets: number
  volumeKg: number
  elapsedMinutes: number
}

const EMPTY: WorkoutSessionContext = {
  active: false,
  dayFocus: '',
  exercises: [],
  completedSets: 0,
  totalSets: 0,
  volumeKg: 0,
  elapsedMinutes: 0,
}

// Estado module-level (singleton) — no se recrea con cada import
const _ctx = ref<WorkoutSessionContext>({ ...EMPTY })

export function useWorkoutSession() {
  function update(ctx: WorkoutSessionContext) {
    _ctx.value = ctx
  }

  function clear() {
    _ctx.value = { ...EMPTY }
  }

  /**
   * Texto compacto que se inyecta en el system prompt del Coach IA.
   * Devuelve null cuando no hay sesión activa.
   */
  const contextString = computed<string | null>(() => {
    if (!_ctx.value.active) return null
    const ctx = _ctx.value

    const focusLabel = ctx.dayFocus
      ? `${ctx.dayFocus.charAt(0).toUpperCase()}${ctx.dayFocus.slice(1)}`
      : 'General'

    const exerciseLines = ctx.exercises
      .map(ex => {
        const prog  = `${ex.setsDone}/${ex.setsTotal} series`
        const last  = ex.lastSet ? ` — última: ${ex.lastSet}` : ''
        return `  • ${ex.name}: ${prog}${last}`
      })
      .join('\n')

    return [
      '════ SESIÓN DE ENTRENAMIENTO ACTIVA ════',
      `Día: ${focusLabel}`,
      `Progreso: ${ctx.completedSets}/${ctx.totalSets} series completadas`,
      `Tiempo: ${ctx.elapsedMinutes} min | Volumen: ${Math.round(ctx.volumeKg)} kg`,
      '',
      'Ejercicios en curso:',
      exerciseLines,
      '════════════════════════════════════════',
      'El usuario está entrenando AHORA MISMO. Usa estos datos para dar consejos precisos: técnica del ejercicio que está ejecutando, ajuste de pesos, recuperación entre series, y motivación contextual.',
    ].join('\n')
  })

  return {
    sessionContext: readonly(_ctx),
    contextString,
    update,
    clear,
  }
}
