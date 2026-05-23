<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Routine, WorkoutLog } from '@/types'

defineOptions({ layout: AppLayout })

const props = defineProps<{
  routine: Routine | null
  todayLog: WorkoutLog | null
}>()

const activeLog = ref(props.todayLog)
const completedSets = ref<Set<string>>(new Set())
const logCreating = ref(false)

async function startWorkout() {
  if (logCreating.value || !props.routine) return
  logCreating.value = true

  const day = props.routine.days?.[0]

  try {
    const res = await fetch(route('workout.logs.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
      },
      body: JSON.stringify({
        routine_day_id: day?.id ?? null,
      }),
    })
    const data = await res.json()
    activeLog.value = data.log
  } finally {
    logCreating.value = false
  }
}

function setKey(exIdx: number, setIdx: number) {
  return `${exIdx}-${setIdx}`
}

async function toggleSet(exIdx: number, setIdx: number, routineExerciseId: number) {
  const k = setKey(exIdx, setIdx)
  if (completedSets.value.has(k)) {
    completedSets.value.delete(k)
    return
  }
  completedSets.value.add(k)

  // Persistir la serie completada en el servidor
  if (!activeLog.value) return
  try {
    await fetch(route('workout.sets.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
      },
      body: JSON.stringify({
        workout_log_id: activeLog.value.id,
        routine_exercise_id: routineExerciseId,
        set_number: setIdx,
      }),
    })
  } catch {
    // keep optimistic UI even if persistence fails
  }
}

async function completeWorkout() {
  if (!activeLog.value) return
  router.patch(route('workout.logs.complete', { workoutLog: activeLog.value.id }))
}

const todayDay = computed(() => props.routine?.days?.[0] ?? null)

const totalSets = computed(() => {
  return todayDay.value?.exercises?.reduce((sum, ex) => sum + (ex.sets ?? 3), 0) ?? 0
})

const completedCount = computed(() => completedSets.value.size)
const progress = computed(() => totalSets.value > 0 ? (completedCount.value / totalSets.value) * 100 : 0)
</script>

<template>
  <div class="min-h-screen relative" style="background:#000;">

    <div class="px-4 md:px-8 py-6 max-w-2xl mx-auto">

      <!-- No routine -->
      <div v-if="!routine" class="flex flex-col items-center text-center py-20">
        <div style="width:72px;height:72px;border-radius:18px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:20px;color:#1DF412;">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
        </div>
        <h1 class="font-display font-bold" style="font-size:26px;margin-bottom:8px;">Sin rutina asignada</h1>
        <p style="font-size:14px;color:#9CA3AF;line-height:1.6;margin-bottom:24px;">Habla con el Coach IA para generar tu plan personalizado.</p>
        <Link :href="route('chat.index')"
          class="inline-flex items-center gap-2 font-bold"
          style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:14px 24px;font-size:15px;text-decoration:none;">
          Generar rutina con Coach IA
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </Link>
      </div>

      <!-- Routine exists -->
      <template v-else>
        <!-- Header -->
        <div style="margin-bottom:24px;">
          <div style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:6px;">Rutina de hoy</div>
          <h1 class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;margin-bottom:4px;">{{ routine.name }}</h1>
          <p v-if="todayDay" style="font-size:14px;color:#9CA3AF;">{{ todayDay.name }}</p>
        </div>

        <!-- Progress bar (when active) -->
        <div v-if="activeLog && !activeLog.completed" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:16px;margin-bottom:20px;">
          <div class="flex justify-between items-center" style="margin-bottom:10px;">
            <span style="font-size:13px;color:#9CA3AF;">Progreso</span>
            <span class="font-display font-bold" style="font-size:15px;color:#1DF412;">{{ completedCount }}/{{ totalSets }} series</span>
          </div>
          <div style="height:6px;background:#242424;border-radius:3px;overflow:hidden;">
            <div style="height:100%;border-radius:3px;background:#1DF412;box-shadow:0 0 8px #1DF412;transition:width 0.3s;" :style="{ width: progress + '%' }"></div>
          </div>
        </div>

        <!-- Start button (before log) -->
        <div v-if="!activeLog" style="margin-bottom:24px;">
          <button @click="startWorkout" :disabled="logCreating"
            class="w-full flex items-center justify-center gap-2 font-bold transition-all"
            style="background:#1DF412;color:#000;border:none;border-radius:14px;padding:16px 24px;font-size:16px;cursor:pointer;box-shadow:0 4px 24px rgba(29,244,18,0.3);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            {{ logCreating ? 'Iniciando...' : 'Empezar entrenamiento' }}
          </button>
        </div>

        <!-- Exercise list -->
        <div v-if="todayDay" class="space-y-4">
          <div v-for="(ex, exIdx) in todayDay.exercises" :key="ex.id"
            style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:18px;padding:20px;">
            <div class="flex justify-between items-start" style="margin-bottom:12px;">
              <div>
                <h3 class="font-display font-bold" style="font-size:17px;letter-spacing:-0.01em;margin-bottom:4px;">{{ ex.exercise?.name }}</h3>
                <div class="flex items-center gap-3" style="font-size:13px;color:#9CA3AF;">
                  <span>{{ ex.sets }} series × {{ ex.reps }}</span>
                  <span>·</span>
                  <span>{{ ex.rest_seconds }}s descanso</span>
                </div>
              </div>
              <span style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);border-radius:6px;padding:4px 8px;">
                {{ ex.exercise?.muscle_group }}
              </span>
            </div>

            <p v-if="ex.notes" style="font-size:12px;color:#9CA3AF;margin-bottom:12px;line-height:1.5;">{{ ex.notes }}</p>

            <!-- Sets tracker -->
            <div v-if="activeLog && !activeLog.completed" class="flex gap-2 flex-wrap">
              <button
                v-for="s in (ex.sets ?? 3)"
                :key="s"
                @click="toggleSet(exIdx, s, ex.id)"
                class="flex items-center justify-center font-bold transition-all"
                style="width:44px;height:44px;border-radius:10px;font-size:13px;border:1.5px solid;cursor:pointer;"
                :style="completedSets.has(setKey(exIdx, s))
                  ? 'background:#1DF412;color:#000;border-color:#1DF412;'
                  : 'background:#242424;color:#9CA3AF;border-color:rgba(255,255,255,0.08);'"
              >
                <svg v-if="completedSets.has(setKey(exIdx, s))" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span v-else>{{ s }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Complete button (when active) -->
        <div v-if="activeLog && !activeLog.completed" style="margin-top:24px;">
          <button @click="completeWorkout"
            class="w-full flex items-center justify-center gap-2 font-bold"
            style="background:#161616;color:#1DF412;border:1.5px solid #1DF412;border-radius:14px;padding:16px 24px;font-size:16px;cursor:pointer;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Completar entrenamiento
          </button>
        </div>

        <!-- Completed state -->
        <div v-if="activeLog?.completed" class="flex flex-col items-center text-center" style="margin-top:24px;background:#161616;border:1px solid rgba(29,244,18,0.2);border-radius:18px;padding:32px;">
          <div style="width:72px;height:72px;border-radius:18px;background:#1DF412;display:flex;align-items:center;justify-content:center;margin-bottom:16px;box-shadow:0 0 40px rgba(29,244,18,0.4);">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h2 class="font-display font-bold" style="font-size:24px;margin-bottom:8px;">¡Entrenamiento completado! 🔥</h2>
          <p style="font-size:14px;color:#9CA3AF;">Excelente trabajo. Mañana seguimos.</p>
        </div>
      </template>
    </div>
  </div>
</template>
