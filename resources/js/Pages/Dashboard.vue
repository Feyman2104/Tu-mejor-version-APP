<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Sparkline from '@/Components/Charts/Sparkline.vue'
import ProgressRing from '@/Components/Charts/ProgressRing.vue'
import type { User, Routine, RoutineDay, WorkoutLog, ProgressEntry } from '@/types'

defineOptions({ layout: AppLayout })

const page = usePage()
const user = computed(() => page.props.auth.user as User)

const props = defineProps<{
  streak:               number
  weekDays:             boolean[]
  routine:              Routine | null
  todayDay:             RoutineDay | null
  isRestDay:            boolean
  todayLog:             WorkoutLog | null
  recentProgress:       ProgressEntry[]
  workoutsThisMonth:    number
  workoutsThisWeek:     number
  weeklyVolume:         number
  weeklyExercises:      number
  adherencePct:        number | null
  weightSeries:         { value: number; date: string }[]
  plannedDaysThisWeek:  number
}>()

const dayLabels = ['D', 'L', 'M', 'M', 'J', 'V', 'S']

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Buenos días'
  if (h < 18) return 'Buenas tardes'
  return 'Buenas noches'
})

const firstName = computed(() => user.value?.name?.split(' ')[0] ?? '')

const roleLabel = computed(() => {
  const map: Record<string, string> = { student: 'Estudiante', trainer: 'Entrenador', admin: 'Administrador' }
  return map[user.value?.role ?? ''] ?? (user.value?.role ?? '')
})

const focusColor = (focus: string) => ({
  push: '#F59E0B', pull: '#3B82F6', legs: '#1DF412',
  full_body: '#8B5CF6', cardio: '#EF4444',
} as Record<string, string>)[focus] ?? '#9CA3AF'

const workoutButtonLabel = computed(() => {
  if (!props.todayLog) return 'Empezar entrenamiento'
  if (props.todayLog.completed) return '¡Ya entrenaste hoy! 🎉'
  return 'Continuar entrenamiento'
})

const workoutButtonDisabled = computed(() => props.todayLog?.completed === true)

const kpiCards = computed(() => [
  {
    label: 'Racha',
    value: props.streak,
    unit: 'días',
    icon: 'fire',
    color: '#1DF412',
  },
  {
    label: 'Este mes',
    value: props.workoutsThisMonth,
    unit: 'entrenamientos',
    icon: 'calendar',
    color: '#3B82F6',
  },
  {
    label: 'Volumen sem.',
    value: props.weeklyVolume.toLocaleString('es-CO'),
    unit: 'kg totales',
    icon: 'dumbbell',
    color: '#F59E0B',
  },
  {
    label: 'Adherencia',
    value: props.adherencePct !== null ? props.adherencePct : '—',
    unit: props.adherencePct !== null ? '%' : 'sin plan',
    icon: 'chart',
    color: '#8B5CF6',
    showRing: props.adherencePct !== null,
  },
])

const displayWeightSeries = computed(() => {
  if (props.weightSeries && props.weightSeries.length >= 2) return props.weightSeries
  return null
})

const animatedValues = ref<Record<string, number>>({})
const _animTimers: ReturnType<typeof setInterval>[] = []
onMounted(() => {
  const targets = [props.streak, props.workoutsThisMonth, props.workoutsThisWeek]
  targets.forEach((v, i) => {
    const key = ['streak', 'month', 'week'][i]
    animatedValues.value[key] = 0
    const step = v / 40
    let current = 0
    const timer = setInterval(() => {
      current += step
      if (current >= v) {
        animatedValues.value[key] = v
        clearInterval(timer)
      } else {
        animatedValues.value[key] = Math.floor(current)
      }
    }, 20)
    _animTimers.push(timer)
  })
})
onUnmounted(() => { _animTimers.forEach(clearInterval) })
</script>

<template>
  <div class="min-h-screen relative" style="background:#000;">

    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
      <div class="absolute rounded-full" style="top:-5%;right:-30%;width:500px;height:500px;background:radial-gradient(circle,rgba(29,244,18,0.15) 0%,transparent 65%);filter:blur(40px);"></div>
      <div class="absolute rounded-full" style="bottom:15%;left:15%;width:450px;height:450px;background:radial-gradient(circle,rgba(29,244,18,0.13) 0%,transparent 65%);filter:blur(40px);"></div>
    </div>

    <!-- ===== MOBILE ===== -->
    <div class="md:hidden relative z-10 pb-20">
      <div class="px-4 pt-6 space-y-4">

        <div class="flex justify-between items-start">
          <div>
            <p style="font-size:13px;color:#9CA3AF;margin-bottom:2px;">{{ greeting }},</p>
            <h1 class="font-display font-bold" style="font-size:26px;letter-spacing:-0.02em;line-height:1.1;">
              {{ firstName }} <span style="color:#1DF412;">💪</span>
            </h1>
          </div>
          <div class="flex items-center justify-center font-bold text-black" style="width:40px;height:40px;border-radius:50%;background:#1DF412;font-size:16px;">
            {{ user?.name?.charAt(0)?.toUpperCase() }}
          </div>
        </div>

        <!-- KPI cards row -->
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;">
          <div v-for="kpi in kpiCards" :key="kpi.label"
            style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:14px;position:relative;overflow:hidden;">
            <div class="absolute rounded-full" :style="`top:-20px;right:-20px;width:100px;height:100px;background:radial-gradient(circle,${kpi.color}22 0%,transparent 70%);filter:blur(15px);`"></div>
            <div class="relative">
              <div style="font-size:10px;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;">{{ kpi.label }}</div>
              <div class="font-display font-bold" style="font-size:26px;color:#fff;line-height:1;margin-bottom:2px;">
                {{ typeof kpi.value === 'number' ? kpi.value.toLocaleString('es-CO') : kpi.value }}
              </div>
              <div style="font-size:11px;color:#6B7280;">{{ kpi.unit }}</div>
            </div>
          </div>
        </div>

        <!-- Weight sparkline -->
        <div v-if="displayWeightSeries"
          style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px;">
          <div style="font-size:11px;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:10px;">Peso corporal</div>
          <Sparkline :data="displayWeightSeries" :height="70" />
        </div>

        <!-- Streak card -->
        <div class="relative overflow-hidden" style="background:linear-gradient(135deg,#161616 0%,#0D0D0D 100%);border:1px solid rgba(29,244,18,0.2);border-radius:18px;padding:20px;">
          <div class="absolute rounded-full" style="top:-20px;right:-20px;width:120px;height:120px;background:radial-gradient(circle,rgba(29,244,18,0.15) 0%,transparent 70%);filter:blur(20px);"></div>
          <div class="relative flex justify-between items-start" style="margin-bottom:16px;">
            <div>
              <div class="flex items-center gap-1" style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:6px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" style="color:#1DF412;"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                RACHA ACTIVA
              </div>
              <div class="flex items-baseline gap-2">
                <span class="font-display font-bold" style="font-size:40px;letter-spacing:-0.03em;line-height:1;">{{ streak }}</span>
                <span style="font-size:14px;color:#9CA3AF;font-weight:500;">días seguidos</span>
              </div>
            </div>
            <div class="flex items-center justify-center" style="width:44px;height:44px;border-radius:12px;background:#1DF412;color:#000;box-shadow:0 0 20px rgba(29,244,18,0.5);">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
            </div>
          </div>
          <div class="relative flex gap-1">
            <div v-for="(active, i) in weekDays" :key="i" class="flex-1 text-center">
              <div class="flex items-center justify-center" style="width:100%;height:32px;border-radius:8px;margin-bottom:4px;transition:all 0.2s;"
                :style="active ? 'background:#1DF412;' : 'background:#242424;opacity:0.5;'">
                <svg v-if="active" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
              <div style="font-size:10px;color:#9CA3AF;font-weight:500;">{{ dayLabels[i] }}</div>
            </div>
          </div>
        </div>

        <!-- Today's workout card -->
        <div>
          <div class="flex justify-between items-center" style="margin-bottom:12px;">
            <h3 class="font-display font-bold" style="font-size:17px;letter-spacing:-0.01em;">Entrenamiento de hoy</h3>
            <Link :href="route('workout.log')" style="color:#1DF412;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:4px;text-decoration:none;">
              Historial <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </Link>
          </div>

          <div v-if="!routine" class="flex flex-col items-center justify-center text-center" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:22px;padding:40px 24px;">
            <div style="width:64px;height:64px;border-radius:16px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:16px;color:#1DF412;">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11 11-11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/></svg>
            </div>
            <h3 class="font-display font-bold" style="font-size:20px;margin-bottom:8px;">Aún sin rutina</h3>
            <p style="font-size:14px;color:#9CA3AF;line-height:1.5;margin-bottom:20px;">Genera tu plan personalizado con IA en segundos.</p>
            <Link :href="route('chat.index')" class="inline-flex items-center gap-2 font-bold" style="background:#1DF412;color:#000;border-radius:12px;padding:12px 20px;font-size:14px;text-decoration:none;">
              Generar rutina con IA
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </Link>
          </div>

          <div v-else-if="isRestDay" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:22px;padding:24px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
              <div style="width:44px;height:44px;border-radius:12px;background:#242424;display:flex;align-items:center;justify-content:center;font-size:22px;">😴</div>
              <div>
                <div style="font-size:11px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:2px;">Hoy toca</div>
                <div class="font-display font-bold" style="font-size:20px;letter-spacing:-0.01em;">Día de descanso</div>
              </div>
            </div>
            <p style="font-size:13px;color:#9CA3AF;line-height:1.5;margin-bottom:16px;">El descanso es parte del entrenamiento. Hoy tu cuerpo se recupera y crece. 💪</p>
            <div style="font-size:11px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">O entrena otro día:</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;">
              <Link v-for="day in routine!.days" :key="day.id"
                :href="route('workout.today')"
                style="font-size:12px;font-weight:600;border-radius:8px;padding:6px 12px;text-decoration:none;display:flex;align-items:center;gap:5px;background:#111;border:1px solid rgba(255,255,255,0.08);color:#9CA3AF;">
                <span :style="{ color: focusColor(day.focus) }">●</span>
                {{ day.name }}
              </Link>
            </div>
            <Link :href="route('workout.today')" class="w-full flex items-center justify-center gap-2 font-bold" style="background:#161616;color:#1DF412;border:1.5px solid rgba(29,244,18,0.35);border-radius:14px;padding:14px;font-size:15px;text-decoration:none;">
              Ver todos los ejercicios
            </Link>
          </div>

          <div v-else style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:22px;overflow:hidden;">
            <div style="padding:16px 18px 14px;border-bottom:1px solid rgba(255,255,255,0.05);">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <span :style="{ color: focusColor(todayDay!.focus) }">●</span>
                  <span style="font-size:11px;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;">Hoy</span>
                </div>
                <span style="font-size:11px;color:#6B7280;">{{ todayDay!.exercises.length }} ejercicios</span>
              </div>
              <div class="font-display font-bold" style="font-size:20px;letter-spacing:-0.01em;">{{ todayDay!.name }}</div>
            </div>

            <div style="padding:12px 18px 0;">
              <div v-for="(re, i) in todayDay!.exercises.slice(0, 4)" :key="re.id"
                style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                <div style="width:40px;height:40px;border-radius:10px;overflow:hidden;flex-shrink:0;background:#111;">
                  <img v-if="re.exercise?.gif_url || re.exercise?.thumbnail"
                    :src="(re.exercise.gif_url || re.exercise.thumbnail)!"
                    :alt="re.exercise.name"
                    style="width:100%;height:100%;object-fit:cover;"
                    loading="lazy"
                  />
                  <div v-else style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:16px;">💪</div>
                </div>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ re.exercise?.name }}</div>
                  <div style="font-size:11px;color:#6B7280;">{{ re.sets }} × {{ re.reps }}</div>
                </div>
                <div v-if="todayLog?.completed" style="width:20px;height:20px;border-radius:50%;background:#1DF412;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
              </div>
              <div v-if="todayDay!.exercises.length > 4" style="padding:8px 0 2px;font-size:12px;color:#6B7280;text-align:center;">
                +{{ todayDay!.exercises.length - 4 }} ejercicios más
              </div>
            </div>

            <div style="padding:14px 18px 18px;">
              <div v-if="todayLog?.completed"
                style="width:100%;display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);border-radius:14px;">
                <svg width="16" ID="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span style="font-size:15px;font-weight:700;color:#1DF412;">¡Ya entrenaste hoy!</span>
              </div>
              <Link v-else :href="route('workout.today')"
                class="w-full flex items-center justify-center gap-2 font-bold"
                style="padding:14px;background:#1DF412;color:#000;border-radius:14px;font-size:15px;text-decoration:none;box-shadow:0 4px 20px rgba(29,244,18,0.25);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                {{ todayLog ? 'Continuar entrenamiento' : 'Empezar entrenamiento' }}
              </Link>
            </div>
          </div>
        </div>

        <!-- Quick actions -->
        <div>
          <h3 class="font-display font-bold" style="font-size:17px;letter-spacing:-0.01em;margin-bottom:12px;">Acceso rápido</h3>
          <div class="grid grid-cols-2 gap-3">
            <Link :href="route('chat.index')" class="flex flex-col gap-2 transition-all" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px;text-decoration:none;">
              <div class="flex items-center justify-center" style="width:40px;height:40px;border-radius:10px;background:rgba(29,244,18,0.08);color:#1DF412;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
              </div>
              <span class="font-display font-semibold" style="font-size:15px;color:#fff;letter-spacing:-0.01em;">Coach IA</span>
              <span style="font-size:12px;color:#9CA3AF;">Pregunta lo que quieras</span>
            </Link>
            <Link :href="route('posture.index')" class="flex flex-col gap-2 transition-all" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px;text-decoration:none;">
              <div class="flex items-center justify-center" style="width:40px;height:40px;border-radius:10px;background:rgba(29,244,18,0.08);color:#1DF412;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              </div>
              <span class="font-display font-semibold" style="font-size:15px;color:#fff;letter-spacing:-0.01em;">Postura</span>
              <span style="font-size:12px;color:#9CA3AF;">Analiza tu forma</span>
            </Link>
            <Link :href="route('progress.index')" class="flex flex-col gap-2 transition-all" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px;text-decoration:none;">
              <div class="flex items-center justify-center" style="width:40px;height:40px;border-radius:10px;background:rgba(29,244,18,0.08);color:#1DF412;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
              </div>
              <span class="font-display font-semibold" style="font-size:15px;color:#fff;letter-spacing:-0.01em;">Progreso</span>
              <span style="font-size:12px;color:#9CA3AF;">Registra tu evolución</span>
            </Link>
            <Link :href="route('exercises.index')" class="flex flex-col gap-2 transition-all" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px;text-decoration:none;">
              <div class="flex items-center justify-center" style="width:40px;height:40px;border-radius:10px;background:rgba(29,244,18,0.08);color:#1DF412;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/></svg>
              </div>
              <span class="font-display font-semibold" style="font-size:15px;color:#fff;letter-spacing:-0.01em;">Ejercicios</span>
              <span style="font-size:12px;color:#9CA3AF;">Ver todos</span>
            </Link>
          </div>
        </div>

      </div>
    </div>

    <!-- ===== DESKTOP ===== -->
    <div class="hidden md:block relative z-10">
      <div style="max-width:1280px;margin:0 auto;padding:40px 48px;">

        <div class="flex justify-between items-center" style="margin-bottom:36px;">
          <div>
            <p style="font-size:14px;color:#9CA3AF;margin-bottom:4px;">{{ greeting }},</p>
            <h1 class="font-display font-bold" style="font-size:36px;letter-spacing:-0.02em;line-height:1.1;">
              {{ firstName }} <span style="color:#1DF412;">💪</span>
            </h1>
          </div>
          <div class="flex items-center gap-3">
            <Link :href="route('chat.index')"
              class="flex items-center gap-2 font-bold transition-all"
              style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:12px 20px;font-size:14px;cursor:pointer;text-decoration:none;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
              Hablar con el Coach
            </Link>
          </div>
        </div>

        <!-- KPI row -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
          <div v-for="kpi in kpiCards" :key="kpi.label"
            style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:18px;padding:20px;position:relative;overflow:hidden;">
            <div class="absolute rounded-full" :style="`top:-25px;right:-25px;width:130px;height:130px;background:radial-gradient(circle,${kpi.color}20 0%,transparent 70%);filter:blur(20px);`"></div>
            <div class="relative">
              <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px;">
                <div class="flex items-center justify-center" :style="`width:32px;height:32px;border-radius:8px;background:${kpi.color}18;color:${kpi.color};`">
                  <svg v-if="kpi.icon === 'fire'" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                  <svg v-if="kpi.icon === 'calendar'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  <svg v-if="kpi.icon === 'dumbbell'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/></svg>
                  <svg v-if="kpi.icon === 'chart'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <span style="font-size:12px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;">{{ kpi.label }}</span>
              </div>
              <div class="font-display font-bold" style="font-size:30px;color:#fff;line-height:1;margin-bottom:4px;">
                {{ typeof kpi.value === 'number' ? kpi.value.toLocaleString('es-CO') : kpi.value }}
              </div>
              <div style="font-size:12px;color:#6B7280;">{{ kpi.unit }}</div>
              <div v-if="kpi.showRing" style="position:absolute;top:16px;right:16px;">
                <ProgressRing :value="props.adherencePct!" :max="100" :size="44" :stroke-width="5" :color="kpi.color" />
              </div>
            </div>
          </div>
        </div>

        <!-- Main grid -->
        <div class="grid gap-6" style="grid-template-columns:2fr 1fr;">

          <div class="flex flex-col gap-6">

            <!-- Today's workout -->
            <div>
              <div class="flex justify-between items-center" style="margin-bottom:16px;">
                <h2 class="font-display font-bold" style="font-size:22px;letter-spacing:-0.01em;">Entrenamiento de hoy</h2>
                <Link :href="route('workout.log')" style="color:#1DF412;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                  Ver historial <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </Link>
              </div>

              <div v-if="isRestDay" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:22px;padding:32px;">
                <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;">
                  <div style="width:56px;height:56px;border-radius:14px;background:#242424;display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;">😴</div>
                  <div>
                    <div style="font-size:11px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">Hoy toca</div>
                    <div class="font-display font-bold" style="font-size:26px;letter-spacing:-0.02em;">Día de descanso</div>
                  </div>
                </div>
                <p style="font-size:14px;color:#9CA3AF;line-height:1.6;margin-bottom:20px;">El descanso es parte del entrenamiento. Hoy tu cuerpo se recupera y crece. 💪</p>
                <div style="font-size:11px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:10px;">O entrena otro día:</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;">
                  <Link v-for="day in routine!.days" :key="day.id"
                    :href="route('workout.today')"
                    style="font-size:13px;font-weight:600;border-radius:10px;padding:8px 14px;text-decoration:none;display:flex;align-items:center;gap:6px;background:#111;border:1px solid rgba(255,255,255,0.08);color:#9CA3AF;">
                    <span :style="{ color: focusColor(day.focus) }">●</span>
                    {{ day.name }}
                  </Link>
                </div>
                <Link :href="route('workout.today')" class="inline-flex items-center gap-2 font-bold"
                  style="background:#161616;color:#1DF412;border:1.5px solid rgba(29,244,18,0.35);border-radius:14px;padding:14px 24px;font-size:15px;text-decoration:none;">
                  Ver todos los ejercicios
                </Link>
              </div>

              <div v-else-if="todayDay" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:22px;overflow:hidden;">
                <div style="padding:20px 24px 16px;border-bottom:1px solid rgba(255,255,255,0.05);">
                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                      <span :style="{ color: focusColor(todayDay.focus) }">●</span>
                      <span style="font-size:11px;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;">Hoy</span>
                    </div>
                    <span style="font-size:12px;color:#6B7280;">{{ todayDay.exercises.length }} ejercicios</span>
                  </div>
                  <div class="font-display font-bold" style="font-size:24px;letter-spacing:-0.01em;">{{ todayDay.name }}</div>
                </div>

                <div style="padding:16px 24px 0;">
                  <div v-for="(re, i) in todayDay.exercises.slice(0, 5)" :key="re.id"
                    style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                    <div style="width:44px;height:44px;border-radius:10px;overflow:hidden;flex-shrink:0;background:#111;">
                      <img v-if="re.exercise?.gif_url || re.exercise?.thumbnail"
                        :src="(re.exercise.gif_url || re.exercise.thumbnail)!"
                        :alt="re.exercise?.name"
                        style="width:100%;height:100%;object-fit:cover;"
                        loading="lazy"
                      />
                      <div v-else style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:18px;">💪</div>
                    </div>
                    <div style="flex:1;min-width:0;">
                      <div style="font-size:14px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ re.exercise?.name }}</div>
                      <div style="font-size:12px;color:#6B7280;">{{ re.sets }} × {{ re.reps }}</div>
                    </div>
                    <div v-if="todayLog?.completed" style="width:22px;height:22px;border-radius:50%;background:#1DF412;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                  </div>
                  <div v-if="todayDay.exercises.length > 5" style="padding:10px 0 2px;font-size:12px;color:#6B7280;text-align:center;">
                    +{{ todayDay.exercises.length - 5 }} ejercicios más
                  </div>
                </div>

                <div style="padding:16px 24px 24px;">
                  <div v-if="todayLog?.completed"
                    style="width:100%;display:flex;align-items:center;justify-content:center;gap:8px;padding:16px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);border-radius:14px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <span style="font-size:15px;font-weight:700;color:#1DF412;">¡Ya entrenaste hoy!</span>
                  </div>
                  <Link v-else :href="route('workout.today')"
                    class="w-full flex items-center justify-center gap-2 font-bold"
                    style="padding:16px 24px;background:#1DF412;color:#000;border-radius:14px;font-size:15px;text-decoration:none;box-shadow:0 4px 20px rgba(29,244,18,0.25);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    {{ todayLog ? 'Continuar entrenamiento' : 'Empezar entrenamiento' }}
                  </Link>
                </div>
              </div>

              <div v-else class="flex flex-col items-center justify-center text-center" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:22px;padding:60px 24px;aspect-ratio:16/10;">
                <div class="flex items-center justify-center" style="width:64px;height:64px;border-radius:16px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);margin-bottom:16px;color:#1DF412;">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/></svg>
                </div>
                <h3 class="font-display font-bold" style="font-size:22px;margin-bottom:8px;">Aún sin rutina</h3>
                <p style="font-size:15px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;max-width:360px;">Genera tu plan personalizado con IA en segundos usando el Coach.</p>
                <Link :href="route('chat.index')"
                  class="inline-flex items-center gap-2 font-bold"
                  style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:14px 24px;font-size:15px;cursor:pointer;text-decoration:none;">
                  Generar rutina con Coach IA
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </Link>
              </div>
            </div>

            <!-- Quick actions row -->
            <div class="grid grid-cols-4 gap-4">
              <Link v-for="action in [
                { label:'Coach IA', desc:'Pregunta lo que quieras', route:'chat.index', icon:'message' },
                { label:'Postura', desc:'Analiza tu forma', route:'posture.index', icon:'camera' },
                { label:'Progreso', desc:'Registra tu evolución', route:'progress.index', icon:'chart' },
                { label:'Ejercicios', desc:'Ver todos', route:'exercises.index', icon:'dumbbell' },
              ]" :key="action.route" :href="route(action.route)"
                class="flex flex-col gap-2 transition-all group"
                style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:20px;text-decoration:none;"
              >
                <div class="flex items-center justify-center flex-shrink-0" style="width:44px;height:44px;border-radius:12px;background:rgba(29,244,18,0.08);color:#1DF412;margin-bottom:4px;">
                  <svg v-if="action.icon === 'message'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                  <svg v-if="action.icon === 'camera'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                  <svg v-if="action.icon === 'chart'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                  <svg v-if="action.icon === 'dumbbell'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
                </div>
                <span class="font-display font-bold" style="font-size:16px;color:#fff;letter-spacing:-0.01em;">{{ action.label }}</span>
                <span style="font-size:12px;color:#9CA3AF;">{{ action.desc }}</span>
              </Link>
            </div>
          </div>

          <!-- Right column -->
          <div class="flex flex-col gap-4">

            <!-- Streak card -->
            <div class="relative overflow-hidden" style="background:linear-gradient(135deg,#161616 0%,#0D0D0D 100%);border:1px solid rgba(29,244,18,0.2);border-radius:18px;padding:20px;">
              <div class="absolute rounded-full" style="top:-20px;right:-20px;width:120px;height:120px;background:radial-gradient(circle,rgba(29,244,18,0.15) 0%,transparent 70%);filter:blur(20px);"></div>
              <div class="relative flex justify-between items-start" style="margin-bottom:16px;">
                <div>
                  <div class="flex items-center gap-1" style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:6px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" style="color:#1DF412;"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                    RACHA ACTIVA
                  </div>
                  <div class="flex items-baseline gap-2">
                    <span class="font-display font-bold" style="font-size:40px;letter-spacing:-0.03em;line-height:1;">{{ streak }}</span>
                    <span style="font-size:14px;color:#9CA3AF;font-weight:500;">días</span>
                  </div>
                </div>
                <div class="flex items-center justify-center" style="width:44px;height:44px;border-radius:12px;background:#1DF412;color:#000;box-shadow:0 0 20px rgba(29,244,18,0.5);">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                </div>
              </div>
              <div class="relative flex gap-1">
                <div v-for="(active, i) in weekDays" :key="i" class="flex-1 text-center">
                  <div class="flex items-center justify-center" style="width:100%;height:28px;border-radius:6px;margin-bottom:4px;"
                    :style="active ? 'background:#1DF412;' : 'background:#242424;opacity:0.5;'">
                    <svg v-if="active" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                  <div style="font-size:10px;color:#9CA3AF;font-weight:500;">{{ dayLabels[i] }}</div>
                </div>
              </div>
            </div>

            <!-- Profile card -->
            <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:18px;padding:20px;">
              <div class="flex items-center gap-3" style="margin-bottom:16px;">
                <div class="flex items-center justify-center font-bold text-black" style="width:48px;height:48px;border-radius:50%;background:#1DF412;font-size:18px;flex-shrink:0;">
                  {{ user?.name?.charAt(0)?.toUpperCase() }}
                </div>
                <div>
                  <div class="font-semibold" style="font-size:15px;color:#fff;">{{ user?.name }}</div>
                  <div style="font-size:12px;color:#9CA3AF;">{{ roleLabel }}</div>
                </div>
              </div>
              <div class="flex flex-col gap-2">
                <div v-for="item in [
                  { label:'Nivel', value: user?.level ? { beginner:'Principiante', intermediate:'Intermedio', advanced:'Avanzado' }[user.level] ?? user.level : '—' },
                  { label:'Objetivo', value: user?.goal ? { fat_loss:'Perder grasa', muscle_gain:'Ganar músculo', strength:'Fuerza', maintain:'Mantener', flexibility:'Flexibilidad', cardio:'Resistencia', body_recomposition:'Recomposición' }[user.goal] ?? user.goal : '—' },
                ]" :key="item.label"
                  class="flex justify-between items-center" style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                  <span style="font-size:13px;color:#9CA3AF;">{{ item.label }}</span>
                  <span style="font-size:13px;color:#fff;font-weight:600;">{{ item.value }}</span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>
</template>
