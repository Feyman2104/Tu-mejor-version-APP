<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import type { User } from '@/types'

defineOptions({ layout: GuestLayout })

const page = usePage()
const user = computed(() => page.props.auth.user as User)

const STEP_IMAGES = [
  'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=900&h=1200&fit=crop&q=85',
  'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&h=1200&fit=crop&q=85',
  'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=900&h=1200&fit=crop&q=85',
  'https://images.unsplash.com/photo-1605296867424-35fc25c9212a?w=900&h=1200&fit=crop&q=85',
  'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=900&h=1200&fit=crop&q=85',
]

const STEP_META = [
  { eyebrow: 'Bienvenida',      title: 'Construye tu mejor versión.',       sub: 'En menos de 2 minutos crearemos tu plan de entrenamiento personalizado con IA.' },
  { eyebrow: 'Nivel de fitness', title: 'Sin trampas, sé honesto.',           sub: 'El sistema escala la dificultad automáticamente cada semana.' },
  { eyebrow: 'Objetivo',        title: 'Tu meta define el plan.',             sub: 'Define el norte de tu plan. Puedes cambiarlo cuando quieras.' },
  { eyebrow: 'Equipamiento',    title: 'Entrenamos con lo que tienes.',       sub: 'Tu plan usa solo el equipamiento que tengas disponible.' },
  { eyebrow: 'Cuidado',         title: 'Adaptamos sin riesgos.',              sub: 'Conocer tus limitaciones nos permite evitar molestias en cada rutina.' },
]

const TOTAL_STEPS = 5
const step = ref(0)

const form = useForm({
  level: '',
  goal: '',
  equipment: [] as string[],
  injuries: [] as string[],
})

const canContinue = computed(() => {
  if (step.value === 0) return true
  if (step.value === 1) return !!form.level
  if (step.value === 2) return !!form.goal
  if (step.value === 3) return form.equipment.length > 0
  if (step.value === 4) return true
  return false
})

function goNext() {
  if (!canContinue.value) return
  if (step.value < TOTAL_STEPS - 1) {
    step.value++
  } else {
    submit()
  }
}

function goBack() {
  if (step.value > 0) step.value--
}

function submit() {
  form.post(route('onboarding.store'))
}

function toggleInjury(id: string) {
  if (id === 'none') {
    form.injuries = form.injuries.includes('none') ? [] : ['none']
    return
  }
  const next = form.injuries.filter(i => i !== 'none')
  if (next.includes(id)) {
    form.injuries = next.filter(i => i !== id)
  } else {
    form.injuries = [...next, id]
  }
}

function toggleEquipment(id: string) {
  if (id === 'none') {
    form.equipment = form.equipment.includes('none') ? [] : ['none']
    return
  }
  const next = form.equipment.filter(e => e !== 'none')
  if (next.includes(id)) {
    form.equipment = next.filter(e => e !== id)
  } else {
    form.equipment = [...next, id]
  }
}

const progressPct = computed(() => ((step.value + 1) / TOTAL_STEPS) * 100)

const firstName = computed(() => user.value?.name?.split(' ')[0] || 'campeón')

const goalLabels: Record<string, string> = {
  fat_loss: 'Perder grasa', muscle_gain: 'Ganar músculo', strength: 'Aumentar fuerza',
  maintain: 'Mantener forma', flexibility: 'Flexibilidad', cardio: 'Resistencia',
}
const levelLabels: Record<string, string> = {
  beginner: 'Principiante', intermediate: 'Intermedio', advanced: 'Avanzado',
}
const equipmentLabels: Record<string, string> = {
  none: 'Sin equipamiento', dumbbells: 'Mancuernas', barbell: 'Barra y discos',
  pull_up_bar: 'Barra de dominadas', cables: 'Poleas', machines: 'Máquinas de gimnasio',
}
</script>

<template>
  <div class="min-h-screen font-sans relative overflow-hidden" style="background:#000;color:#fff;">

    <!-- Glow orbs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
      <div class="absolute rounded-full" style="top:-10%;left:-20%;width:380px;height:380px;background:radial-gradient(circle,rgba(29,244,18,0.2) 0%,transparent 65%);filter:blur(40px);"></div>
      <div class="absolute rounded-full" style="bottom:-5%;right:-25%;width:340px;height:340px;background:radial-gradient(circle,rgba(29,244,18,0.18) 0%,transparent 65%);filter:blur(40px);"></div>
    </div>

    <!-- ========== MOBILE ========== -->
    <div class="md:hidden flex flex-col min-h-screen relative z-10">

      <!-- Image header -->
      <div class="relative overflow-hidden flex-shrink-0" style="height:260px;">
        <img :src="STEP_IMAGES[step]" alt="" class="w-full h-full object-cover transition-opacity duration-300" />
        <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(0,0,0,0.4) 0%,rgba(0,0,0,0.2) 40%,rgba(0,0,0,0.95) 100%);"></div>

        <!-- Top nav -->
        <div class="absolute inset-x-0 top-0 flex items-center gap-3" style="padding:20px;">
          <button
            @click="goBack"
            :disabled="step === 0"
            class="flex items-center justify-center flex-shrink-0"
            style="width:40px;height:40px;border-radius:10px;background:rgba(0,0,0,0.5);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.06);color:#fff;cursor:pointer;"
            :style="step === 0 ? 'opacity:0.4;cursor:not-allowed;' : ''"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          </button>

          <!-- Progress bar -->
          <div class="flex-1 overflow-hidden" style="height:4px;background:rgba(255,255,255,0.1);border-radius:2px;">
            <div style="height:100%;background:#1DF412;border-radius:2px;box-shadow:0 0 12px #1DF412;transition:width 0.4s cubic-bezier(0.4,0,0.2,1);" :style="{ width: progressPct + '%' }"></div>
          </div>

          <div style="font-size:12px;color:#fff;font-weight:600;background:rgba(0,0,0,0.5);backdrop-filter:blur(12px);padding:8px 10px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);">
            {{ step + 1 }}/{{ TOTAL_STEPS }}
          </div>
        </div>

        <!-- Eyebrow -->
        <div class="absolute bottom-5 left-5">
          <div class="inline-flex items-center gap-2" style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.12em;">
            <span class="rounded-full" style="width:5px;height:5px;background:#1DF412;display:inline-block;"></span>
            {{ STEP_META[step].eyebrow }}
          </div>
        </div>
      </div>

      <!-- Step content -->
      <div class="flex-1 px-5 pt-6" style="padding-bottom:140px;">

        <!-- Step 0: Welcome -->
        <template v-if="step === 0">
          <h2 class="font-display font-bold" style="font-size:26px;line-height:1.2;letter-spacing:-0.02em;margin-bottom:10px;">
            Bienvenido a<br><span style="color:#1DF412;">tu mejor versión.</span>
          </h2>
          <p style="font-size:13px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">
            En menos de 2 minutos crearemos tu plan de entrenamiento personalizado con IA.
          </p>
          <div class="flex flex-col gap-2">
            <div v-for="item in [{ text: 'Nivel y objetivo de entrenamiento' }, { text: 'Equipamiento disponible' }, { text: 'Adaptación por lesiones o limitaciones' }]" :key="item.text"
              class="flex items-center gap-3" style="padding:12px 16px;background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;font-size:14px;color:#fff;font-weight:500;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              {{ item.text }}
            </div>
          </div>
        </template>

        <!-- Step 1: Level -->
        <template v-if="step === 1">
          <h2 class="font-display font-bold" style="font-size:26px;line-height:1.2;letter-spacing:-0.02em;margin-bottom:10px;">
            ¿Cuál es<br><span style="color:#1DF412;">tu nivel actual?</span>
          </h2>
          <p style="font-size:13px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">Sé honesto. El sistema escala la dificultad automáticamente.</p>
          <div class="flex flex-col gap-3">
            <button v-for="opt in [
              { id:'beginner',     label:'Principiante', desc:'Nunca he entrenado o llevo menos de 3 meses' },
              { id:'intermediate', label:'Intermedio',   desc:'Entreno hace 3-12 meses con cierta regularidad' },
              { id:'advanced',     label:'Avanzado',     desc:'Más de 1 año entrenando consistentemente' },
            ]" :key="opt.id"
              @click="form.level = opt.id"
              class="w-full text-left flex items-center gap-4 transition-all"
              style="border-radius:14px;padding:18px 20px;cursor:pointer;"
              :style="form.level === opt.id ? 'background:rgba(29,244,18,0.08);border:1.5px solid #1DF412;' : 'background:#161616;border:1.5px solid rgba(255,255,255,0.06);'"
            >
              <div class="flex-shrink-0 flex items-center justify-center" style="width:44px;height:44px;border-radius:10px;transition:all 0.15s;"
                :style="form.level === opt.id ? 'background:#1DF412;color:#000;' : 'background:#242424;color:#9CA3AF;'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-display font-semibold" style="font-size:17px;letter-spacing:-0.01em;color:#fff;margin-bottom:2px;">{{ opt.label }}</div>
                <div style="font-size:12px;color:#9CA3AF;line-height:1.4;">{{ opt.desc }}</div>
              </div>
              <div v-if="form.level === opt.id" class="flex items-center justify-center flex-shrink-0" style="width:24px;height:24px;border-radius:50%;background:#1DF412;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
            </button>
          </div>
        </template>

        <!-- Step 2: Goal -->
        <template v-if="step === 2">
          <h2 class="font-display font-bold" style="font-size:26px;line-height:1.2;letter-spacing:-0.02em;margin-bottom:10px;">
            ¿Cuál es tu<br><span style="color:#1DF412;">objetivo principal?</span>
          </h2>
          <p style="font-size:13px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">Determina la estructura de tu plan. Puedes cambiarlo después.</p>
          <div class="flex flex-col gap-2">
            <button v-for="opt in [
              { id:'muscle_gain', label:'Ganar músculo' },
              { id:'fat_loss',    label:'Perder grasa' },
              { id:'strength',    label:'Aumentar fuerza' },
              { id:'maintain',    label:'Mantener forma' },
              { id:'flexibility', label:'Flexibilidad' },
              { id:'cardio',      label:'Resistencia' },
            ]" :key="opt.id"
              @click="form.goal = opt.id"
              class="w-full text-left flex items-center gap-3 transition-all"
              style="border-radius:14px;padding:14px 16px;cursor:pointer;"
              :style="form.goal === opt.id ? 'background:rgba(29,244,18,0.08);border:1.5px solid #1DF412;' : 'background:#161616;border:1.5px solid rgba(255,255,255,0.06);'"
            >
              <div class="flex-shrink-0 flex items-center justify-center" style="width:36px;height:36px;border-radius:10px;transition:all 0.15s;"
                :style="form.goal === opt.id ? 'background:#1DF412;color:#000;' : 'background:#242424;color:#9CA3AF;'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              </div>
              <span class="font-display font-semibold" style="font-size:15px;letter-spacing:-0.01em;color:#fff;">{{ opt.label }}</span>
              <div v-if="form.goal === opt.id" class="ml-auto flex items-center justify-center flex-shrink-0" style="width:24px;height:24px;border-radius:50%;background:#1DF412;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
            </button>
          </div>
        </template>

        <!-- Step 3: Equipment -->
        <template v-if="step === 3">
          <h2 class="font-display font-bold" style="font-size:26px;line-height:1.2;letter-spacing:-0.02em;margin-bottom:10px;">
            ¿Con qué<br><span style="color:#1DF412;">equipamiento cuentas?</span>
          </h2>
          <p style="font-size:13px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">Selecciona todo lo que tienes disponible.</p>
          <div class="flex flex-wrap gap-2">
            <button v-for="opt in [
              { id:'none',         label:'Sin equipamiento' },
              { id:'dumbbells',    label:'Mancuernas' },
              { id:'barbell',      label:'Barra y discos' },
              { id:'pull_up_bar',  label:'Barra de dominadas' },
              { id:'cables',       label:'Poleas' },
              { id:'machines',     label:'Máquinas de gimnasio' },
            ]" :key="opt.id"
              @click="toggleEquipment(opt.id)"
              style="border-radius:999px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.15s;letter-spacing:-0.01em;border:1.5px solid;"
              :style="form.equipment.includes(opt.id)
                ? 'background:#1DF412;color:#000;border-color:#1DF412;'
                : 'background:#161616;color:#fff;border-color:rgba(255,255,255,0.06);'"
            >
              {{ opt.label }}
            </button>
          </div>
        </template>

        <!-- Step 4: Injuries -->
        <template v-if="step === 4">
          <h2 class="font-display font-bold" style="font-size:26px;line-height:1.2;letter-spacing:-0.02em;margin-bottom:10px;">
            ¿Alguna limitación<br><span style="color:#1DF412;">o lesión?</span>
          </h2>
          <p style="font-size:13px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">Adaptaremos los ejercicios para evitar molestias.</p>
          <div class="flex flex-wrap gap-2 mb-4">
            <button v-for="opt in [
              { id:'knee',     label:'Rodilla' },
              { id:'back',     label:'Espalda' },
              { id:'shoulder', label:'Hombro' },
              { id:'wrist',    label:'Muñeca' },
              { id:'ankle',    label:'Tobillo' },
              { id:'neck',     label:'Cuello' },
              { id:'hip',      label:'Cadera' },
            ]" :key="opt.id"
              @click="toggleInjury(opt.id)"
              style="border-radius:999px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.15s;letter-spacing:-0.01em;border:1.5px solid;"
              :style="form.injuries.includes(opt.id)
                ? 'background:#1DF412;color:#000;border-color:#1DF412;'
                : 'background:#161616;color:#fff;border-color:rgba(255,255,255,0.06);'"
            >
              {{ opt.label }}
            </button>
          </div>
          <div style="padding-top:16px;border-top:1px solid rgba(255,255,255,0.06);">
            <button @click="toggleInjury('none')"
              class="w-full text-left flex items-center gap-3 transition-all"
              style="border-radius:14px;padding:14px 16px;cursor:pointer;"
              :style="form.injuries.includes('none') ? 'background:rgba(29,244,18,0.08);border:1.5px solid #1DF412;' : 'background:#161616;border:1.5px solid rgba(255,255,255,0.06);'"
            >
              <div class="flex-shrink-0 flex items-center justify-center" style="width:36px;height:36px;border-radius:10px;"
                :style="form.injuries.includes('none') ? 'background:#1DF412;color:#000;' : 'background:#242424;color:#9CA3AF;'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
              <span class="font-display font-semibold" style="font-size:15px;letter-spacing:-0.01em;color:#fff;">No tengo limitaciones</span>
              <div v-if="form.injuries.includes('none')" class="ml-auto flex items-center justify-center flex-shrink-0" style="width:24px;height:24px;border-radius:50%;background:#1DF412;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
            </button>
          </div>
        </template>
      </div>

      <!-- Sticky CTA -->
      <div class="fixed bottom-0 left-0 right-0 px-5 pb-7 pt-4 z-20" style="background:linear-gradient(to top,rgba(0,0,0,0.98) 30%,rgba(0,0,0,0.85) 75%,transparent 100%);">
        <button
          @click="goNext"
          :disabled="!canContinue || form.processing"
          class="w-full flex items-center justify-center gap-2 font-bold transition-all"
          style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:16px 24px;font-size:16px;cursor:pointer;letter-spacing:-0.01em;"
          :style="(!canContinue || form.processing) ? 'opacity:0.5;cursor:not-allowed;' : ''"
        >
          {{ step === 0 ? 'Empezar' : step === TOTAL_STEPS - 1 ? (form.processing ? 'Guardando...' : 'Ir al Dashboard') : 'Continuar' }}
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
      </div>
    </div>

    <!-- ========== DESKTOP ========== -->
    <div class="hidden md:flex min-h-screen relative z-10">

      <!-- Left panel -->
      <div class="relative overflow-hidden flex-shrink-0" style="width:40%;">
        <img :src="STEP_IMAGES[step]" alt="" class="w-full h-full object-cover" style="opacity:0.7;transition:opacity 0.3s;" />
        <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(0,0,0,0.6) 0%,rgba(0,0,0,0.4) 40%,rgba(0,0,0,0.85) 100%);"></div>

        <!-- Logo -->
        <div class="absolute" style="top:48px;left:48px;">
          <span class="font-display font-black text-2xl uppercase text-white tracking-tight">
            Tu Mejor<br><span style="color:#1DF412;">Versión</span>
          </span>
        </div>

        <!-- Microcopy center -->
        <div class="absolute" style="top:50%;left:48px;right:48px;transform:translateY(-50%);">
          <div class="flex items-center gap-2" style="font-size:12px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.15em;margin-bottom:20px;">
            <span class="rounded-full" style="width:6px;height:6px;background:#1DF412;display:inline-block;"></span>
            Paso {{ step + 1 }} de {{ TOTAL_STEPS }} · {{ STEP_META[step].eyebrow }}
          </div>
          <h3 class="font-display font-bold" style="font-size:36px;line-height:1.15;letter-spacing:-0.025em;color:#fff;margin-bottom:20px;text-shadow:0 2px 12px rgba(0,0,0,0.5);">
            {{ STEP_META[step].title }}
          </h3>
          <p style="font-size:15px;color:rgba(255,255,255,0.7);line-height:1.6;max-width:380px;text-shadow:0 1px 4px rgba(0,0,0,0.5);">
            {{ STEP_META[step].sub }}
          </p>
        </div>

        <!-- Step pills at bottom -->
        <div class="absolute" style="bottom:48px;left:48px;right:48px;">
          <div class="flex gap-1 mb-4">
            <div v-for="i in TOTAL_STEPS" :key="i" class="flex-1" style="height:3px;border-radius:2px;transition:all 0.3s;"
              :style="i - 1 <= step ? 'background:#1DF412;box-shadow:0 0 12px #1DF412;' : 'background:rgba(255,255,255,0.15);'"></div>
          </div>
          <div class="flex justify-between" style="font-size:12px;color:rgba(255,255,255,0.6);font-weight:500;">
            <span>{{ STEP_META[step].eyebrow }}</span>
            <span>{{ Math.round(((step + 1) / TOTAL_STEPS) * 100) }}%</span>
          </div>
        </div>
      </div>

      <!-- Right panel -->
      <div class="flex-1 flex flex-col relative">

        <!-- Top bar -->
        <div class="flex justify-between items-center" style="padding:32px 64px;">
          <button @click="goBack" :disabled="step === 0" class="flex items-center gap-2 transition-colors"
            style="background:transparent;border:none;font-size:14px;font-weight:500;padding:8px;cursor:pointer;"
            :style="step === 0 ? 'color:#6B7280;opacity:0.4;cursor:not-allowed;' : 'color:#9CA3AF;'"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Atrás
          </button>
          <div style="font-size:13px;color:#9CA3AF;">
            Hola, <strong style="color:#fff;">{{ firstName }}</strong> 👋
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1 flex items-center justify-center" style="padding:20px 64px;">
          <div style="width:100%;max-width:440px;">

            <!-- Step 0: Welcome -->
            <template v-if="step === 0">
              <h2 class="font-display font-bold" style="font-size:40px;line-height:1.1;letter-spacing:-0.02em;margin-bottom:10px;">
                Bienvenido a<br><span style="color:#1DF412;">tu mejor versión.</span>
              </h2>
              <p style="font-size:16px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">En menos de 2 minutos crearemos tu plan personalizado con IA.</p>
              <div class="flex flex-col gap-2">
                <div v-for="item in [
                  { text: 'Nivel y objetivo de entrenamiento' },
                  { text: 'Equipamiento disponible' },
                  { text: 'Adaptación por lesiones o limitaciones' }
                ]" :key="item.text"
                  class="flex items-center gap-3" style="padding:12px 16px;background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;font-size:14px;color:#fff;font-weight:500;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  {{ item.text }}
                </div>
              </div>
            </template>

            <!-- Step 1: Level -->
            <template v-if="step === 1">
              <h2 class="font-display font-bold" style="font-size:40px;line-height:1.1;letter-spacing:-0.02em;margin-bottom:10px;">
                ¿Cuál es<br><span style="color:#1DF412;">tu nivel actual?</span>
              </h2>
              <p style="font-size:16px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">Sé honesto. El sistema escala la dificultad automáticamente.</p>
              <div class="flex flex-col gap-3">
                <button v-for="opt in [
                  { id:'beginner',     label:'Principiante', desc:'Nunca he entrenado o llevo menos de 3 meses' },
                  { id:'intermediate', label:'Intermedio',   desc:'Entreno hace 3-12 meses con cierta regularidad' },
                  { id:'advanced',     label:'Avanzado',     desc:'Más de 1 año entrenando consistentemente' },
                ]" :key="opt.id"
                  @click="form.level = opt.id"
                  class="w-full text-left flex items-center gap-4 transition-all"
                  style="border-radius:14px;padding:18px 20px;cursor:pointer;"
                  :style="form.level === opt.id ? 'background:rgba(29,244,18,0.08);border:1.5px solid #1DF412;' : 'background:#161616;border:1.5px solid rgba(255,255,255,0.06);'"
                >
                  <div class="flex-shrink-0 flex items-center justify-center" style="width:44px;height:44px;border-radius:10px;transition:all 0.15s;"
                    :style="form.level === opt.id ? 'background:#1DF412;color:#000;' : 'background:#242424;color:#9CA3AF;'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="font-display font-semibold" style="font-size:17px;letter-spacing:-0.01em;color:#fff;margin-bottom:2px;">{{ opt.label }}</div>
                    <div style="font-size:12px;color:#9CA3AF;line-height:1.4;">{{ opt.desc }}</div>
                  </div>
                  <div v-if="form.level === opt.id" class="flex items-center justify-center flex-shrink-0" style="width:24px;height:24px;border-radius:50%;background:#1DF412;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </button>
              </div>
            </template>

            <!-- Step 2: Goal -->
            <template v-if="step === 2">
              <h2 class="font-display font-bold" style="font-size:40px;line-height:1.1;letter-spacing:-0.02em;margin-bottom:10px;">
                ¿Cuál es tu<br><span style="color:#1DF412;">objetivo principal?</span>
              </h2>
              <p style="font-size:16px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">Determina la estructura de tu plan.</p>
              <div class="grid gap-2" style="grid-template-columns:repeat(2,1fr);">
                <button v-for="opt in [
                  { id:'muscle_gain', label:'Ganar músculo' },
                  { id:'fat_loss',    label:'Perder grasa' },
                  { id:'strength',    label:'Aumentar fuerza' },
                  { id:'maintain',    label:'Mantener forma' },
                  { id:'flexibility', label:'Flexibilidad' },
                  { id:'cardio',      label:'Resistencia' },
                ]" :key="opt.id"
                  @click="form.goal = opt.id"
                  class="flex items-center gap-3 transition-all"
                  style="border-radius:14px;padding:14px 16px;cursor:pointer;"
                  :style="form.goal === opt.id ? 'background:rgba(29,244,18,0.08);border:1.5px solid #1DF412;' : 'background:#161616;border:1.5px solid rgba(255,255,255,0.06);'"
                >
                  <div class="flex-shrink-0 flex items-center justify-center" style="width:36px;height:36px;border-radius:10px;"
                    :style="form.goal === opt.id ? 'background:#1DF412;color:#000;' : 'background:#242424;color:#9CA3AF;'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  </div>
                  <span class="font-display font-semibold" style="font-size:14px;letter-spacing:-0.01em;color:#fff;">{{ opt.label }}</span>
                </button>
              </div>
            </template>

            <!-- Step 3: Equipment -->
            <template v-if="step === 3">
              <h2 class="font-display font-bold" style="font-size:40px;line-height:1.1;letter-spacing:-0.02em;margin-bottom:10px;">
                ¿Con qué<br><span style="color:#1DF412;">equipamiento cuentas?</span>
              </h2>
              <p style="font-size:16px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">Selecciona todo lo que tienes disponible.</p>
              <div class="flex flex-wrap gap-2">
                <button v-for="opt in [
                  { id:'none',         label:'Sin equipamiento' },
                  { id:'dumbbells',    label:'Mancuernas' },
                  { id:'barbell',      label:'Barra y discos' },
                  { id:'pull_up_bar',  label:'Barra de dominadas' },
                  { id:'cables',       label:'Poleas' },
                  { id:'machines',     label:'Máquinas de gimnasio' },
                ]" :key="opt.id"
                  @click="toggleEquipment(opt.id)"
                  style="border-radius:999px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.15s;letter-spacing:-0.01em;border:1.5px solid;"
                  :style="form.equipment.includes(opt.id)
                    ? 'background:#1DF412;color:#000;border-color:#1DF412;'
                    : 'background:#161616;color:#fff;border-color:rgba(255,255,255,0.06);'"
                >
                  {{ opt.label }}
                </button>
              </div>
            </template>

            <!-- Step 4: Injuries -->
            <template v-if="step === 4">
              <h2 class="font-display font-bold" style="font-size:40px;line-height:1.1;letter-spacing:-0.02em;margin-bottom:10px;">
                ¿Alguna limitación<br><span style="color:#1DF412;">o lesión?</span>
              </h2>
              <p style="font-size:16px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">Adaptaremos los ejercicios para evitar molestias.</p>
              <div class="flex flex-wrap gap-2 mb-4">
                <button v-for="opt in [
                  { id:'knee',     label:'Rodilla' },
                  { id:'back',     label:'Espalda' },
                  { id:'shoulder', label:'Hombro' },
                  { id:'wrist',    label:'Muñeca' },
                  { id:'ankle',    label:'Tobillo' },
                  { id:'neck',     label:'Cuello' },
                  { id:'hip',      label:'Cadera' },
                ]" :key="opt.id"
                  @click="toggleInjury(opt.id)"
                  style="border-radius:999px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.15s;letter-spacing:-0.01em;border:1.5px solid;"
                  :style="form.injuries.includes(opt.id)
                    ? 'background:#1DF412;color:#000;border-color:#1DF412;'
                    : 'background:#161616;color:#fff;border-color:rgba(255,255,255,0.06);'"
                >
                  {{ opt.label }}
                </button>
              </div>
              <div style="padding-top:16px;border-top:1px solid rgba(255,255,255,0.06);">
                <button @click="toggleInjury('none')"
                  class="w-full text-left flex items-center gap-3 transition-all"
                  style="border-radius:14px;padding:14px 16px;cursor:pointer;"
                  :style="form.injuries.includes('none') ? 'background:rgba(29,244,18,0.08);border:1.5px solid #1DF412;' : 'background:#161616;border:1.5px solid rgba(255,255,255,0.06);'"
                >
                  <div class="flex-shrink-0 flex items-center justify-center" style="width:36px;height:36px;border-radius:10px;"
                    :style="form.injuries.includes('none') ? 'background:#1DF412;color:#000;' : 'background:#242424;color:#9CA3AF;'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                  <span class="font-display font-semibold" style="font-size:15px;letter-spacing:-0.01em;color:#fff;">No tengo limitaciones</span>
                  <div v-if="form.injuries.includes('none')" class="ml-auto flex items-center justify-center flex-shrink-0" style="width:24px;height:24px;border-radius:50%;background:#1DF412;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                </button>
              </div>
            </template>

          </div>
        </div>

        <!-- Footer CTA -->
        <div class="flex justify-center" style="padding:24px 64px 48px;">
          <div style="width:100%;max-width:440px;">
            <button
              @click="goNext"
              :disabled="!canContinue || form.processing"
              class="w-full flex items-center justify-center gap-2 font-bold transition-all"
              style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:16px 24px;font-size:16px;cursor:pointer;letter-spacing:-0.01em;"
              :style="(!canContinue || form.processing) ? 'opacity:0.5;cursor:not-allowed;' : ''"
            >
              {{ step === 0 ? 'Empezar' : step === TOTAL_STEPS - 1 ? (form.processing ? 'Guardando...' : 'Ir al Dashboard') : 'Continuar' }}
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>
