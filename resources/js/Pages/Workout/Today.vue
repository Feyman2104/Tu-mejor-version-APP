<script setup lang="ts">
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ExerciseModal from '@/Components/Workout/ExerciseModal.vue'
import type { Routine, RoutineDay, RoutineExercise, WorkoutLog, Exercise } from '@/types'

defineOptions({ layout: AppLayout })

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps<{
  routine:   Routine | null
  todayDay:  RoutineDay | null
  isRestDay: boolean
  todayLog:  WorkoutLog | null
  prevSets:  Record<number, { weight_kg: number | null; reps_done: number | null }>
}>()

// ─── Types ────────────────────────────────────────────────────────────────────
interface ActiveSet {
  setNumber: number
  weightKg:  string
  repsDone:  string
  completed: boolean
  savedId:   number | null
  saving:    boolean
}

// ─── State ────────────────────────────────────────────────────────────────────
const activeLog     = ref<WorkoutLog | null>(props.todayLog)
const logCreating   = ref(false)
const completing    = ref(false)
const selectedDay   = ref<RoutineDay | null>(props.todayDay ?? props.routine?.days?.[0] ?? null)
const showDayPicker = ref(false)
const activeReId    = ref<number | null>(null)
const mood          = ref<string | null>(null)
const sessionNotes  = ref('')
const modalExercise = ref<Exercise | null>(null)

// Rest timer
const restSeconds  = ref(0)
const restRunning  = ref(false)
let restInterval: ReturnType<typeof setInterval> | null = null

// Elapsed time
const elapsedSeconds = ref(0)
let elapsedInterval: ReturnType<typeof setInterval> | null = null

// Sets por routine_exercise_id
const exerciseSets = reactive<Record<number, ActiveSet[]>>({})

function csrf(): string {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
}

// ─── Init sets ────────────────────────────────────────────────────────────────
function initSets(day: RoutineDay | null) {
  if (!day) return
  day.exercises.forEach((re) => {
    if (exerciseSets[re.id]) return
    const prev        = props.prevSets[re.exercise.id]
    const defaultReps = (re.reps ?? '').split(/[-x]/)[0].trim()
    exerciseSets[re.id] = Array.from({ length: re.sets ?? 3 }, (_, i) => ({
      setNumber: i + 1,
      weightKg:  prev?.weight_kg != null ? String(prev.weight_kg) : '',
      repsDone:  defaultReps || '',
      completed: false,
      savedId:   null,
      saving:    false,
    }))
  })
}

onMounted(() => {
  initSets(selectedDay.value)
  if (activeLog.value && !activeLog.value.completed) {
    startElapsed()
    setFirstActiveExercise()
  }
})

onUnmounted(() => {
  if (restInterval) clearInterval(restInterval)
  if (elapsedInterval) clearInterval(elapsedInterval)
})

function setFirstActiveExercise() {
  const day = selectedDay.value
  if (!day) return
  for (const re of day.exercises) {
    const sets = exerciseSets[re.id]
    if (sets && sets.some(s => !s.completed)) {
      activeReId.value = re.id
      return
    }
  }
}

function startElapsed() {
  if (elapsedInterval) clearInterval(elapsedInterval)
  elapsedInterval = setInterval(() => { elapsedSeconds.value++ }, 1000)
}

// ─── Rest Timer ───────────────────────────────────────────────────────────────
function startRestTimer(seconds: number) {
  if (restInterval) clearInterval(restInterval)
  restSeconds.value = seconds
  restRunning.value = true
  restInterval = setInterval(() => {
    if (restSeconds.value <= 0) {
      stopRestTimer()
      return
    }
    restSeconds.value--
  }, 1000)
}

function stopRestTimer() {
  if (restInterval) clearInterval(restInterval)
  restInterval    = null
  restRunning.value = false
  restSeconds.value = 0
}

// ─── Day select ───────────────────────────────────────────────────────────────
function selectDay(day: RoutineDay) {
  selectedDay.value   = day
  showDayPicker.value = false
  initSets(day)
}

// ─── Computed ─────────────────────────────────────────────────────────────────
const totalSets = computed(() => {
  if (!selectedDay.value) return 0
  return selectedDay.value.exercises.reduce((sum, re) =>
    sum + (exerciseSets[re.id]?.length ?? re.sets ?? 0), 0)
})

const completedCount = computed(() =>
  Object.values(exerciseSets).reduce((sum, sets) => sum + sets.filter(s => s.completed).length, 0)
)

const progress = computed(() =>
  totalSets.value > 0 ? (completedCount.value / totalSets.value) * 100 : 0
)

const allDone = computed(() =>
  totalSets.value > 0 && completedCount.value >= totalSets.value
)

const totalVolume = computed(() =>
  Object.values(exerciseSets).reduce((sum, sets) =>
    sum + sets.filter(s => s.completed).reduce((s2, set) =>
      s2 + (parseFloat(set.weightKg) || 0) * (parseInt(set.repsDone) || 0), 0), 0)
)

const completedExercises = computed(() => {
  if (!selectedDay.value) return 0
  return selectedDay.value.exercises.filter(re => {
    const sets = exerciseSets[re.id]
    return sets && sets.length > 0 && sets.every(s => s.completed)
  }).length
})

const elapsedFormatted = computed(() => {
  const m = Math.floor(elapsedSeconds.value / 60)
  const s = elapsedSeconds.value % 60
  return `${m}:${String(s).padStart(2, '0')}`
})

const restFormatted = computed(() => {
  const m = Math.floor(restSeconds.value / 60)
  const s = restSeconds.value % 60
  return `${m}:${String(s).padStart(2, '0')}`
})

// ─── Exercise status ──────────────────────────────────────────────────────────
function exerciseStatus(re: RoutineExercise): 'done' | 'active' | 'pending' {
  const sets = exerciseSets[re.id]
  if (!sets) return 'pending'
  if (sets.every(s => s.completed)) return 'done'
  if (re.id === activeReId.value || sets.some(s => s.completed)) return 'active'
  return 'pending'
}

// ─── PR detection ─────────────────────────────────────────────────────────────
function hasPR(re: RoutineExercise): boolean {
  const prev = props.prevSets[re.exercise.id]
  if (!prev?.weight_kg) return false
  const sets = exerciseSets[re.id]
  if (!sets) return false
  return sets.some(s => s.completed && parseFloat(s.weightKg) > (prev.weight_kg ?? 0))
}

// ─── Actions ──────────────────────────────────────────────────────────────────
async function startWorkout() {
  if (logCreating.value || !selectedDay.value) return
  logCreating.value = true
  try {
    const res  = await fetch(route('workout.logs.store'), {
      method:      'POST',
      credentials: 'same-origin',
      headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body:        JSON.stringify({ routine_day_id: selectedDay.value.id }),
    })
    const data = await res.json()
    activeLog.value = data.log
    startElapsed()
    setFirstActiveExercise()
  } finally {
    logCreating.value = false
  }
}

async function toggleSet(re: RoutineExercise, idx: number) {
  if (!activeLog.value) return
  const set = exerciseSets[re.id]?.[idx]
  if (!set) return

  // Desmarcar — solo UI
  if (set.completed) { set.completed = false; return }

  if (!set.repsDone || parseInt(set.repsDone) <= 0) {
    set.repsDone = re.reps?.split(/[-x]/)[0].trim() || '1'
  }

  set.saving    = true
  set.completed = true
  activeReId.value = re.id

  // Iniciar timer de descanso
  if (re.rest_seconds) startRestTimer(re.rest_seconds)

  try {
    const res = await fetch(route('workout.sets.store'), {
      method:      'POST',
      credentials: 'same-origin',
      headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body:        JSON.stringify({
        workout_log_id:      activeLog.value.id,
        routine_exercise_id: re.id,
        set_number:          set.setNumber,
        reps_done:           parseInt(set.repsDone) || null,
        weight_kg:           set.weightKg ? parseFloat(set.weightKg) : null,
      }),
    })
    if (res.ok) {
      const data  = await res.json()
      set.savedId = data.set?.id ?? null
    }
  } catch { /* mantener UI optimista */ } finally {
    set.saving = false
  }
}

function addSet(re: RoutineExercise) {
  const sets = exerciseSets[re.id]
  if (!sets) return
  const last = sets[sets.length - 1]
  sets.push({
    setNumber: sets.length + 1,
    weightKg:  last?.weightKg ?? '',
    repsDone:  last?.repsDone ?? '',
    completed: false,
    savedId:   null,
    saving:    false,
  })
}

function removeLastSet(re: RoutineExercise) {
  const sets = exerciseSets[re.id]
  if (!sets || sets.length <= 1) return
  if (!sets[sets.length - 1].completed) sets.pop()
}

async function completeWorkout() {
  if (!activeLog.value || completing.value) return
  completing.value = true
  stopRestTimer()
  if (elapsedInterval) clearInterval(elapsedInterval)
  router.patch(
    route('workout.logs.complete', { workoutLog: activeLog.value.id }),
    {},
    { onFinish: () => { completing.value = false } }
  )
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function prevLabel(exId: number): string {
  const p = props.prevSets[exId]
  if (!p) return 'Primera vez'
  const parts: string[] = []
  if (p.weight_kg) parts.push(`${p.weight_kg}kg`)
  if (p.reps_done) parts.push(`× ${p.reps_done}`)
  return parts.length ? parts.join(' ') : '—'
}

function focusColor(focus: string): string {
  const map: Record<string, string> = {
    push: '#F59E0B', pull: '#3B82F6', legs: '#1DF412',
    full_body: '#8B5CF6', cardio: '#EF4444', rest: '#6B7280',
  }
  return map[focus] ?? '#9CA3AF'
}

const MOODS = [
  { id: 'great', label: 'Excelente', emoji: '💪' },
  { id: 'good',  label: 'Bien',      emoji: '😊' },
  { id: 'ok',    label: 'Normal',    emoji: '😐' },
  { id: 'bad',   label: 'Difícil',   emoji: '😓' },
  { id: 'awful', label: 'Mal',       emoji: '😩' },
]
</script>

<template>
  <div class="min-h-screen" style="background:#000;color:#fff;">
    <div class="px-4 md:px-8 py-6 max-w-2xl mx-auto pb-32">

      <!-- Sin rutina ─────────────────────────────────────────────────────────── -->
      <div v-if="!routine" class="flex flex-col items-center text-center py-20">
        <div style="width:72px;height:72px;border-radius:18px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/>
            <path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/>
          </svg>
        </div>
        <h1 class="font-display font-bold" style="font-size:26px;margin-bottom:8px;">Sin rutina asignada</h1>
        <p style="font-size:14px;color:#9CA3AF;line-height:1.6;margin-bottom:24px;">
          Habla con el Coach IA para generar tu plan personalizado.
        </p>
        <Link :href="route('chat.index')"
          class="inline-flex items-center gap-2 font-bold"
          style="background:#1DF412;color:#000;border-radius:12px;padding:14px 24px;font-size:15px;text-decoration:none;">
          Generar rutina con Coach IA
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </Link>
      </div>

      <!-- Con rutina ──────────────────────────────────────────────────────────── -->
      <template v-else>

        <!-- Header ──────────────────────────────────────────────────────────── -->
        <div style="margin-bottom:20px;">
          <div style="font-size:11px;color:#1DF412;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;margin-bottom:6px;">
            {{ isRestDay && !activeLog ? 'Día de descanso' : 'Entrenamiento de hoy' }}
          </div>
          <div class="flex items-center justify-between" style="margin-bottom:6px;">
            <h1 class="font-display font-bold" style="font-size:24px;letter-spacing:-0.02em;">
              {{ routine.name }}
            </h1>
            <!-- Elapsed time (only when log active) -->
            <div v-if="activeLog && !activeLog.completed"
              style="font-size:13px;color:#9CA3AF;font-variant-numeric:tabular-nums;">
              ⏱ {{ elapsedFormatted }}
            </div>
          </div>

          <!-- Selector de día -->
          <button v-if="selectedDay" @click="showDayPicker = !showDayPicker"
            class="flex items-center gap-2"
            style="background:none;border:none;cursor:pointer;padding:0;color:#9CA3AF;font-size:13px;">
            <span :style="{ color: focusColor(selectedDay.focus) }">●</span>
            {{ selectedDay.name }}
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
              :style="showDayPicker ? 'transform:rotate(180deg);' : ''">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
            <span v-if="isRestDay && !activeLog" style="font-size:11px;background:rgba(107,114,128,0.15);color:#6B7280;border-radius:6px;padding:2px 7px;margin-left:4px;">
              día libre
            </span>
          </button>

          <!-- Dropdown de días -->
          <div v-if="showDayPicker"
            style="margin-top:8px;background:#161616;border:1px solid rgba(255,255,255,0.08);border-radius:12px;overflow:hidden;z-index:10;position:relative;">
            <button v-for="day in routine.days" :key="day.id"
              @click="selectDay(day)"
              class="flex items-center gap-3 w-full text-left"
              style="padding:12px 16px;background:none;border:none;border-bottom:1px solid rgba(255,255,255,0.05);cursor:pointer;color:#fff;font-size:14px;"
              :style="selectedDay?.id === day.id ? 'background:rgba(29,244,18,0.06);' : ''">
              <span :style="{ color: focusColor(day.focus) }">●</span>
              {{ day.name }}
              <svg v-if="selectedDay?.id === day.id" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left:auto;"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
          </div>
        </div>

        <!-- Stats row (solo cuando hay log activo) ───────────────────────────── -->
        <div v-if="activeLog && !activeLog.completed"
          style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;">
          <div v-for="stat in [
            { label: 'Ejercicios', value: completedExercises + '/' + (selectedDay?.exercises.length ?? 0) },
            { label: 'Series',     value: completedCount + '/' + totalSets },
            { label: 'Volumen',    value: totalVolume > 0 ? Math.round(totalVolume) + 'kg' : '—' },
          ]" :key="stat.label"
            style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:12px;text-align:center;">
            <div class="font-display font-bold" style="font-size:18px;color:#fff;margin-bottom:2px;">
              {{ stat.value }}
            </div>
            <div style="font-size:10px;color:#4B5563;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">
              {{ stat.label }}
            </div>
          </div>
        </div>

        <!-- Barra de progreso ────────────────────────────────────────────────── -->
        <div v-if="activeLog && !activeLog.completed" style="margin-bottom:16px;">
          <div class="flex justify-between items-center" style="margin-bottom:6px;">
            <span style="font-size:12px;color:#9CA3AF;">Progreso de sesión</span>
            <span class="font-display font-bold" style="font-size:13px;color:#1DF412;">
              {{ Math.round(progress) }}%
            </span>
          </div>
          <div style="height:6px;background:#1A1A1A;border-radius:3px;overflow:hidden;">
            <div style="height:100%;border-radius:3px;background:#1DF412;box-shadow:0 0 10px rgba(29,244,18,0.4);transition:width 0.4s ease;"
              :style="{ width: progress + '%' }"/>
          </div>
        </div>

        <!-- Rest timer banner ───────────────────────────────────────────────── -->
        <div v-if="restRunning"
          style="background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);border-radius:14px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:center;gap:14px;">
          <div style="width:48px;height:48px;border-radius:12px;background:#1DF412;color:#000;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 20px rgba(29,244,18,0.35);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div style="flex:1;">
            <div style="font-size:10px;color:#1DF412;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:2px;">DESCANSO</div>
            <div class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;line-height:1;font-variant-numeric:tabular-nums;">
              {{ restFormatted }}
            </div>
          </div>
          <button @click="stopRestTimer"
            style="background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#9CA3AF;cursor:pointer;padding:8px 12px;font-size:12px;font-weight:600;">
            Saltar
          </button>
        </div>

        <!-- Botón empezar ───────────────────────────────────────────────────── -->
        <div v-if="!activeLog && selectedDay" style="margin-bottom:24px;">
          <button @click="startWorkout" :disabled="logCreating"
            class="w-full flex items-center justify-center gap-2 font-bold"
            style="background:#1DF412;color:#000;border:none;border-radius:14px;padding:16px 24px;font-size:16px;cursor:pointer;box-shadow:0 4px 24px rgba(29,244,18,0.25);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            {{ logCreating ? 'Iniciando...' : 'Empezar entrenamiento' }}
          </button>
        </div>

        <!-- Lista de ejercicios ─────────────────────────────────────────────── -->
        <div v-if="selectedDay" class="space-y-3">

          <div v-for="re in selectedDay.exercises" :key="re.id"
            style="border-radius:18px;overflow:hidden;transition:all 0.2s;"
            :style="exerciseStatus(re) === 'active'
              ? 'background:rgba(29,244,18,0.04);border:1px solid rgba(29,244,18,0.25);'
              : exerciseStatus(re) === 'done'
                ? 'background:#111;border:1px solid rgba(255,255,255,0.04);opacity:0.8;'
                : 'background:#161616;border:1px solid rgba(255,255,255,0.06);'">

            <!-- Cabecera del ejercicio ──────────────────────────────────────── -->
            <div class="flex items-start gap-3" style="padding:14px 14px 10px;">

              <!-- Thumbnail — abre modal al tocar -->
              <button @click="modalExercise = re.exercise"
                style="width:52px;height:52px;border-radius:12px;overflow:hidden;flex-shrink:0;background:#1A1A1A;border:none;cursor:pointer;padding:0;">
                <img v-if="re.exercise?.thumbnail || re.exercise?.gif_url"
                  :src="(re.exercise.thumbnail || re.exercise.gif_url)!"
                  :alt="re.exercise.name"
                  style="width:100%;height:100%;object-fit:cover;"
                  loading="lazy"
                />
                <div v-else
                  style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6.5 6.5 11 11"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/>
                    <path d="m3 10 7-7"/><path d="m14 21 7-7"/>
                  </svg>
                </div>
              </button>

              <div style="flex:1;min-width:0;">
                <div class="flex items-start justify-between gap-2" style="margin-bottom:3px;">
                  <!-- Nombre — abre modal + activa ejercicio -->
                  <button @click="modalExercise = re.exercise"
                    class="text-left font-display font-bold"
                    style="font-size:15px;letter-spacing:-0.01em;line-height:1.2;background:none;border:none;color:#fff;cursor:pointer;padding:0;text-decoration:underline;text-decoration-color:rgba(29,244,18,0.3);text-underline-offset:3px;">
                    {{ re.exercise?.name }}
                  </button>
                  <!-- PR badge -->
                  <span v-if="hasPR(re)"
                    style="flex-shrink:0;font-size:9px;font-weight:700;background:rgba(245,158,11,0.15);color:#F59E0B;border-radius:5px;padding:2px 6px;text-transform:uppercase;letter-spacing:0.08em;">
                    🏆 PR
                  </span>
                </div>
                <div style="font-size:11px;color:#6B7280;">
                  {{ re.sets }} series · {{ re.reps }} reps · {{ re.rest_seconds }}s descanso
                </div>
                <div style="font-size:11px;color:#374151;margin-top:2px;">
                  Anterior: {{ prevLabel(re.exercise.id) }}
                </div>
              </div>

              <!-- Status icon — también activa el ejercicio -->
              <button @click="activeReId = re.id; showDayPicker = false"
                style="flex-shrink:0;margin-left:4px;background:none;border:none;cursor:pointer;padding:0;">
                <!-- Done -->
                <div v-if="exerciseStatus(re) === 'done'"
                  style="width:30px;height:30px;border-radius:50%;background:#1DF412;display:flex;align-items:center;justify-content:center;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <!-- Active -->
                <div v-else-if="exerciseStatus(re) === 'active'"
                  style="width:30px;height:30px;border-radius:50%;background:transparent;border:1.5px solid #1DF412;display:flex;align-items:center;justify-content:center;">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="#1DF412"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                </div>
                <!-- Pending -->
                <div v-else
                  style="width:30px;height:30px;border-radius:50%;background:#1A1A1A;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#374151;">
                  {{ selectedDay!.exercises.findIndex(e => e.id === re.id) + 1 }}
                </div>
              </button>
            </div>

            <!-- Tabla de series (solo si el ejercicio está activo o tiene series hechas) -->
            <div v-if="exerciseSets[re.id] && (exerciseStatus(re) !== 'pending' || re.id === activeReId)"
              style="padding:0 14px;">

              <!-- Header columnas -->
              <div class="grid"
                style="grid-template-columns:28px 1fr 1fr 40px;gap:6px;padding-bottom:6px;font-size:10px;color:#374151;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;text-align:center;">
                <span>Set</span>
                <span>Kg</span>
                <span>Reps</span>
                <span></span>
              </div>

              <!-- Filas de series -->
              <div v-for="(set, idx) in exerciseSets[re.id]" :key="idx"
                class="grid"
                style="grid-template-columns:28px 1fr 1fr 40px;gap:6px;margin-bottom:6px;align-items:center;border-radius:8px;padding:2px 0;transition:background 0.15s;"
                :style="set.completed ? 'background:rgba(29,244,18,0.05);' : ''">

                <!-- Nº serie -->
                <span class="font-display font-bold text-center"
                  style="font-size:13px;"
                  :style="set.completed ? 'color:#1DF412;' : 'color:#4B5563;'">
                  {{ set.setNumber }}
                </span>

                <!-- Peso -->
                <input v-model="set.weightKg" type="number" min="0" step="0.5"
                  placeholder="—" :disabled="!activeLog || set.completed"
                  class="text-center font-bold rounded-lg"
                  style="background:#0D0D0D;border:1px solid rgba(255,255,255,0.07);color:#fff;padding:8px 4px;font-size:14px;outline:none;width:100%;-moz-appearance:textfield;"
                  :style="set.completed ? 'opacity:0.45;' : ''"
                />

                <!-- Reps -->
                <input v-model="set.repsDone" type="number" min="0" step="1"
                  placeholder="—" :disabled="!activeLog || set.completed"
                  class="text-center font-bold rounded-lg"
                  style="background:#0D0D0D;border:1px solid rgba(255,255,255,0.07);color:#fff;padding:8px 4px;font-size:14px;outline:none;width:100%;-moz-appearance:textfield;"
                  :style="set.completed ? 'opacity:0.45;' : ''"
                />

                <!-- Check -->
                <button @click="toggleSet(re, idx)" :disabled="!activeLog || set.saving"
                  class="flex items-center justify-center transition-all"
                  style="width:40px;height:36px;border-radius:10px;border:1.5px solid;cursor:pointer;flex-shrink:0;"
                  :style="set.completed
                    ? 'background:#1DF412;border-color:#1DF412;'
                    : 'background:#0D0D0D;border-color:rgba(255,255,255,0.1);'">
                  <svg v-if="set.completed" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  <div v-else-if="set.saving" style="width:12px;height:12px;border:2px solid #1DF412;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;"/>
                  <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
              </div>

              <!-- Botones agregar/quitar serie -->
              <div v-if="activeLog && !activeLog.completed"
                class="flex items-center gap-2" style="padding:6px 0 14px;">
                <button @click="addSet(re)"
                  class="flex items-center gap-1.5 font-bold"
                  style="background:transparent;border:1.5px dashed rgba(29,244,18,0.3);color:#1DF412;border-radius:8px;padding:7px 12px;font-size:12px;cursor:pointer;">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  Añadir serie
                </button>
                <button
                  v-if="exerciseSets[re.id].length > 1 && !exerciseSets[re.id][exerciseSets[re.id].length - 1].completed"
                  @click="removeLastSet(re)"
                  style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);color:#4B5563;border-radius:8px;padding:7px 10px;font-size:12px;cursor:pointer;">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
              </div>
            </div>

            <!-- Nota del ejercicio -->
            <div v-if="re.notes && exerciseStatus(re) !== 'pending'"
              style="padding:0 16px 14px;font-size:12px;color:#4B5563;line-height:1.5;">
              💡 {{ re.notes }}
            </div>
          </div>

        </div>

        <!-- Mood + notas (cuando log activo, abajo) ──────────────────────────── -->
        <div v-if="activeLog && !activeLog.completed" style="margin-top:24px;">

          <!-- Mood selector -->
          <div style="font-size:10px;color:#4B5563;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:10px;">
            ¿Cómo te sientes hoy?
          </div>
          <div style="display:flex;gap:8px;margin-bottom:20px;">
            <button v-for="m in MOODS" :key="m.id" @click="mood = m.id"
              style="flex:1;padding:10px 4px;border-radius:12px;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;border:1.5px solid;transition:all 0.15s;"
              :style="mood === m.id
                ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;'
                : 'background:#161616;border-color:rgba(255,255,255,0.06);'">
              <span style="font-size:20px;">{{ m.emoji }}</span>
              <span style="font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"
                :style="mood === m.id ? 'color:#1DF412;' : 'color:#4B5563;'">
                {{ m.label }}
              </span>
            </button>
          </div>

          <!-- Notas -->
          <textarea v-model="sessionNotes"
            placeholder="Notas de la sesión (opcional)..."
            style="width:100%;min-height:80px;background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:14px;color:#fff;font-size:14px;outline:none;resize:vertical;line-height:1.5;margin-bottom:20px;font-family:inherit;"
          />
        </div>

        <!-- Botón finalizar ─────────────────────────────────────────────────── -->
        <div v-if="activeLog && !activeLog.completed">
          <button @click="completeWorkout" :disabled="completing"
            class="w-full flex items-center justify-center gap-2 font-bold"
            style="border-radius:14px;padding:16px 24px;font-size:16px;cursor:pointer;border:none;transition:all 0.2s;"
            :style="allDone
              ? 'background:#1DF412;color:#000;box-shadow:0 4px 24px rgba(29,244,18,0.3);'
              : 'background:#161616;color:#1DF412;border:1.5px solid rgba(29,244,18,0.35);'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ completing ? 'Guardando...' : allDone ? '¡Finalizar entrenamiento! 🔥' : 'Finalizar entrenamiento' }}
          </button>
          <p v-if="!allDone && completedCount > 0" class="text-center"
            style="font-size:12px;color:#374151;margin-top:8px;">
            {{ totalSets - completedCount }} series pendientes
          </p>
        </div>

        <!-- Entrenamiento completado ────────────────────────────────────────── -->
        <div v-if="activeLog?.completed"
          class="flex flex-col items-center text-center"
          style="margin-top:24px;background:#111;border:1px solid rgba(29,244,18,0.2);border-radius:20px;padding:32px;">
          <div style="width:72px;height:72px;border-radius:18px;background:#1DF412;display:flex;align-items:center;justify-content:center;margin-bottom:16px;box-shadow:0 0 40px rgba(29,244,18,0.4);">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h2 class="font-display font-bold" style="font-size:24px;margin-bottom:16px;">¡Entrenamiento completado!</h2>

          <!-- Stats finales -->
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;width:100%;margin-bottom:20px;">
            <div v-for="s in [
              { label: 'Ejercicios', value: selectedDay?.exercises.length },
              { label: 'Series',     value: completedCount },
              { label: 'Volumen',    value: Math.round(totalVolume) + 'kg' },
            ]" :key="s.label"
              style="background:rgba(29,244,18,0.06);border:1px solid rgba(29,244,18,0.12);border-radius:12px;padding:14px;">
              <div class="font-display font-bold" style="font-size:20px;color:#1DF412;">{{ s.value }}</div>
              <div style="font-size:10px;color:#4B5563;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-top:2px;">{{ s.label }}</div>
            </div>
          </div>

          <p style="font-size:14px;color:#9CA3AF;margin-bottom:4px;">
            Excelente trabajo. Mañana seguimos 💪
          </p>
          <p style="font-size:12px;color:#374151;">
            Los datos de esta sesión se han guardado en tu historial.
          </p>
        </div>

      </template>
    </div>

    <!-- Modal de ejercicio ───────────────────────────────────────────────────── -->
    <ExerciseModal
      :exercise="modalExercise"
      @close="modalExercise = null"
    />
  </div>
</template>

<style scoped>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; }
input[type=number]:focus { border-color: rgba(29,244,18,0.5) !important; }
textarea:focus { border-color: rgba(29,244,18,0.4) !important; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
