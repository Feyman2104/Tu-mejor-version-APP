<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import LogoSVG from '@/Components/LogoSVG.vue'
import type { User } from '@/types'

defineOptions({ layout: GuestLayout })

const page = usePage()
const user = computed(() => page.props.auth.user as User)

// ─── Paneles (gradientes CSS por fase, sin dependencias externas) ─────────────
const STEP_PANELS = [
  { bg: `radial-gradient(ellipse 90% 60% at 15% 85%, rgba(29,244,18,0.22) 0%, transparent 55%), radial-gradient(ellipse 60% 40% at 80% 15%, rgba(29,244,18,0.10) 0%, transparent 50%), #020302` },
  { bg: `radial-gradient(ellipse 70% 70% at 70% 30%, rgba(59,130,246,0.22) 0%, transparent 60%), radial-gradient(ellipse 50% 40% at 15% 75%, rgba(29,244,18,0.10) 0%, transparent 50%), #020308` },
  { bg: `radial-gradient(ellipse 80% 55% at 50% 100%, rgba(245,158,11,0.25) 0%, transparent 60%), radial-gradient(ellipse 40% 30% at 75% 20%, rgba(245,158,11,0.12) 0%, transparent 45%), #050400` },
  { bg: `radial-gradient(ellipse 65% 75% at 30% 50%, rgba(139,92,246,0.22) 0%, transparent 58%), radial-gradient(ellipse 50% 35% at 85% 85%, rgba(29,244,18,0.10) 0%, transparent 50%), #040306` },
  { bg: `radial-gradient(ellipse 75% 65% at 55% 40%, rgba(29,244,18,0.28) 0%, transparent 62%), radial-gradient(ellipse 45% 35% at 10% 85%, rgba(29,244,18,0.15) 0%, transparent 50%), #020402` },
]
const currentPanel = computed(() => STEP_PANELS[step.value % STEP_PANELS.length])

// ─── Meta por paso ────────────────────────────────────────────────────────────
const STEP_META = [
  { eyebrow: 'Bienvenida',          title: 'Construye tu mejor versión.',        sub: 'Un plan científico y personalizado con IA en menos de 3 minutos.' },
  { eyebrow: 'Datos personales',    title: 'Cuéntanos sobre ti.',                sub: 'Estos datos permiten a la IA calibrar la intensidad correcta para ti.' },
  { eyebrow: 'Actividad',           title: '¿Cómo es tu día a día?',             sub: 'No hablamos de flexibilidad — sino de cuánto te mueves en tu rutina habitual.' },
  { eyebrow: 'Experiencia',         title: '¿Tienes experiencia?',               sub: 'El sistema adapta la fase de entrada a tu punto de partida real.' },
  { eyebrow: 'Nivel',               title: '¿Cuál es tu nivel?',                 sub: 'El sistema escala la dificultad automáticamente cada semana.' },
  { eyebrow: 'Objetivo',            title: 'Tu meta define el plan.',            sub: 'Define el norte de tu entrenamiento. Puedes cambiarlo cuando quieras.' },
  { eyebrow: 'Lugar',               title: '¿Dónde entrenas?',                   sub: 'Tu plan se adapta al espacio y equipamiento disponible.' },
  { eyebrow: 'Equipamiento',        title: 'Entrenamos con lo que tienes.',      sub: 'Tu plan usa solo el equipamiento que tengas disponible.' },
  { eyebrow: 'Salud',               title: 'Adaptamos sin riesgos.',             sub: 'Conocer tus limitaciones nos permite evitar molestias en cada rutina.' },
  { eyebrow: 'Disponibilidad',      title: '¿Cuánto tiempo tienes?',             sub: 'Ajustamos el volumen a los días y minutos que puedas dedicar.' },
  { eyebrow: 'Tus días de entreno', title: '¿Qué días\nentrenas?',               sub: 'Los días elegidos definen cuándo aparece tu rutina en el dashboard.' },
  { eyebrow: 'Tipo de split',       title: '¿Cómo distribuyes tus días?',        sub: 'Cada organización tiene pros y contras. Elige la que mejor encaje en tu semana.' },
  { eyebrow: 'Preferencias',        title: '¿Qué quieres trabajar más?',         sub: 'El plan da prioridad a los grupos musculares que elijas.' },
  { eyebrow: 'Resumen',             title: 'Todo listo.',                        sub: 'Revisa tu perfil y genera tu rutina personalizada con IA.' },
]

// ─── Labels ───────────────────────────────────────────────────────────────────
const GOAL_LABELS: Record<string, string> = {
  muscle_gain: 'Ganar músculo', fat_loss: 'Perder grasa', strength: 'Aumentar fuerza',
  body_recomposition: 'Recomposición corporal', maintain: 'Mantener forma',
  flexibility: 'Flexibilidad', cardio: 'Resistencia cardiovascular',
}
const GOAL_ICONS: Record<string, string> = {
  muscle_gain: '💪', fat_loss: '🔥', strength: '🏋️', body_recomposition: '⚖️',
  maintain: '🎯', flexibility: '🧘', cardio: '❤️',
}
const LEVEL_OPTS = [
  { id: 'beginner',     label: 'Principiante', desc: '0 a 6 meses de entrenamiento' },
  { id: 'intermediate', label: 'Intermedio',   desc: 'De 6 meses a 2 años entrenando' },
  { id: 'advanced',     label: 'Avanzado',     desc: 'Más de 2 años de entrenamiento consistente' },
]
const SPLIT_OPTS = [
  { id: 'auto',             label: 'Recomendado', icon: '✨', desc: 'IA decide el mejor' },
  { id: 'full_body',        label: 'Cuerpo total', icon: '🏋️', desc: 'Todo cada sesión' },
  { id: 'upper_lower',       label: 'Torso-Pierna', icon: '🦿', desc: '2 arriba / 2 abajo' },
  { id: 'ppl',              label: 'Push/Pull/Legs', icon: '💪', desc: '3 grupos' },
  { id: 'weider',           label: 'Dividida',      icon: '📋', desc: 'Grupo por día' },
]
const PLACE_OPTS = [
  { id: 'gym',  label: 'Gimnasio',  icon: '🏟️', desc: 'Tengo acceso a maquinaria, pesos libres y más' },
  { id: 'home', label: 'En casa',   icon: '🏠', desc: 'Entreno en mi propio espacio con lo que tengo' },
  { id: 'both', label: 'Ambos',     icon: '🔄', desc: 'Alterno entre el gimnasio y entrenamientos en casa' },
]
const EQUIPMENT_OPTS = [
  { id: 'none',        label: 'Sin equipamiento' },
  { id: 'dumbbells',   label: 'Mancuernas' },
  { id: 'barbell',     label: 'Barra y discos' },
  { id: 'pull_up_bar', label: 'Barra dominadas' },
  { id: 'cables',      label: 'Poleas' },
  { id: 'machines',    label: 'Máquinas' },
  { id: 'kettlebell',  label: 'Kettlebell' },
  { id: 'bands',       label: 'Bandas elásticas' },
]
const INJURY_ZONES = [
  { id: 'knee',     label: 'Rodilla' },
  { id: 'back',     label: 'Espalda' },
  { id: 'shoulder', label: 'Hombro' },
  { id: 'wrist',    label: 'Muñeca' },
  { id: 'ankle',    label: 'Tobillo' },
  { id: 'neck',     label: 'Cuello' },
  { id: 'hip',      label: 'Cadera' },
]
const INJURY_LABELS: Record<string, string> = Object.fromEntries(INJURY_ZONES.map(z => [z.id, z.label]))
const DURATION_OPTS = [
  { value: 30,  label: '30 min' },
  { value: 45,  label: '45 min' },
  { value: 60,  label: '1 hora' },
  { value: 90,  label: '1.5 h' },
  { value: 120, label: '2 horas' },
]
const MUSCLE_OPTS = [
  { id: 'chest',     label: 'Pecho' },
  { id: 'back',      label: 'Espalda' },
  { id: 'shoulders', label: 'Hombros' },
  { id: 'biceps',    label: 'Bíceps' },
  { id: 'triceps',   label: 'Tríceps' },
  { id: 'legs',      label: 'Piernas' },
  { id: 'glutes',    label: 'Glúteos' },
  { id: 'core',      label: 'Core / Abdomen' },
]
const EQUIPMENT_LABELS: Record<string, string> = Object.fromEntries(EQUIPMENT_OPTS.map(e => [e.id, e.label]))
const MOBILITY_LABELS: Record<string, string> = { good: 'Buena', average: 'Regular', limited: 'Limitada' }
const PLACE_LABELS: Record<string, string> = { gym: 'Gimnasio', home: 'En casa', both: 'Casa y gimnasio' }
const LEVEL_LABELS: Record<string, string> = Object.fromEntries(LEVEL_OPTS.map(l => [l.id, l.label]))
const MUSCLE_LABELS: Record<string, string> = Object.fromEntries(MUSCLE_OPTS.map(m => [m.id, m.label]))
const ACTIVITY_LABELS: Record<string, string> = {
  sedentary: 'Sedentario',
  lightly_active: 'Poco activo',
  active: 'Activo',
  very_active: 'Muy activo',
}

const DAYS = [
  { iso: 1, initial: 'L', name: 'Lunes',      type: 'Entre semana'  },
  { iso: 2, initial: 'M', name: 'Martes',     type: 'Entre semana'  },
  { iso: 3, initial: 'X', name: 'Miércoles',  type: 'Entre semana'  },
  { iso: 4, initial: 'J', name: 'Jueves',     type: 'Entre semana'  },
  { iso: 5, initial: 'V', name: 'Viernes',    type: 'Entre semana'  },
  { iso: 6, initial: 'S', name: 'Sábado',     type: 'Fin de semana' },
  { iso: 7, initial: 'D', name: 'Domingo',    type: 'Fin de semana' },
] as const

const DAYS_LABELS: Record<number, string> = {
  1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves',
  5: 'Viernes', 6: 'Sábado', 7: 'Domingo',
}

const DEFAULT_DAYS_MAP: Record<number, number[]> = {
  1: [3],
  2: [1, 4],
  3: [1, 3, 5],
  4: [1, 2, 4, 5],
  5: [1, 2, 3, 4, 5],
  6: [1, 2, 3, 4, 5, 6],
  7: [1, 2, 3, 4, 5, 6, 7],
}

// ─── Estado ───────────────────────────────────────────────────────────────────
const TOTAL_STEPS = 14
const step = ref(0)
const noInjuries = ref(false)

const form = useForm({
  name:                     (user.value?.name ?? '') as string,
  age:                      null as number | null,
  sex:                      '' as string,
  weight_kg:                null as number | null,
  height_cm:                null as number | null,
  mobility:                 '' as string,
  activity_level:           '' as string,
  level:                    '' as string,
  goal:                     '' as string,
  place:                    '' as string,
  equipment:                [] as string[],
  injuries:                 [] as Array<{ zone: string; notes: string }>,
  days_per_week:            null as number | null,
  session_duration_minutes: null as number | null,
  preferred_muscles:        [] as string[],
  split_type:               'auto' as string,
  training_days:            [] as number[],
  has_trained_before:       null as boolean | null,
  last_trained:             '' as string,
})

// ─── Computed ─────────────────────────────────────────────────────────────────
const canContinue = computed(() => {
  switch (step.value) {
    case 0:  return true
    case 1:  return true
    case 2:  return !!form.activity_level
    case 3:  return form.has_trained_before !== null
    case 4:  return !!form.level
    case 5:  return !!form.goal
    case 6:  return !!form.place
    case 7:  return form.equipment.length > 0
    case 8:  return true
    case 9:  return !!form.days_per_week && !!form.session_duration_minutes
    case 10: return form.training_days.length >= 1
    case 11: return true
    case 12: return true
    case 13: return true
    default: return false
  }
})

const progressPct = computed(() => ((step.value + 1) / TOTAL_STEPS) * 100)

const defaultDays = computed<number[]>(() =>
  DEFAULT_DAYS_MAP[form.days_per_week ?? 3] ?? [1, 3, 5]
)

const summaryDays = computed(() =>
  form.training_days.map(d => DAYS_LABELS[d]).join(' · ') || '—'
)

const firstName = computed(() => (user.value?.name ?? form.name)?.split(' ')[0] || 'campeón')

const ctaLabel = computed(() => {
  if (step.value === 0)                return 'Empezar'
  if (step.value === TOTAL_STEPS - 1)  return form.processing ? 'Generando rutina...' : 'Generar mi rutina  →'
  return 'Continuar'
})

// ─── Navegación ───────────────────────────────────────────────────────────────
function goNext(): void {
  if (!canContinue.value) return
  if (step.value < TOTAL_STEPS - 1) {
    step.value++
  } else {
    submit()
  }
}

function goBack(): void {
  if (step.value > 0) step.value--
}

function submit(): void {
  localStorage.removeItem('onboarding_draft')
  form.post(route('onboarding.store'))
}

// ─── Persistencia localStorage ────────────────────────────────────────────────
const LS_KEY = 'onboarding_draft'

onMounted(() => {
  try {
    const raw = localStorage.getItem(LS_KEY)
    if (!raw) return
    const draft = JSON.parse(raw) as Record<string, unknown>
    if (typeof draft.step === 'number') step.value = Math.min(Math.max(draft.step, 0), TOTAL_STEPS - 1)
    const fields: (keyof typeof form)[] = [
      'name','age','sex','weight_kg','height_cm','mobility','level','goal','place',
      'equipment','injuries','days_per_week','session_duration_minutes','preferred_muscles',
      'training_days',
    ]
    for (const f of fields) {
      if (draft[f] !== undefined) (form as Record<string, unknown>)[f] = draft[f]
    }
  } catch { /* ignore corrupt drafts */ }
  _mounting = false
})

watch(
  () => ({
    step: step.value,
    name: form.name, age: form.age, sex: form.sex, weight_kg: form.weight_kg, height_cm: form.height_cm,
    mobility: form.mobility,
    activity_level: form.activity_level, level: form.level, goal: form.goal, place: form.place,
    equipment: form.equipment, injuries: form.injuries,
    days_per_week: form.days_per_week, session_duration_minutes: form.session_duration_minutes,
    preferred_muscles: form.preferred_muscles, split_type: form.split_type,
    has_trained_before: form.has_trained_before, last_trained: form.last_trained,
    training_days: form.training_days,
  }),
  (val) => {
    try { localStorage.setItem(LS_KEY, JSON.stringify(val)) } catch { /* quota */ }
  },
  { deep: true },
)

watch(() => step.value, (newStep) => {
  if (newStep === 10 && form.training_days.length === 0) {
    form.training_days = [...defaultDays.value]
  }
})

// Reset training_days when user changes days_per_week before reaching step 10
// _mounting suppresses this watch during the onMounted localStorage restore
let _mounting = true
watch(() => form.days_per_week, () => {
  if (!_mounting && step.value < 10) form.training_days = []
})

// ─── Toggles ──────────────────────────────────────────────────────────────────
function toggleEquipment(id: string): void {
  if (id === 'none') {
    form.equipment = form.equipment.includes('none') ? [] : ['none']
    return
  }
  const next = form.equipment.filter(e => e !== 'none')
  form.equipment = next.includes(id) ? next.filter(e => e !== id) : [...next, id]
}

function toggleInjuryZone(zone: string): void {
  noInjuries.value = false
  const idx = form.injuries.findIndex(i => i.zone === zone)
  if (idx >= 0) {
    form.injuries.splice(idx, 1)
  } else {
    form.injuries.push({ zone, notes: '' })
  }
}

function selectNoInjuries(): void {
  noInjuries.value = !noInjuries.value
  if (noInjuries.value) form.injuries = []
}

function updateInjuryNotes(zone: string, value: string): void {
  const inj = form.injuries.find(i => i.zone === zone)
  if (inj) inj.notes = value
}

function toggleMuscle(id: string): void {
  if (form.preferred_muscles.includes(id)) {
    form.preferred_muscles = form.preferred_muscles.filter(m => m !== id)
  } else {
    form.preferred_muscles = [...form.preferred_muscles, id]
  }
}

function toggleDay(iso: number): void {
  const idx = form.training_days.indexOf(iso)
  if (idx === -1) {
    form.training_days.push(iso)
    form.training_days.sort((a, b) => a - b)
  } else {
    form.training_days.splice(idx, 1)
  }
  form.days_per_week = form.training_days.length
}

function toNum(e: Event): number | null {
  const v = (e.target as HTMLInputElement).valueAsNumber
  return isNaN(v) || v <= 0 ? null : v
}
</script>

<template>
  <div class="relative min-h-screen font-sans" style="background:#000;color:#fff;">

    <!-- Glow orbs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
      <div class="absolute rounded-full" style="top:-10%;left:-20%;width:380px;height:380px;background:radial-gradient(circle,rgba(29,244,18,0.18) 0%,transparent 65%);filter:blur(40px);"></div>
      <div class="absolute rounded-full" style="bottom:-5%;right:-20%;width:340px;height:340px;background:radial-gradient(circle,rgba(29,244,18,0.15) 0%,transparent 65%);filter:blur(40px);"></div>
    </div>

    <!-- ── Animación "Creando rutina" ── -->
    <Teleport to="body">
      <Transition name="fade-overlay">
        <div v-if="form.processing"
          class="fixed inset-0 flex flex-col items-center justify-center z-[9999]"
          style="background:#000;">
          <div class="relative" style="width:80px;height:80px;margin-bottom:32px;">
            <img
              src="/icon-main.svg"
              alt="Tu Mejor Versión"
              style="width:80px;height:80px;object-contain;animation:logoZoomOut 1.4s cubic-bezier(0.4,0,0.2,1) forwards;"
            />
          </div>
          <h2 class="font-display font-black text-center mb-3"
            style="font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:900;text-transform:uppercase;letter-spacing:-0.01em;color:#fff;">
            Creando tu rutina
          </h2>
          <p style="font-size:14px;color:#6B7280;text-align:center;max-width:220px;line-height:1.6;">
            La IA está diseñando tu plan personalizado de entrenamiento
          </p>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Layout principal ── -->
    <div class="relative z-10">

      <!-- ── Panel imagen: h-260px móvil, fixed 40% escritorio ── -->
      <div class="relative overflow-hidden h-[260px] md:fixed md:inset-y-0 md:left-0 md:w-[40%] md:h-screen">
        <div class="absolute inset-0 transition-all duration-700" :style="{ background: currentPanel.bg }"></div>
        <svg class="absolute inset-0 w-full h-full pointer-events-none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" style="opacity:0.035;">
          <defs><pattern id="onb-grid" width="48" height="48" patternUnits="userSpaceOnUse"><path d="M 48 0 L 0 0 0 48" fill="none" stroke="white" stroke-width="0.6"/></pattern></defs>
          <rect width="100%" height="100%" fill="url(#onb-grid)"/>
        </svg>
        <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(0,0,0,0.25) 0%,rgba(0,0,0,0.05) 40%,rgba(0,0,0,0.92) 100%);"></div>

        <!-- ── Móvil: barra superior ── -->
        <div class="absolute inset-x-0 top-0 flex items-center gap-3 md:hidden" style="padding:20px;">
          <button @click="goBack" :disabled="step === 0"
            class="flex items-center justify-center flex-shrink-0"
            style="width:40px;height:40px;border-radius:10px;background:rgba(0,0,0,0.5);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.06);color:#fff;cursor:pointer;"
            :style="step === 0 ? 'opacity:0.3;cursor:not-allowed;' : ''">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          </button>
          <div class="flex-1 overflow-hidden" style="height:4px;background:rgba(255,255,255,0.1);border-radius:2px;">
            <div style="height:100%;background:#1DF412;border-radius:2px;box-shadow:0 0 12px #1DF412;transition:width 0.4s cubic-bezier(0.4,0,0.2,1);" :style="{ width: progressPct + '%' }"></div>
          </div>
          <div style="font-size:12px;color:#fff;font-weight:600;background:rgba(0,0,0,0.5);backdrop-filter:blur(12px);padding:8px 10px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);">
            {{ step + 1 }}/{{ TOTAL_STEPS }}
          </div>
        </div>

        <!-- ── Móvil: eyebrow ── -->
        <div class="absolute bottom-5 left-5 md:hidden">
          <div class="inline-flex items-center gap-2" style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.12em;">
            <span class="rounded-full" style="width:5px;height:5px;background:#1DF412;display:inline-block;"></span>
            {{ STEP_META[step].eyebrow }}
          </div>
        </div>

        <!-- ── Escritorio: logo + meta + indicadores (superpuesto sobre la imagen) ── -->
        <div class="absolute inset-0 hidden md:flex flex-col" style="padding:48px;pointer-events:none;">
          <!-- Logo -->
          <div>
            <LogoSVG variant="navbar" size="medium" />
          </div>

          <!-- Meta central -->
          <div class="flex-1 flex flex-col justify-center">
            <div class="flex items-center gap-2 mb-5" style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.15em;">
              <span class="rounded-full" style="width:6px;height:6px;background:#1DF412;display:inline-block;"></span>
              Paso {{ step + 1 }} de {{ TOTAL_STEPS }} · {{ STEP_META[step].eyebrow }}
            </div>
            <h3 class="font-black" style="font-family:'Barlow Condensed',sans-serif;font-size:38px;font-weight:900;line-height:1.1;letter-spacing:-0.025em;color:#fff;margin-bottom:16px;text-shadow:0 2px 16px rgba(0,0,0,0.6);">
              {{ STEP_META[step].title }}
            </h3>
            <p style="font-size:15px;color:rgba(255,255,255,0.7);line-height:1.6;max-width:360px;text-shadow:0 1px 4px rgba(0,0,0,0.5);">
              {{ STEP_META[step].sub }}
            </p>
          </div>

          <!-- Indicadores de paso -->
          <div>
            <div class="flex gap-1 mb-3">
              <div v-for="i in TOTAL_STEPS" :key="i" class="flex-1" style="height:3px;border-radius:2px;transition:all 0.35s;"
                :style="i - 1 <= step ? 'background:#1DF412;box-shadow:0 0 8px #1DF412;' : 'background:rgba(255,255,255,0.12);'"></div>
            </div>
            <div class="flex justify-between" style="font-size:12px;color:rgba(255,255,255,0.5);font-weight:500;">
              <span>{{ STEP_META[step].eyebrow }}</span>
              <span>{{ Math.round(progressPct) }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Panel de contenido ── -->
      <div class="flex flex-col min-h-screen md:ml-[40%]">

        <!-- ── Escritorio: barra superior ── -->
        <div class="hidden md:flex justify-between items-center" style="padding:32px 64px;">
          <button @click="goBack" :disabled="step === 0"
            class="flex items-center gap-2"
            style="background:transparent;border:none;font-size:14px;font-weight:500;padding:8px;cursor:pointer;"
            :style="step === 0 ? 'color:#6B7280;opacity:0.4;cursor:not-allowed;' : 'color:#9CA3AF;'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Atrás
          </button>
          <div style="font-size:13px;color:#9CA3AF;">
            Hola, <strong style="color:#fff;">{{ firstName }}</strong> 👋
          </div>
        </div>

        <!-- ── Contenido del paso ── -->
        <div class="flex-1 md:flex md:flex-col md:justify-center px-5 pt-6 pb-40 md:px-0 md:pt-8 md:pb-8"
          style="max-width:540px;margin:0 auto;width:100%;">
          <div>

            <!-- Título del paso (móvil) -->
            <div class="md:hidden mb-6">
              <h2 class="font-black" style="font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:900;line-height:1.1;letter-spacing:-0.01em;color:#fff;margin-bottom:6px;">
                {{ STEP_META[step].title }}
              </h2>
              <p style="font-size:13px;color:#9CA3AF;line-height:1.5;">{{ STEP_META[step].sub }}</p>
            </div>

            <!-- ═══ PASO 0: Bienvenida ═══ -->
            <template v-if="step === 0">
              <div class="flex flex-col gap-2">
                <div v-for="item in [
                  'Análisis de tu perfil físico completo',
                  'Plan de entrenamiento generado por IA',
                  'Adaptado a tu nivel, objetivo y lesiones',
                  'Ajuste automático de dificultad semana a semana',
                ]" :key="item"
                  class="flex items-center gap-3" style="padding:14px 16px;background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;font-size:14px;color:#fff;font-weight:500;">
                  <div style="width:28px;height:28px;border-radius:8px;background:rgba(29,244,18,0.1);flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                  {{ item }}
                </div>
              </div>
            </template>

            <!-- ═══ PASO 1: Datos personales ═══ -->
            <template v-if="step === 1">
              <div class="flex flex-col gap-3">

                <!-- Nombre -->
                <div>
                  <label style="font-size:13px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">Nombre</label>
                  <input v-model="form.name" type="text" placeholder="Tu nombre completo"
                    style="width:100%;background:#161616;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:14px 16px;font-size:15px;color:#fff;outline:none;caret-color:#1DF412;box-sizing:border-box;"
                    @focus="($event.target as HTMLInputElement).style.borderColor='rgba(29,244,18,0.4)'"
                    @blur="($event.target as HTMLInputElement).style.borderColor='rgba(255,255,255,0.08)'"
                  />
                </div>

                <!-- Sexo biológico -->
                <div>
                  <label style="font-size:13px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:8px;">Sexo biológico</label>
                  <div class="grid grid-cols-3 gap-2">
                    <button v-for="opt in [
                      { id:'male',   label:'Hombre', icon:'♂️' },
                      { id:'female', label:'Mujer',  icon:'♀️' },
                      { id:'other',  label:'Otro',   icon:'⚧️' },
                    ]" :key="opt.id"
                      @click="form.sex = opt.id"
                      type="button"
                      style="border-radius:12px;padding:14px 8px;cursor:pointer;border:1.5px solid;transition:all 0.15s;text-align:center;"
                      :style="form.sex === opt.id
                        ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;'
                        : 'background:#161616;border-color:rgba(255,255,255,0.08);'">
                      <div style="font-size:20px;margin-bottom:4px;">{{ opt.icon }}</div>
                      <div style="font-size:13px;font-weight:600;" :style="form.sex === opt.id ? 'color:#1DF412;' : 'color:#9CA3AF;'">{{ opt.label }}</div>
                    </button>
                  </div>
                  <p style="font-size:11px;color:#6B7280;margin-top:5px;">Usado para calcular tu metabolismo basal con precisión</p>
                </div>

                <!-- Edad / Peso / Altura -->
                <div class="grid grid-cols-3 gap-2">
                  <div>
                    <label style="font-size:13px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">Edad</label>
                    <input type="number" min="10" max="100" placeholder="—"
                      :value="form.age ?? ''"
                      @change="form.age = toNum($event)"
                      style="width:100%;background:#161616;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:14px 12px;font-size:15px;color:#fff;outline:none;caret-color:#1DF412;box-sizing:border-box;text-align:center;"
                      @focus="($event.target as HTMLInputElement).style.borderColor='rgba(29,244,18,0.4)'"
                      @blur="($event.target as HTMLInputElement).style.borderColor='rgba(255,255,255,0.08)'"
                    />
                    <div style="font-size:12px;color:#6B7280;text-align:center;margin-top:3px;">años</div>
                  </div>
                  <div>
                    <label style="font-size:13px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">Peso</label>
                    <input type="number" min="20" max="300" step="0.5" placeholder="—"
                      :value="form.weight_kg ?? ''"
                      @change="form.weight_kg = toNum($event)"
                      style="width:100%;background:#161616;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:14px 12px;font-size:15px;color:#fff;outline:none;caret-color:#1DF412;box-sizing:border-box;text-align:center;"
                      @focus="($event.target as HTMLInputElement).style.borderColor='rgba(29,244,18,0.4)'"
                      @blur="($event.target as HTMLInputElement).style.borderColor='rgba(255,255,255,0.08)'"
                    />
                    <div style="font-size:12px;color:#6B7280;text-align:center;margin-top:3px;">kg</div>
                  </div>
                  <div>
                    <label style="font-size:13px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">Altura</label>
                    <input type="number" min="100" max="250" placeholder="—"
                      :value="form.height_cm ?? ''"
                      @change="form.height_cm = toNum($event)"
                      style="width:100%;background:#161616;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:14px 12px;font-size:15px;color:#fff;outline:none;caret-color:#1DF412;box-sizing:border-box;text-align:center;"
                      @focus="($event.target as HTMLInputElement).style.borderColor='rgba(29,244,18,0.4)'"
                      @blur="($event.target as HTMLInputElement).style.borderColor='rgba(255,255,255,0.08)'"
                    />
                    <div style="font-size:12px;color:#6B7280;text-align:center;margin-top:3px;">cm</div>
                  </div>
                </div>

                <!-- Movilidad -->
                <div>
                  <label style="font-size:13px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:8px;">Movilidad articular general</label>
                  <div class="grid grid-cols-3 gap-2">
                    <button v-for="opt in [{id:'good',label:'Buena',icon:'✅'},{id:'average',label:'Regular',icon:'⚠️'},{id:'limited',label:'Limitada',icon:'🔴'}]"
                      :key="opt.id" @click="form.mobility = opt.id"
                      style="border-radius:12px;padding:12px 8px;cursor:pointer;border:1.5px solid;transition:all 0.15s;text-align:center;"
                      :style="form.mobility === opt.id ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;' : 'background:#161616;border-color:rgba(255,255,255,0.08);'">
                      <div style="font-size:18px;margin-bottom:4px;">{{ opt.icon }}</div>
                      <div style="font-size:12px;font-weight:600;" :style="form.mobility === opt.id ? 'color:#1DF412;' : 'color:#9CA3AF;'">{{ opt.label }}</div>
                    </button>
                  </div>
                </div>

                <p style="font-size:12px;color:#6B7280;text-align:center;margin-top:4px;">Todos los campos son opcionales</p>
              </div>
            </template>

            <!-- ═══ PASO 2: Actividad diaria ═══ -->
            <template v-if="step === 2">
              <div class="flex flex-col gap-3">
                <button v-for="opt in [
                  { id:'sedentary',       label:'Sedentario',       icon:'🪑', desc:'Paso la mayor parte del día sentado/a (oficina, universidad).' },
                  { id:'lightly_active',  label:'Poco activo',     icon:'🚶', desc:'Me muevo algo durante el día (5k–8k pasos en promedio).' },
                  { id:'active',          label:'Activo',           icon:'🏃', desc:'Paso bastante tiempo de pie o caminando (8k–12k pasos).' },
                  { id:'very_active',     label:'Muy activo',      icon:'💪', desc:'Trabajo físico o hago deporte intenso a diario.' },
                ]" :key="opt.id"
                  @click="form.activity_level = opt.id"
                  class="w-full text-left flex items-center gap-4"
                  style="border-radius:14px;padding:18px 20px;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
                  :style="form.activity_level === opt.id ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;' : 'background:#161616;border-color:rgba(255,255,255,0.06);'">
                  <div class="flex-shrink-0 flex items-center justify-center" style="width:44px;height:44px;border-radius:12px;font-size:22px;transition:all 0.15s;"
                    :style="form.activity_level === opt.id ? 'background:#1DF412;' : 'background:#242424;'">
                    <span :style="form.activity_level === opt.id ? 'filter:grayscale(1);' : ''">{{ opt.icon }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="font-bold" style="font-size:16px;letter-spacing:-0.01em;color:#fff;margin-bottom:2px;">{{ opt.label }}</div>
                    <div style="font-size:12px;color:#9CA3AF;line-height:1.4;">{{ opt.desc }}</div>
                  </div>
                  <div v-if="form.activity_level === opt.id" class="flex-shrink-0 flex items-center justify-center" style="width:24px;height:24px;border-radius:50%;background:#1DF412;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </button>
              </div>
            </template>

            <!-- ═══ PASO 3: Experiencia ═══ -->
            <template v-if="step === 3">
              <div class="flex flex-col gap-4">
                <!-- ¿Entrenaste antes? -->
                <div>
                  <div style="font-size:12px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:10px;">¿Has entrenado antes?</div>
                  <div class="grid grid-cols-2 gap-2">
                    <button
                      @click="form.has_trained_before = true"
                      style="border-radius:12px;padding:16px;cursor:pointer;transition:all 0.15s;border:1.5px solid;text-align:center;font-size:15px;font-weight:600;"
                      :style="form.has_trained_before === true ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;color:#1DF412;' : 'background:#161616;border-color:rgba(255,255,255,0.06);color:#9CA3AF;'">
                      Sí
                    </button>
                    <button
                      @click="form.has_trained_before = false"
                      style="border-radius:12px;padding:16px;cursor:pointer;transition:all 0.15s;border:1.5px solid;text-align:center;font-size:15px;font-weight:600;"
                      :style="form.has_trained_before === false ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;color:#1DF412;' : 'background:#161616;border-color:rgba(255,255,255,0.06);color:#9CA3AF;'">
                      No, soy nuevo/a
                    </button>
                  </div>
                </div>

                <!-- ¿Cuándo fue el último entrenamiento? -->
                <div v-if="form.has_trained_before">
                  <div style="font-size:12px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:10px;">¿Cuándo fue tu último entrenamiento?</div>
                  <div class="flex flex-col gap-2">
                    <button v-for="opt in [
                      { id:'currently', label:'Estoy entrenando ahora',  icon:'🔄' },
                      { id:'lt_1m',     label:'Hace menos de 1 mes',     icon:'📅' },
                      { id:'1_3m',      label:'Hace 1 a 3 meses',        icon:'📅' },
                      { id:'3_6m',      label:'Hace 3 a 6 meses',        icon:'📅' },
                      { id:'gt_6m',     label:'Hace más de 6 meses',    icon:'📆' },
                    ]" :key="opt.id"
                      @click="form.last_trained = opt.id"
                      class="w-full text-left flex items-center gap-3"
                      style="border-radius:12px;padding:14px 16px;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
                      :style="form.last_trained === opt.id ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;' : 'background:#161616;border-color:rgba(255,255,255,0.06);'">
                      <span style="font-size:18px;">{{ opt.icon }}</span>
                      <span class="font-semibold" style="font-size:14px;letter-spacing:-0.01em;color:#fff;flex:1;">{{ opt.label }}</span>
                      <div v-if="form.last_trained === opt.id" class="flex-shrink-0 flex items-center justify-center" style="width:22px;height:22px;border-radius:50%;background:#1DF412;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Nota informativa -->
                <div v-if="form.has_trained_before === false" style="background:rgba(29,244,18,0.06);border:1px solid rgba(29,244,18,0.2);border-radius:12px;padding:12px 14px;display:flex;gap:10px;align-items:flex-start;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  <p style="font-size:13px;color:#D1FAE5;line-height:1.5;margin:0;">
                    Empezarás con una <strong>fase de adaptación</strong> de 3–4 semanas a menor volumen. Esto prepara tendones y sistema nervioso para evitar lesiones.
                  </p>
                </div>
              </div>
            </template>

            <!-- ═══ PASO 4: Nivel de entrenamiento ═══ -->
            <template v-if="step === 4">
              <div class="flex flex-col gap-3">
                <button v-for="opt in LEVEL_OPTS" :key="opt.id"
                  @click="form.level = opt.id"
                  class="w-full text-left flex items-center gap-4"
                  style="border-radius:14px;padding:18px 20px;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
                  :style="form.level === opt.id ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;' : 'background:#161616;border-color:rgba(255,255,255,0.06);'">
                  <div class="flex-shrink-0 flex items-center justify-center" style="width:44px;height:44px;border-radius:12px;transition:all 0.15s;"
                    :style="form.level === opt.id ? 'background:#1DF412;' : 'background:#242424;'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" :stroke="form.level === opt.id ? '#000' : '#9CA3AF'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M6 15l6-6 6 6"/>
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="font-bold" style="font-size:16px;letter-spacing:-0.01em;color:#fff;margin-bottom:2px;">{{ opt.label }}</div>
                    <div style="font-size:12px;color:#9CA3AF;line-height:1.4;">{{ opt.desc }}</div>
                  </div>
                  <div v-if="form.level === opt.id" class="flex-shrink-0 flex items-center justify-center" style="width:24px;height:24px;border-radius:50%;background:#1DF412;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </button>
              </div>
            </template>

            <!-- ═══ PASO 5: Objetivo ═══ -->
            <template v-if="step === 5">
              <div class="flex flex-col gap-2">
                <button v-for="(label, id) in GOAL_LABELS" :key="id"
                  @click="form.goal = id"
                  class="w-full text-left flex items-center gap-3"
                  style="border-radius:14px;padding:14px 16px;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
                  :style="form.goal === id ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;' : 'background:#161616;border-color:rgba(255,255,255,0.06);'">
                  <div class="flex-shrink-0 flex items-center justify-center" style="width:40px;height:40px;border-radius:10px;font-size:20px;transition:all 0.15s;"
                    :style="form.goal === id ? 'background:#1DF412;' : 'background:#242424;'">
                    <span :style="form.goal === id ? 'filter:grayscale(1);' : ''">{{ GOAL_ICONS[id] }}</span>
                  </div>
                  <span class="font-semibold" style="font-size:15px;letter-spacing:-0.01em;color:#fff;flex:1;">{{ label }}</span>
                  <div v-if="form.goal === id" class="flex-shrink-0 flex items-center justify-center" style="width:24px;height:24px;border-radius:50%;background:#1DF412;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </button>
              </div>
            </template>

            <!-- ═══ PASO 6: Lugar ═══ -->
            <template v-if="step === 6">
              <div class="flex flex-col gap-3">
                <button v-for="opt in PLACE_OPTS" :key="opt.id"
                  @click="form.place = opt.id"
                  class="w-full text-left flex items-center gap-4"
                  style="border-radius:14px;padding:18px 20px;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
                  :style="form.place === opt.id ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;' : 'background:#161616;border-color:rgba(255,255,255,0.06);'">
                  <div class="flex-shrink-0 flex items-center justify-center" style="width:44px;height:44px;border-radius:12px;font-size:22px;transition:all 0.15s;"
                    :style="form.place === opt.id ? 'background:#1DF412;' : 'background:#242424;'">
                    <span :style="form.place === opt.id ? 'filter:grayscale(1);' : ''">{{ opt.icon }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="font-bold" style="font-size:16px;letter-spacing:-0.01em;color:#fff;margin-bottom:2px;">{{ opt.label }}</div>
                    <div style="font-size:12px;color:#9CA3AF;line-height:1.4;">{{ opt.desc }}</div>
                  </div>
                  <div v-if="form.place === opt.id" class="flex-shrink-0 flex items-center justify-center" style="width:24px;height:24px;border-radius:50%;background:#1DF412;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </button>
              </div>
            </template>

            <!-- ═══ PASO 7: Equipamiento ═══ -->
            <template v-if="step === 7">
              <div class="flex flex-wrap gap-2">
                <button v-for="opt in EQUIPMENT_OPTS" :key="opt.id"
                  @click="toggleEquipment(opt.id)"
                  style="border-radius:999px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
                  :style="form.equipment.includes(opt.id)
                    ? 'background:#1DF412;color:#000;border-color:#1DF412;'
                    : 'background:#161616;color:#fff;border-color:rgba(255,255,255,0.06);'">
                  {{ opt.label }}
                </button>
              </div>
            </template>

            <!-- ═══ PASO 8: Lesiones ═══ -->
            <template v-if="step === 8">
              <div style="margin-bottom:16px;">
                <div style="font-size:12px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:10px;">Zonas afectadas</div>
                <div class="flex flex-wrap gap-2">
                  <button v-for="zone in INJURY_ZONES" :key="zone.id"
                    @click="toggleInjuryZone(zone.id)"
                    style="border-radius:999px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
                    :style="form.injuries.some(i => i.zone === zone.id)
                      ? 'background:rgba(239,68,68,0.15);color:#FCA5A5;border-color:rgba(239,68,68,0.4);'
                      : 'background:#161616;color:#fff;border-color:rgba(255,255,255,0.06);'">
                    {{ zone.label }}
                  </button>
                </div>
              </div>

              <div v-if="form.injuries.length" class="flex flex-col gap-3 mb-4">
                <div v-for="inj in form.injuries" :key="inj.zone"
                  style="background:#161616;border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:12px 14px;">
                  <div style="font-size:11px;font-weight:700;color:#FCA5A5;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">
                    {{ INJURY_LABELS[inj.zone] }}
                  </div>
                  <textarea
                    :value="inj.notes"
                    @input="updateInjuryNotes(inj.zone, ($event.target as HTMLTextAreaElement).value)"
                    placeholder="Describe la lesión o limitación (opcional)"
                    rows="2"
                    style="width:100%;background:transparent;border:none;outline:none;color:#E5E7EB;font-size:13px;line-height:1.5;resize:none;font-family:inherit;box-sizing:border-box;"
                  ></textarea>
                </div>
              </div>

              <div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:16px;">
                <button @click="selectNoInjuries()"
                  class="w-full text-left flex items-center gap-3"
                  style="border-radius:14px;padding:14px 16px;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
                  :style="noInjuries ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;' : 'background:#161616;border-color:rgba(255,255,255,0.06);'">
                  <div class="flex-shrink-0 flex items-center justify-center" style="width:36px;height:36px;border-radius:10px;"
                    :style="noInjuries ? 'background:#1DF412;color:#000;' : 'background:#242424;color:#9CA3AF;'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                  <span class="font-semibold" style="font-size:15px;letter-spacing:-0.01em;color:#fff;">Sin lesiones ni limitaciones</span>
                  <div v-if="noInjuries" class="ml-auto flex-shrink-0 flex items-center justify-center" style="width:24px;height:24px;border-radius:50%;background:#1DF412;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </button>
              </div>
            </template>

            <!-- ═══ PASO 9: Disponibilidad ═══ -->
            <template v-if="step === 9">
              <!-- Días por semana -->
              <div style="margin-bottom:24px;">
                <div style="font-size:12px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">Días disponibles por semana</div>
                <div class="flex gap-2 flex-wrap">
                  <button v-for="d in [1,2,3,4,5,6,7]" :key="d"
                    @click="form.days_per_week = d"
                    style="width:48px;height:48px;border-radius:12px;cursor:pointer;font-size:16px;font-weight:700;transition:all 0.15s;border:1.5px solid;display:flex;align-items:center;justify-content:center;"
                    :style="form.days_per_week === d
                      ? 'background:#1DF412;color:#000;border-color:#1DF412;'
                      : 'background:#161616;color:#9CA3AF;border-color:rgba(255,255,255,0.06);'">
                    {{ d }}
                  </button>
                </div>
                <p v-if="form.days_per_week" style="font-size:12px;color:#6B7280;margin-top:8px;">
                  {{ form.days_per_week === 1 ? '1 día a la semana' : `${form.days_per_week} días a la semana` }}
                </p>
              </div>

              <!-- Duración por sesión -->
              <div>
                <div style="font-size:12px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">Duración por sesión</div>
                <div class="flex flex-wrap gap-2">
                  <button v-for="opt in DURATION_OPTS" :key="opt.value"
                    @click="form.session_duration_minutes = opt.value"
                    style="border-radius:12px;padding:12px 18px;cursor:pointer;font-size:14px;font-weight:600;transition:all 0.15s;border:1.5px solid;"
                    :style="form.session_duration_minutes === opt.value
                      ? 'background:#1DF412;color:#000;border-color:#1DF412;'
                      : 'background:#161616;color:#9CA3AF;border-color:rgba(255,255,255,0.06);'">
                    {{ opt.label }}
                  </button>
                </div>
              </div>
            </template>

            <!-- ═══ PASO 10: Días de entreno ═══ -->
            <template v-if="step === 10">
              <div class="flex flex-col gap-2">
                <button
                  v-for="day in DAYS"
                  :key="day.iso"
                  type="button"
                  class="w-full flex items-center justify-between"
                  style="border-radius:14px;padding:14px 16px;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
                  :style="form.training_days.includes(day.iso)
                    ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;'
                    : 'background:#161616;border-color:rgba(255,255,255,0.06);'"
                  @click="toggleDay(day.iso)"
                >
                  <div class="flex items-center gap-3">
                    <div
                      class="flex items-center justify-center flex-shrink-0"
                      style="width:36px;height:36px;border-radius:10px;font-size:15px;font-weight:800;font-family:'Barlow Condensed',sans-serif;transition:all 0.15s;border:1.5px solid;"
                      :style="form.training_days.includes(day.iso)
                        ? 'background:rgba(29,244,18,0.15);border-color:#1DF412;color:#1DF412;'
                        : 'background:#1a1a1a;border-color:#2a2a2a;color:#444;'"
                    >
                      {{ day.initial }}
                    </div>
                    <div>
                      <div
                        class="font-semibold"
                        style="font-size:15px;"
                        :style="form.training_days.includes(day.iso) ? 'color:#fff;' : 'color:#888;'"
                      >{{ day.name }}</div>
                      <div
                        style="font-size:11px;"
                        :style="form.training_days.includes(day.iso) ? 'color:rgba(29,244,18,0.6);' : 'color:#444;'"
                      >{{ day.type }}</div>
                    </div>
                  </div>
                  <div
                    class="flex items-center justify-center flex-shrink-0"
                    style="width:22px;height:22px;border-radius:50%;transition:all 0.15s;"
                    :style="form.training_days.includes(day.iso)
                      ? 'background:#1DF412;border:2px solid #1DF412;'
                      : 'background:#1a1a1a;border:2px solid #2a2a2a;'"
                  >
                    <svg v-if="form.training_days.includes(day.iso)" width="12" height="10" viewBox="0 0 12 10" fill="none">
                      <path d="M1 5L4.5 8.5L11 1.5" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </div>
                </button>
              </div>

              <!-- Resumen reactivo -->
              <div
                v-if="form.training_days.length"
                style="margin-top:16px;background:#0d0d0d;border:1px solid #1a1a1a;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:10px;"
              >
                <div style="width:8px;height:8px;border-radius:50%;background:#1DF412;flex-shrink:0;"></div>
                <div style="font-size:13px;color:#aaa;">
                  <strong style="color:#1DF412;">{{ summaryDays }}</strong>
                  <span> — {{ form.training_days.length }} {{ form.training_days.length === 1 ? 'día' : 'días' }}/semana</span>
                </div>
              </div>
              <p v-else style="font-size:12px;color:#6B7280;margin-top:12px;">
                Selecciona al menos un día para continuar
              </p>
            </template>

            <!-- ═══ PASO 11: Tipo de split ═══ -->
            <template v-if="step === 11">
              <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                <button v-for="opt in SPLIT_OPTS" :key="opt.id"
                  @click="form.split_type = opt.id"
                  style="border-radius:14px;padding:16px 14px;cursor:pointer;transition:all 0.15s;border:1.5px solid;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center;"
                  :style="form.split_type === opt.id ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;' : 'background:#161616;border-color:rgba(255,255,255,0.06);'">
                  <span style="font-size:28px;">{{ opt.icon }}</span>
                  <div>
                    <div class="font-bold" style="font-size:13px;letter-spacing:-0.01em;color:#fff;margin-bottom:2px;">{{ opt.label }}</div>
                    <div style="font-size:10px;color:#9CA3AF;line-height:1.3;">{{ opt.desc }}</div>
                  </div>
                  <div v-if="form.split_type === opt.id" class="flex items-center justify-center" style="width:20px;height:20px;border-radius:50%;background:#1DF412;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </button>
              </div>
            </template>

            <!-- ═══ PASO 12: Preferencias musculares ═══ -->
            <template v-if="step === 12">
              <div class="flex flex-wrap gap-2">
                <button v-for="opt in MUSCLE_OPTS" :key="opt.id"
                  @click="toggleMuscle(opt.id)"
                  style="border-radius:999px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
                  :style="form.preferred_muscles.includes(opt.id)
                    ? 'background:#1DF412;color:#000;border-color:#1DF412;'
                    : 'background:#161616;color:#fff;border-color:rgba(255,255,255,0.06);'">
                  {{ opt.label }}
                </button>
              </div>
              <p style="font-size:12px;color:#6B7280;margin-top:16px;">Selecciona los grupos que quieras priorizar (opcional)</p>
            </template>

            <!-- ═══ PASO 13: Resumen ═══ -->
            <template v-if="step === 13">
              <div class="flex flex-col gap-3">

                <template v-for="row in [
                  { label: 'Nombre',          value: form.name || '—' },
                  { label: 'Sexo',            value: form.sex === 'male' ? 'Hombre' : form.sex === 'female' ? 'Mujer' : form.sex === 'other' ? 'Otro' : '—' },
                  { label: 'Nivel',            value: LEVEL_LABELS[form.level] || '—' },
                  { label: 'Objetivo',         value: GOAL_LABELS[form.goal] || '—' },
                  { label: 'Actividad',        value: ACTIVITY_LABELS[form.activity_level] || '—' },
                  { label: 'Lugar',            value: PLACE_LABELS[form.place] || '—' },
                ]" :key="row.label">
                  <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                    <span style="font-size:13px;color:#6B7280;">{{ row.label }}</span>
                    <span style="font-size:14px;font-weight:600;color:#fff;">{{ row.value }}</span>
                  </div>
                </template>

                <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                  <span style="font-size:13px;color:#6B7280;">Equipamiento</span>
                  <span style="font-size:14px;font-weight:600;color:#fff;text-align:right;max-width:200px;">
                    {{ form.equipment.map(e => EQUIPMENT_LABELS[e]).join(', ') || '—' }}
                  </span>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                  <span style="font-size:13px;color:#6B7280;">Disponibilidad</span>
                  <span style="font-size:14px;font-weight:600;color:#fff;">
                    {{ form.days_per_week ? `${form.days_per_week} días` : '—' }}
                    {{ form.session_duration_minutes ? ` · ${DURATION_OPTS.find(d => d.value === form.session_duration_minutes)?.label}` : '' }}
                  </span>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                  <span style="font-size:13px;color:#6B7280;">Días de entreno</span>
                  <span style="font-size:14px;font-weight:600;color:#1DF412;text-align:right;max-width:240px;">
                    {{ summaryDays }}
                  </span>
                </div>

                <div v-if="form.injuries.length" style="display:flex;justify-content:space-between;align-items:flex-start;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                  <span style="font-size:13px;color:#6B7280;">Lesiones</span>
                  <span style="font-size:14px;font-weight:600;color:#FCA5A5;text-align:right;max-width:200px;">
                    {{ form.injuries.map(i => INJURY_LABELS[i.zone]).join(', ') }}
                  </span>
                </div>

                <div v-if="form.preferred_muscles.length" style="display:flex;justify-content:space-between;align-items:flex-start;padding:12px 0;">
                  <span style="font-size:13px;color:#6B7280;">Prioridad</span>
                  <span style="font-size:14px;font-weight:600;color:#1DF412;text-align:right;max-width:200px;">
                    {{ form.preferred_muscles.map(m => MUSCLE_LABELS[m]).join(', ') }}
                  </span>
                </div>

                <div style="background:rgba(29,244,18,0.06);border:1px solid rgba(29,244,18,0.2);border-radius:14px;padding:14px 16px;margin-top:4px;display:flex;gap:10px;align-items:flex-start;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  <p style="font-size:13px;color:#D1FAE5;line-height:1.5;margin:0;">
                    La IA diseñará tus <strong>{{ form.training_days.length }}</strong> sesiones semanales para {{ summaryDays }}. El dashboard mostrará tu rutina exactamente esos días.
                  </p>
                </div>
              </div>
            </template>

          </div>
        </div>

        <!-- ── CTA (fijo en móvil, estático en escritorio) ── -->
        <div class="fixed bottom-0 left-0 right-0 z-30 md:static md:z-auto md:px-0"
          style="padding:12px 20px 28px;"
          :style="{
            background: 'linear-gradient(to top,rgba(0,0,0,0.98) 30%,rgba(0,0,0,0.85) 75%,transparent 100%)',
          }">
          <div style="max-width:500px;margin:0 auto;">
            <button @click="goNext"
              :disabled="!canContinue || form.processing"
              class="w-full flex items-center justify-center gap-2 font-bold"
              style="border-radius:14px;padding:16px 24px;font-size:16px;cursor:pointer;transition:all 0.2s;border:none;letter-spacing:-0.01em;"
              :style="(!canContinue || form.processing)
                ? 'background:#1A1A1A;color:#6B7280;cursor:not-allowed;'
                : 'background:#1DF412;color:#000;box-shadow:0 4px 24px rgba(29,244,18,0.3);'">
              {{ ctaLabel }}
              <svg v-if="!form.processing" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </div>
        </div>
      </div><!-- /content panel -->
    </div><!-- /layout -->
  </div><!-- /root -->
</template>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes bounce {
  0%, 80%, 100% { transform: scale(0); opacity: 0.3; }
  40% { transform: scale(1); opacity: 1; }
}
@keyframes logoZoomOut {
  0%   { transform: scale(0.4); opacity: 0; }
  40%  { transform: scale(1.1); opacity: 1; }
  70%  { transform: scale(1.0); opacity: 1; }
  100% { transform: scale(1.15); opacity: 0.4; }
}

.fade-overlay-enter-active { transition: opacity 0.3s ease; }
.fade-overlay-leave-active { transition: opacity 0.2s ease; }
.fade-overlay-enter-from,
.fade-overlay-leave-to    { opacity: 0; }

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; }
input[type="number"] { -moz-appearance: textfield; }

textarea { -webkit-tap-highlight-color: transparent; }
</style>
