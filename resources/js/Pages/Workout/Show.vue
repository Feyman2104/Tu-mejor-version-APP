<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { WorkoutLog, WorkoutSet, RoutineExercise, Exercise } from '@/types'

defineOptions({ layout: AppLayout })

interface SetWithExercise extends WorkoutSet {
  routine_exercise?: {
    id: number
    exercise: Exercise
  } | null
}

interface LogWithSets extends WorkoutLog {
  sets: SetWithExercise[]
  routine_day?: { id: number; name: string; focus: string } | null
}

interface PR {
  exercise_name: string
  muscle_group: string
  weight_kg: number
  prev_kg: number | null
}

const props = defineProps<{
  log: LogWithSets
  prs: PR[]
  isFresh: boolean
}>()

// ─── Stats calculadas desde sets ─────────────────────────────────────────────
const totalVolume = computed(() =>
  props.log.sets.reduce((sum, s) =>
    sum + (parseFloat(String(s.weight_kg ?? 0)) * (s.reps_done ?? 0)), 0)
)

const totalReps = computed(() =>
  props.log.sets.reduce((sum, s) => sum + (s.reps_done ?? 0), 0)
)

const uniqueExercises = computed(() => {
  const ids = new Set(props.log.sets.map(s => s.routine_exercise?.exercise?.id).filter(Boolean))
  return ids.size
})

// ─── Agrupar sets por ejercicio ───────────────────────────────────────────────
interface ExerciseGroup {
  exercise: Exercise
  sets: SetWithExercise[]
}

const exerciseGroups = computed((): ExerciseGroup[] => {
  const map = new Map<number, ExerciseGroup>()
  for (const set of props.log.sets) {
    const ex = set.routine_exercise?.exercise
    if (!ex) continue
    if (!map.has(ex.id)) map.set(ex.id, { exercise: ex, sets: [] })
    map.get(ex.id)!.sets.push(set)
  }
  return Array.from(map.values())
})

// ─── Helpers ──────────────────────────────────────────────────────────────────
function formatDate(d: string): string {
  return new Date(d).toLocaleDateString('es', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  })
}

function formatDuration(minutes: number | null): string {
  if (!minutes) return '—'
  if (minutes < 60) return `${minutes} min`
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return m > 0 ? `${h}h ${m}min` : `${h}h`
}

function focusColor(focus: string): string {
  const map: Record<string, string> = {
    push: '#F59E0B', pull: '#3B82F6', legs: '#1DF412',
    full_body: '#8B5CF6', cardio: '#EF4444',
  }
  return map[focus] ?? '#9CA3AF'
}
</script>

<template>
  <div class="min-h-screen" style="background:#000;color:#fff;">
    <div class="px-4 md:px-8 py-6 max-w-2xl mx-auto pb-28">

      <!-- Header celebración (sesión recién completada) -->
      <div v-if="isFresh" class="flex flex-col items-center text-center" style="margin-bottom:32px;">
        <div style="width:80px;height:80px;border-radius:20px;background:#1DF412;display:flex;align-items:center;justify-content:center;margin-bottom:16px;box-shadow:0 0 60px rgba(29,244,18,0.5);">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </div>
        <h1 class="font-display font-bold" style="font-size:32px;letter-spacing:-0.02em;margin-bottom:6px;">
          ¡Entrenamiento completado!
        </h1>
        <p style="font-size:14px;color:#9CA3AF;">Excelente trabajo. Estos son tus resultados 💪</p>
      </div>

      <!-- Header historial (sesión pasada) -->
      <div v-else style="margin-bottom:24px;">
        <div class="flex items-center gap-3" style="margin-bottom:4px;">
          <Link :href="route('workout.log')"
            style="color:#9CA3AF;text-decoration:none;font-size:13px;display:flex;align-items:center;gap:4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Historial
          </Link>
        </div>
        <h1 class="font-display font-bold" style="font-size:26px;letter-spacing:-0.02em;text-transform:capitalize;margin-bottom:4px;">
          {{ formatDate(log.date) }}
        </h1>
        <div v-if="log.routine_day" style="font-size:13px;display:flex;align-items:center;gap:6px;">
          <span :style="{ color: focusColor(log.routine_day.focus) }">●</span>
          <span style="color:#9CA3AF;">{{ log.routine_day.name }}</span>
        </div>
      </div>

      <!-- Stats grid ────────────────────────────────────────────────────────── -->
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:20px;">
        <div v-for="stat in [
          { label: 'Ejercicios',  value: uniqueExercises,                     icon: '🏋️' },
          { label: 'Series',      value: log.sets.length,                     icon: '📊' },
          { label: 'Volumen',     value: totalVolume > 0 ? Math.round(totalVolume) + ' kg' : '—', icon: '⚡' },
          { label: 'Duración',    value: formatDuration(log.duration_minutes), icon: '⏱️' },
        ]" :key="stat.label"
          style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:16px;">
          <div style="font-size:20px;margin-bottom:8px;">{{ stat.icon }}</div>
          <div class="font-display font-bold" style="font-size:24px;letter-spacing:-0.02em;color:#fff;margin-bottom:2px;">
            {{ stat.value }}
          </div>
          <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">
            {{ stat.label }}
          </div>
        </div>
      </div>

      <!-- Récords personales ────────────────────────────────────────────────── -->
      <div v-if="prs.length" style="margin-bottom:20px;">
        <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:10px;">
          🏆 Récords personales
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <div v-for="pr in prs" :key="pr.exercise_name"
            style="background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.2);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(245,158,11,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px;">
              🏆
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:2px;">{{ pr.exercise_name }}</div>
              <div style="font-size:12px;color:#9CA3AF;">
                <span style="color:#F59E0B;font-weight:700;">{{ pr.weight_kg }} kg</span>
                <span v-if="pr.prev_kg"> · antes: {{ pr.prev_kg }} kg</span>
                <span v-else> · primer registro con peso</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Desglose por ejercicio ────────────────────────────────────────────── -->
      <div v-if="exerciseGroups.length" style="margin-bottom:24px;">
        <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:10px;">
          Ejercicios realizados
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div v-for="group in exerciseGroups" :key="group.exercise.id"
            style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;overflow:hidden;">

            <!-- Cabecera ejercicio -->
            <div class="flex items-center gap-3" style="padding:12px 14px 10px;">
              <div style="width:44px;height:44px;border-radius:10px;overflow:hidden;flex-shrink:0;background:#111;">
                <img v-if="group.exercise.gif_url || group.exercise.thumbnail"
                  :src="(group.exercise.gif_url || group.exercise.thumbnail)!"
                  :alt="group.exercise.name"
                  style="width:100%;height:100%;object-fit:cover;"
                  loading="lazy"
                />
                <div v-else style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:18px;">💪</div>
              </div>
              <div style="flex:1;min-width:0;">
                <div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:2px;">{{ group.exercise.name }}</div>
                <div style="font-size:11px;color:#6B7280;">
                  {{ group.exercise.muscle_group }} · {{ group.sets.length }} series
                </div>
              </div>
              <!-- Volumen del ejercicio -->
              <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:14px;font-weight:700;color:#1DF412;">
                  {{ Math.round(group.sets.reduce((s, set) => s + (parseFloat(String(set.weight_kg ?? 0)) * (set.reps_done ?? 0)), 0)) }} kg
                </div>
                <div style="font-size:10px;color:#6B7280;">volumen</div>
              </div>
            </div>

            <!-- Tabla de sets -->
            <div style="padding:0 14px 12px;">
              <div style="display:grid;grid-template-columns:32px 1fr 1fr 1fr;gap:4px;padding-bottom:6px;font-size:9px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;text-align:center;">
                <span>Set</span><span>Kg</span><span>Reps</span><span>RPE</span>
              </div>
              <div v-for="set in group.sets.sort((a, b) => a.set_number - b.set_number)" :key="set.id"
                style="display:grid;grid-template-columns:32px 1fr 1fr 1fr;gap:4px;padding:6px 0;border-top:1px solid rgba(255,255,255,0.04);text-align:center;">
                <span style="font-size:12px;font-weight:700;color:#6B7280;">{{ set.set_number }}</span>
                <span style="font-size:13px;font-weight:700;color:#fff;">{{ set.weight_kg ?? '—' }}</span>
                <span style="font-size:13px;font-weight:700;color:#fff;">{{ set.reps_done ?? '—' }}</span>
                <span style="font-size:12px;color:#6B7280;">{{ set.rpe ?? '—' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sin sets registrados -->
      <div v-if="log.sets.length === 0"
        style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:32px;text-align:center;margin-bottom:24px;">
        <p style="font-size:14px;color:#6B7280;">No se registraron series en esta sesión.</p>
      </div>

      <!-- Acciones ────────────────────────────────────────────────────────────── -->
      <div style="display:flex;flex-direction:column;gap:10px;">
        <Link :href="route('workout.today')"
          class="flex items-center justify-center gap-2 font-bold w-full"
          style="background:#1DF412;color:#000;border-radius:14px;padding:16px 24px;font-size:15px;text-decoration:none;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Entrenar hoy
        </Link>
        <Link :href="route('workout.log')"
          class="flex items-center justify-center gap-2 font-bold w-full"
          style="background:#161616;color:#9CA3AF;border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:14px 24px;font-size:15px;text-decoration:none;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Ver historial completo
        </Link>
      </div>

    </div>
  </div>
</template>
