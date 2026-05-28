<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { PaginatedResponse, WorkoutLog, WorkoutSet, Exercise } from '@/types'

defineOptions({ layout: AppLayout })

interface SetWithExercise extends WorkoutSet {
  routine_exercise?: { exercise: Exercise } | null
}

interface LogWithDetails extends WorkoutLog {
  sets: SetWithExercise[]
  routine_day?: { id: number; name: string; focus: string } | null
}

const props = defineProps<{
  logs: PaginatedResponse<LogWithDetails>
}>()

// ─── Stats globales del historial ─────────────────────────────────────────────
const totalSessions = computed(() => props.logs.total)

const totalVolumeAll = computed(() =>
  props.logs.data.reduce((sum, log) =>
    sum + log.sets.reduce((s, set) =>
      s + (parseFloat(String(set.weight_kg ?? 0)) * (set.reps_done ?? 0)), 0), 0)
)

// ─── Helpers por log ──────────────────────────────────────────────────────────
function logVolume(log: LogWithDetails): number {
  return log.sets.reduce((sum, s) =>
    sum + (parseFloat(String(s.weight_kg ?? 0)) * (s.reps_done ?? 0)), 0)
}

function logExerciseCount(log: LogWithDetails): number {
  const ids = new Set(log.sets.map(s => s.routine_exercise?.exercise?.id).filter(Boolean))
  return ids.size
}

function formatDate(d: string): string {
  return new Date(d).toLocaleDateString('es', {
    weekday: 'long', day: 'numeric', month: 'long',
  })
}

function formatDuration(minutes: number | null): string {
  if (!minutes) return null as any
  if (minutes < 60) return `${minutes} min`
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return m > 0 ? `${h}h ${m}m` : `${h}h`
}

function focusColor(focus: string): string {
  const map: Record<string, string> = {
    push: '#F59E0B', pull: '#3B82F6', legs: '#1DF412',
    full_body: '#8B5CF6', cardio: '#EF4444',
  }
  return map[focus] ?? '#9CA3AF'
}

// Grupo de ejercicios únicos para el preview
function topExercises(log: LogWithDetails): string[] {
  const names: string[] = []
  const seen = new Set<number>()
  for (const set of log.sets) {
    const ex = set.routine_exercise?.exercise
    if (ex && !seen.has(ex.id)) {
      seen.add(ex.id)
      names.push(ex.name)
      if (names.length >= 3) break
    }
  }
  return names
}
</script>

<template>
  <div class="min-h-screen" style="background:#000;color:#fff;">
    <div class="px-4 md:px-8 py-6 max-w-2xl mx-auto pb-28">

      <!-- Header ──────────────────────────────────────────────────────────── -->
      <div class="flex justify-between items-start" style="margin-bottom:24px;">
        <div>
          <h1 class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;margin-bottom:4px;">
            Historial
          </h1>
          <p style="font-size:14px;color:#9CA3AF;">Tus sesiones de entrenamiento</p>
        </div>
        <Link :href="route('workout.today')"
          class="flex items-center gap-2 font-bold flex-shrink-0"
          style="background:#1DF412;color:#000;border-radius:12px;padding:12px 16px;font-size:14px;text-decoration:none;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          <span class="hidden sm:inline">Entrenar hoy</span>
        </Link>
      </div>

      <!-- Stats globales ───────────────────────────────────────────────────── -->
      <div v-if="logs.total > 0"
        style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:24px;">
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:16px;">
          <div class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;color:#1DF412;">
            {{ logs.total }}
          </div>
          <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-top:2px;">
            Sesiones completadas
          </div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:16px;">
          <div class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;color:#1DF412;">
            {{ totalVolumeAll > 0 ? (totalVolumeAll / 1000).toFixed(1) + 't' : '—' }}
          </div>
          <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-top:2px;">
            Volumen total
          </div>
        </div>
      </div>

      <!-- Empty state ──────────────────────────────────────────────────────── -->
      <div v-if="logs.data.length === 0" class="flex flex-col items-center text-center py-20">
        <div style="width:64px;height:64px;border-radius:16px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
        </div>
        <h3 class="font-display font-bold" style="font-size:20px;margin-bottom:8px;">Sin entrenamientos aún</h3>
        <p style="font-size:14px;color:#9CA3AF;margin-bottom:20px;">Cuando completes tu primer entrenamiento aparecerá aquí.</p>
        <Link :href="route('workout.today')"
          class="font-bold"
          style="background:#1DF412;color:#000;border-radius:12px;padding:14px 24px;font-size:15px;text-decoration:none;">
          Empezar ahora
        </Link>
      </div>

      <!-- Lista de sesiones ────────────────────────────────────────────────── -->
      <div v-else class="space-y-3">
        <Link v-for="log in logs.data" :key="log.id"
          :href="route('workout.logs.show', { workoutLog: log.id })"
          style="display:block;text-decoration:none;background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px 18px;transition:border-color 0.15s;"
          class="hover:border-green-500/30">

          <!-- Fila superior: fecha + badge -->
          <div class="flex justify-between items-start" style="margin-bottom:10px;">
            <div>
              <div style="font-size:13px;color:#9CA3AF;text-transform:capitalize;margin-bottom:2px;">
                {{ formatDate(log.date) }}
              </div>
              <div v-if="log.routine_day" style="font-size:12px;display:flex;align-items:center;gap:5px;">
                <span :style="{ color: focusColor(log.routine_day.focus) }">●</span>
                <span style="color:#6B7280;">{{ log.routine_day.name }}</span>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
              <span v-if="formatDuration(log.duration_minutes)"
                style="font-size:11px;color:#6B7280;font-weight:600;">
                {{ formatDuration(log.duration_minutes) }}
              </span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </div>
          </div>

          <!-- Stats de la sesión -->
          <div style="display:flex;gap:16px;margin-bottom:10px;">
            <div style="text-align:center;">
              <div class="font-display font-bold" style="font-size:18px;color:#fff;">{{ logExerciseCount(log) }}</div>
              <div style="font-size:9px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;">ejercicios</div>
            </div>
            <div style="text-align:center;">
              <div class="font-display font-bold" style="font-size:18px;color:#fff;">{{ log.sets.length }}</div>
              <div style="font-size:9px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;">series</div>
            </div>
            <div style="text-align:center;">
              <div class="font-display font-bold" style="font-size:18px;color:#1DF412;">
                {{ logVolume(log) > 0 ? Math.round(logVolume(log)) + ' kg' : '—' }}
              </div>
              <div style="font-size:9px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;">volumen</div>
            </div>
          </div>

          <!-- Preview ejercicios -->
          <div v-if="topExercises(log).length" style="display:flex;flex-wrap:wrap;gap:5px;">
            <span v-for="name in topExercises(log)" :key="name"
              style="font-size:11px;color:#6B7280;background:#111;border:1px solid rgba(255,255,255,0.05);border-radius:6px;padding:3px 8px;">
              {{ name }}
            </span>
            <span v-if="logExerciseCount(log) > 3"
              style="font-size:11px;color:#6B7280;padding:3px 4px;">
              +{{ logExerciseCount(log) - 3 }} más
            </span>
          </div>
        </Link>
      </div>

      <!-- Paginación ───────────────────────────────────────────────────────── -->
      <div v-if="logs.last_page > 1" class="flex justify-center gap-2" style="margin-top:24px;">
        <Link v-for="link in logs.links" :key="link.label"
          :href="link.url ?? '#'"
          :class="{ 'pointer-events-none': !link.url }"
          style="padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all 0.15s;"
          :style="link.active
            ? 'background:#1DF412;color:#000;'
            : link.url
              ? 'background:#161616;border:1px solid rgba(255,255,255,0.06);color:#9CA3AF;'
              : 'background:#0D0D0D;color:#2A2A2A;cursor:default;'"
          v-html="link.label"
        />
      </div>

    </div>
  </div>
</template>
