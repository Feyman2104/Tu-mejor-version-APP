<script setup lang="ts">
import { ref, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useMediaPipe, type PoseResults } from '@/composables/useMediaPipe'
import { usePostureFeedback } from '@/composables/usePostureFeedback'

defineOptions({ layout: AppLayout })

const EXERCISES = [
  { id: 'squat',        label: 'Sentadilla' },
  { id: 'pushup',       label: 'Flexión de pecho' },
  { id: 'lunge',        label: 'Zancada' },
  { id: 'glute_bridge', label: 'Puente de glúteo' },
  { id: 'overhead_press', label: 'Press militar' },
  { id: 'bicep_curl',   label: 'Curl de bíceps' },
]

const selectedExercise = ref('squat')
const started = ref(false)

const videoRef = ref<HTMLVideoElement | null>(null)
const canvasRef = ref<HTMLCanvasElement | null>(null)

const { isRunning, errorMessage, start, stop } = useMediaPipe()
const { currentFeedback, score, repCount, processFrame, reset } = usePostureFeedback()

function onResults(results: PoseResults) {
  if (results.poseLandmarks) {
    processFrame(selectedExercise.value, results.poseLandmarks)
  }
}

async function startAnalysis() {
  if (!videoRef.value || !canvasRef.value) return
  reset()
  started.value = true
  await start(videoRef.value, canvasRef.value, onResults)
}

async function stopAnalysis() {
  await stop()
  started.value = false
}

function saveSession() {
  router.post(route('posture.sessions.store'), {
    exercise_slug: selectedExercise.value,
    score: score.value,
    feedback: currentFeedback.value.map(f => f.message),
    duration_sec: 0,
  }, {
    onSuccess: () => stopAnalysis(),
  })
}

onBeforeUnmount(() => stop())

const scoreColor = () => {
  if (score.value >= 80) return '#1DF412'
  if (score.value >= 50) return '#F59E0B'
  return '#EF4444'
}
</script>

<template>
  <div class="min-h-screen relative" style="background:#000;">
    <div class="px-4 md:px-8 py-6 max-w-5xl mx-auto pb-24">

      <!-- Header -->
      <div style="margin-bottom:20px;">
        <div style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:6px;">Analizador con IA</div>
        <h1 class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;">Corrección de Postura</h1>
        <p style="font-size:14px;color:#9CA3AF;">Análisis en tiempo real de tu técnica con MediaPipe</p>
      </div>

      <!-- Exercise selector -->
      <div v-if="!started" style="margin-bottom:20px;">
        <label style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:10px;">Selecciona ejercicio</label>
        <div class="flex flex-wrap gap-2">
          <button v-for="ex in EXERCISES" :key="ex.id"
            @click="selectedExercise = ex.id"
            style="border-radius:999px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
            :style="selectedExercise === ex.id
              ? 'background:#1DF412;color:#000;border-color:#1DF412;'
              : 'background:#161616;color:#fff;border-color:rgba(255,255,255,0.06);'"
          >
            {{ ex.label }}
          </button>
        </div>
      </div>

      <div class="grid gap-6" :style="started ? 'grid-template-columns:1fr;' : ''" :class="started ? 'md:grid-cols-[2fr_1fr]' : ''">

        <!-- Camera view -->
        <div>
          <div class="relative overflow-hidden" style="border-radius:18px;background:#0D0D0D;border:1px solid rgba(255,255,255,0.06);aspect-ratio:4/3;">
            <!-- hidden video, canvas shows the rendered frame -->
            <video ref="videoRef" class="hidden" playsinline></video>
            <canvas ref="canvasRef" class="w-full h-full object-cover" :style="started ? '' : 'display:none;'"></canvas>

            <!-- Placeholder before start -->
            <div v-if="!started" class="absolute inset-0 flex flex-col items-center justify-center text-center px-6">
              <div style="width:72px;height:72px;border-radius:18px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:16px;color:#1DF412;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              </div>
              <h3 class="font-display font-bold" style="font-size:18px;margin-bottom:8px;">Cámara lista</h3>
              <p style="font-size:13px;color:#9CA3AF;line-height:1.5;max-width:300px;">Colócate de perfil a 2–3 metros para que la cámara capte todo tu cuerpo.</p>
            </div>

            <!-- Live score overlay -->
            <div v-if="started && isRunning" class="absolute" style="top:16px;left:16px;background:rgba(0,0,0,0.6);backdrop-filter:blur(12px);border-radius:12px;padding:10px 14px;border:1px solid rgba(255,255,255,0.1);">
              <div style="font-size:10px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:2px;">Puntuación</div>
              <div class="font-display font-bold" style="font-size:28px;line-height:1;" :style="{ color: scoreColor() }">{{ score }}</div>
            </div>
            <div v-if="started && isRunning" class="absolute" style="top:16px;right:16px;background:rgba(0,0,0,0.6);backdrop-filter:blur(12px);border-radius:12px;padding:10px 14px;border:1px solid rgba(255,255,255,0.1);">
              <div style="font-size:10px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:2px;">Reps</div>
              <div class="font-display font-bold" style="font-size:28px;line-height:1;color:#fff;">{{ repCount }}</div>
            </div>
          </div>

          <!-- Error -->
          <div v-if="errorMessage" style="margin-top:12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:12px;padding:14px 16px;font-size:13px;color:#EF4444;">
            {{ errorMessage }}
          </div>

          <!-- Controls -->
          <div class="flex gap-3" style="margin-top:16px;">
            <button v-if="!started" @click="startAnalysis"
              class="flex-1 flex items-center justify-center gap-2 font-bold"
              style="background:#1DF412;color:#000;border:none;border-radius:14px;padding:16px;font-size:16px;cursor:pointer;box-shadow:0 4px 24px rgba(29,244,18,0.3);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="5 3 19 12 5 21 5 3"/></svg>
              Iniciar análisis
            </button>
            <template v-else>
              <button @click="saveSession"
                class="flex-1 flex items-center justify-center gap-2 font-bold"
                style="background:#1DF412;color:#000;border:none;border-radius:14px;padding:16px;font-size:15px;cursor:pointer;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Guardar sesión
              </button>
              <button @click="stopAnalysis"
                class="flex items-center justify-center gap-2 font-bold"
                style="background:#161616;color:#EF4444;border:1.5px solid rgba(239,68,68,0.3);border-radius:14px;padding:16px 24px;font-size:15px;cursor:pointer;">
                Detener
              </button>
            </template>
          </div>
        </div>

        <!-- Feedback panel -->
        <div v-if="started">
          <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:18px;padding:20px;">
            <h3 class="font-display font-bold" style="font-size:17px;margin-bottom:16px;">Feedback en vivo</h3>

            <div v-if="currentFeedback.length === 0" class="text-center py-8">
              <p style="font-size:13px;color:#9CA3AF;">Posiciónate frente a la cámara para empezar a recibir correcciones.</p>
            </div>

            <div v-else class="space-y-3">
              <div v-for="(fb, i) in currentFeedback" :key="i"
                class="flex items-start gap-3" style="padding:12px 14px;border-radius:12px;"
                :style="fb.severity === 'good'
                  ? 'background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);'
                  : fb.severity === 'error'
                    ? 'background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);'
                    : 'background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);'"
              >
                <div class="flex-shrink-0" style="margin-top:1px;"
                  :style="{ color: fb.severity === 'good' ? '#1DF412' : fb.severity === 'error' ? '#EF4444' : '#F59E0B' }">
                  <svg v-if="fb.severity === 'good'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <span style="font-size:13px;line-height:1.5;color:#fff;">{{ fb.message }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
