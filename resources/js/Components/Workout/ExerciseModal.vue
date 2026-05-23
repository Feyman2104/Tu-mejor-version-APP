<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { Exercise } from '@/types'

const props = defineProps<{
  exercise: Exercise | null
  restSeconds?: number | null
}>()

const emit = defineEmits<{
  close: []
}>()

const page = usePage()
const userGoal = computed(() => page.props.auth?.user?.goal ?? null)

// ─── Cerrar con Escape ────────────────────────────────────────────────────────
function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') emit('close')
}
onMounted(() => {
  document.addEventListener('keydown', onKeydown)
  document.body.style.overflow = 'hidden'
})
onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})

// ─── Recomendación según objetivo ─────────────────────────────────────────────
const GOAL_RECS: Record<string, string> = {
  fat_loss:    'Reduce el descanso a 30-45s entre series y mantén un ritmo constante para maximizar la quema calórica.',
  hypertrophy: 'Trabaja en el rango de 8-12 reps con RPE 7-9. Siente el músculo objetivo en cada repetición.',
  muscle_gain: 'Trabaja en el rango de 8-12 reps con RPE 7-9. Siente el músculo objetivo en cada repetición.',
  strength:    'Usa 3-5 reps con el 80-90% de tu máximo. Descansa 3-5 min para recuperar completamente el sistema neuromuscular.',
  endurance:   'Mantén series largas (15-20+ reps) con descansos cortos (30-45s) para desarrollar resistencia muscular.',
  maintain:    'Mantén cargas moderadas y técnica perfecta. 3 series de 10-12 reps es suficiente para conservar la masa.',
  flexibility: 'Prioriza el rango de movimiento completo sobre el peso levantado. Nunca sacrifiques técnica por carga.',
  cardio:      'Mantén la respiración rítmica y reduce el descanso entre series para elevar la frecuencia cardíaca.',
}

const goalRecommendation = computed(() => {
  if (!userGoal.value) return 'Enfócate en la técnica perfecta en cada repetición antes de aumentar la carga.'
  return GOAL_RECS[userGoal.value] ?? 'Enfócate en la técnica perfecta en cada repetición antes de aumentar la carga.'
})

const GOAL_LABELS: Record<string, string> = {
  fat_loss:    'Pérdida de grasa',
  hypertrophy: 'Hipertrofia',
  muscle_gain: 'Ganar músculo',
  strength:    'Fuerza',
  endurance:   'Resistencia',
  maintain:    'Mantenimiento',
  flexibility: 'Flexibilidad',
  cardio:      'Cardio',
}

const goalLabel = computed(() =>
  userGoal.value ? GOAL_LABELS[userGoal.value] ?? userGoal.value : null
)

// ─── ¿Este ejercicio es bueno para el objetivo del usuario? ──────────────────
const isRecommendedForGoal = computed(() => {
  if (!userGoal.value || !props.exercise?.goal_tags?.length) return false
  return props.exercise.goal_tags.includes(userGoal.value)
})

// ─── Instrucciones como array ─────────────────────────────────────────────────
const instructions = computed(() => {
  const raw = props.exercise?.instructions
  if (!raw) return []
  return Array.isArray(raw) ? raw : []
})

const commonErrors = computed(() => {
  const raw = props.exercise?.common_errors
  if (!raw) return []
  return Array.isArray(raw) ? raw : []
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="exercise"
        style="position:fixed;inset:0;z-index:100;display:flex;align-items:flex-end;justify-content:center;"
        @click.self="emit('close')">

        <!-- Backdrop -->
        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);" @click="emit('close')"/>

        <!-- Sheet -->
        <div style="position:relative;z-index:1;width:100%;max-width:640px;max-height:92dvh;background:#0A0A0A;border-radius:24px 24px 0 0;overflow:hidden;display:flex;flex-direction:column;">

          <!-- Handle -->
          <div style="padding:12px 0 0;display:flex;justify-content:center;flex-shrink:0;">
            <div style="width:36px;height:4px;border-radius:2px;background:#2A2A2A;"/>
          </div>

          <!-- Scrollable content -->
          <div style="overflow-y:auto;flex:1;">

            <!-- Hero image -->
            <div style="position:relative;width:100%;aspect-ratio:16/9;background:#111;overflow:hidden;">
              <img v-if="exercise.gif_url || exercise.thumbnail"
                :src="(exercise.gif_url || exercise.thumbnail)!"
                :alt="exercise.name"
                style="width:100%;height:100%;object-fit:cover;"
                loading="eager"
              />
              <!-- Placeholder si no hay imagen -->
              <div v-else
                style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2A2A2A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m6.5 6.5 11 11"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/>
                  <path d="m3 10 7-7"/><path d="m14 21 7-7"/>
                </svg>
                <span style="font-size:12px;color:#2A2A2A;">Sin imagen disponible</span>
              </div>

              <!-- Gradiente sobre imagen -->
              <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,0.2) 0%,rgba(0,0,0,0.7) 100%);"/>

              <!-- Muscle badge sobre imagen -->
              <div style="position:absolute;bottom:14px;left:16px;display:flex;align-items:center;gap:8px;">
                <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;background:rgba(0,0,0,0.6);color:#9CA3AF;border-radius:6px;padding:4px 8px;backdrop-filter:blur(8px);">
                  {{ exercise.muscle_group }}
                </span>
                <span v-if="isRecommendedForGoal"
                  style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;background:rgba(29,244,18,0.2);color:#1DF412;border-radius:6px;padding:4px 8px;backdrop-filter:blur(8px);">
                  ✓ Para tu objetivo
                </span>
              </div>

              <!-- Botón cerrar -->
              <button @click="emit('close')"
                style="position:absolute;top:12px;right:12px;width:36px;height:36px;border-radius:50%;background:rgba(0,0,0,0.6);border:1px solid rgba(255,255,255,0.1);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>

            <div style="padding:20px 20px 32px;">

              <!-- Nombre -->
              <h2 style="font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:900;text-transform:uppercase;letter-spacing:-0.01em;color:#fff;line-height:1.1;margin-bottom:8px;">
                {{ exercise.name }}
              </h2>

              <!-- Descripción -->
              <p v-if="exercise.description"
                style="font-size:14px;color:#9CA3AF;line-height:1.6;margin-bottom:20px;">
                {{ exercise.description }}
              </p>

              <!-- Recomendación según objetivo ──────────────────────────────── -->
              <div style="background:rgba(29,244,18,0.06);border:1px solid rgba(29,244,18,0.2);border-radius:14px;padding:14px 16px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-start;">
                <div style="width:36px;height:36px;border-radius:10px;background:#1DF412;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                  <div style="font-size:10px;font-weight:700;color:#1DF412;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:4px;">
                    Coach — {{ goalLabel ?? 'Tu objetivo' }}
                  </div>
                  <p style="font-size:13px;color:#D1FAE5;line-height:1.5;margin:0;">
                    {{ goalRecommendation }}
                  </p>
                </div>
              </div>

              <!-- Instrucciones ───────────────────────────────────────────── -->
              <div v-if="instructions.length" style="margin-bottom:20px;">
                <div style="font-size:10px;font-weight:700;color:#4B5563;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:12px;">
                  Cómo ejecutarlo
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                  <div v-for="(step, i) in instructions" :key="i"
                    style="display:flex;gap:12px;align-items:flex-start;">
                    <div style="width:24px;height:24px;border-radius:8px;background:#1DF412;color:#000;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                      {{ i + 1 }}
                    </div>
                    <p style="font-size:14px;color:#E5E7EB;line-height:1.5;margin:0;padding-top:2px;">
                      {{ step }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Errores comunes ─────────────────────────────────────────── -->
              <div v-if="commonErrors.length">
                <div style="font-size:10px;font-weight:700;color:#4B5563;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:12px;">
                  Errores frecuentes
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                  <div v-for="(err, i) in commonErrors" :key="i"
                    style="display:flex;gap:10px;align-items:flex-start;background:#161616;border:1px solid rgba(255,255,255,0.05);border-radius:10px;padding:10px 12px;">
                    <span style="font-size:14px;flex-shrink:0;margin-top:1px;">⚠️</span>
                    <p style="font-size:13px;color:#9CA3AF;line-height:1.5;margin:0;">
                      {{ err }}
                    </p>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active { transition: opacity 0.2s ease; }
.modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }

.modal-enter-active > div:last-child {
  transition: transform 0.25s cubic-bezier(0.32, 0.72, 0, 1);
}
.modal-leave-active > div:last-child {
  transition: transform 0.2s ease-in;
}
.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
  transform: translateY(100%);
}
</style>
