<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import { useMediaPipe, type PoseResults } from '@/Composables/useMediaPipe'
import { usePostureFeedback, type SessionSummary } from '@/Composables/usePostureFeedback'

const EXERCISES = [
  { id: 'squat',           label: 'Sentadilla' },
  { id: 'pushup',          label: 'Flexión' },
  { id: 'lunge',           label: 'Zancada' },
  { id: 'glute_bridge',    label: 'Puente glúteo' },
  { id: 'overhead_press',  label: 'Press militar' },
  { id: 'bicep_curl',      label: 'Curl bíceps' },
]

const selectedExercise = ref('squat')
const started      = ref(false)
const initializing = ref(false)
const summary      = ref<SessionSummary | null>(null)
const saved        = ref(false)

const videoRef  = ref<HTMLVideoElement | null>(null)
const canvasRef = ref<HTMLCanvasElement | null>(null)

const { isRunning, errorMessage, start, stop, librariesLoaded } = useMediaPipe()
const { currentFeedback, score, repCount, processFrame, reset, getSummary } = usePostureFeedback()

function onResults(results: PoseResults) {
  if (results.poseLandmarks) processFrame(selectedExercise.value, results.poseLandmarks)
}

async function startAnalysis() {
  if (!videoRef.value || !canvasRef.value) return
  initializing.value = true
  errorMessage.value = null
  summary.value = null
  saved.value = false
  reset()

  await start(videoRef.value, canvasRef.value, onResults)

  initializing.value = false
  if (!errorMessage.value) {
    started.value = true
  }
}

async function stopAnalysis() {
  await stop()
  started.value = false
}

/** Detiene la sesión y muestra el resumen (sin guardar) */
async function finishSession() {
  summary.value = getSummary()
  await stopAnalysis()
}

/** Guarda en servidor y muestra el resumen */
function saveAndFinish() {
  const snap = getSummary()
  router.post(route('posture.sessions.store'), {
    exercise_slug: selectedExercise.value,
    score: snap.finalScore,
    feedback: snap.topErrors.map(e => e.message),
    duration_sec: 0,
  }, {
    onSuccess: () => {
      summary.value = snap
      saved.value = true
      stopAnalysis()
    },
  })
}

/** Guarda el resumen ya capturado desde la pantalla de resumen */
function saveSummary() {
  if (!summary.value || saved.value) return
  router.post(route('posture.sessions.store'), {
    exercise_slug: selectedExercise.value,
    score: summary.value.finalScore,
    feedback: summary.value.topErrors.map(e => e.message),
    duration_sec: 0,
  }, {
    onSuccess: () => { saved.value = true },
  })
}

/** Vuelve al selector sin perder el ejercicio elegido */
function analyzeAgain() {
  summary.value = null
  saved.value = false
  reset()
}

// getUserMedia solo existe en HTTPS o localhost
const hasGetUserMedia = computed(() =>
  typeof navigator !== 'undefined' && !!navigator.mediaDevices?.getUserMedia
)

// Exponer para que CoachModal detenga la cámara al cerrar
defineExpose({ stopAnalysis })

onBeforeUnmount(() => stop())

function scoreColor(val?: number): string {
  const v = val ?? score.value
  if (v >= 80) return '#1DF412'
  if (v >= 50) return '#F59E0B'
  return '#EF4444'
}
</script>

<template>
  <div style="height:100%;overflow-y:auto;padding:16px;">

    <!-- ═══════════════════════════════════════
         PANTALLA DE RESUMEN (post-sesión)
    ════════════════════════════════════════ -->
    <div v-if="summary">

      <!-- Header -->
      <div style="text-align:center;margin-bottom:20px;">
        <div style="font-size:10px;color:#6B7280;text-transform:uppercase;letter-spacing:0.12em;margin-bottom:5px;">Sesión completada</div>
        <div style="font-size:16px;font-weight:700;color:#fff;">
          {{ EXERCISES.find(e => e.id === selectedExercise)?.label }}
        </div>
        <div v-if="saved"
          style="display:inline-flex;align-items:center;gap:5px;margin-top:7px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);border-radius:99px;padding:3px 10px;">
          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          <span style="font-size:10px;color:#1DF412;font-weight:600;">Guardado</span>
        </div>
      </div>

      <!-- Score Ring -->
      <div style="display:flex;justify-content:center;margin-bottom:20px;">
        <div style="position:relative;width:112px;height:112px;">
          <svg width="112" height="112" viewBox="0 0 112 112">
            <circle cx="56" cy="56" r="46" fill="none"
              stroke="rgba(255,255,255,0.06)" stroke-width="9"/>
            <circle cx="56" cy="56" r="46" fill="none"
              :stroke="scoreColor(summary.finalScore)"
              stroke-width="9"
              stroke-linecap="round"
              :stroke-dasharray="`${(2 * Math.PI * 46 * summary.finalScore / 100).toFixed(1)} ${(2 * Math.PI * 46).toFixed(1)}`"
              transform="rotate(-90 56 56)"
            />
          </svg>
          <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;">
            <div style="font-size:30px;font-weight:800;line-height:1;"
              :style="{ color: scoreColor(summary.finalScore) }">
              {{ summary.finalScore }}
            </div>
            <div style="font-size:9px;color:#6B7280;text-transform:uppercase;letter-spacing:0.1em;">Score</div>
          </div>
        </div>
      </div>

      <!-- Stats Row -->
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:20px;">
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:12px 6px;text-align:center;">
          <div style="font-size:22px;font-weight:700;color:#fff;line-height:1;">{{ summary.reps }}</div>
          <div style="font-size:9px;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;margin-top:3px;">Reps</div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:12px 6px;text-align:center;">
          <div style="font-size:22px;font-weight:700;color:#fff;line-height:1;">{{ summary.totalFrames }}</div>
          <div style="font-size:9px;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;margin-top:3px;">Frames</div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:12px 6px;text-align:center;">
          <div style="font-size:22px;font-weight:700;line-height:1;"
            :style="{ color: summary.totalFrames > 0 ? scoreColor(Math.round(summary.goodFrames / summary.totalFrames * 100)) : '#fff' }">
            {{ summary.totalFrames > 0 ? Math.round(summary.goodFrames / summary.totalFrames * 100) : 0 }}%
          </div>
          <div style="font-size:9px;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;margin-top:3px;">Técnica</div>
        </div>
      </div>

      <!-- Puntos a mejorar -->
      <div>
        <div style="font-size:10px;color:#4B5563;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:10px;">
          Puntos a mejorar
        </div>

        <!-- Sin errores: sesión perfecta -->
        <div v-if="summary.topErrors.length === 0"
          style="text-align:center;padding:20px 16px;background:rgba(29,244,18,0.05);border:1px solid rgba(29,244,18,0.15);border-radius:12px;">
          <div style="font-size:24px;margin-bottom:6px;">🎉</div>
          <div style="font-size:13px;font-weight:700;color:#1DF412;margin-bottom:4px;">¡Técnica perfecta!</div>
          <div style="font-size:12px;color:#6B7280;line-height:1.4;">No se detectaron errores durante la sesión.</div>
        </div>

        <!-- Lista de errores más frecuentes -->
        <div v-else style="display:flex;flex-direction:column;gap:8px;">
          <div v-for="err in summary.topErrors" :key="err.message"
            style="background:#161616;border-radius:10px;padding:11px 12px;"
            :style="err.severity === 'error'
              ? 'border:1px solid rgba(239,68,68,0.18);'
              : 'border:1px solid rgba(245,158,11,0.18);'">

            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:7px;">
              <div style="display:flex;align-items:flex-start;gap:7px;flex:1;">
                <div style="flex-shrink:0;margin-top:2px;"
                  :style="{ color: err.severity === 'error' ? '#EF4444' : '#F59E0B' }">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                  </svg>
                </div>
                <span style="font-size:12px;color:#fff;line-height:1.45;">{{ err.message }}</span>
              </div>
              <span style="font-size:12px;font-weight:700;flex-shrink:0;"
                :style="{ color: err.severity === 'error' ? '#EF4444' : '#F59E0B' }">
                {{ err.frequency }}%
              </span>
            </div>

            <!-- Barra de frecuencia -->
            <div style="height:3px;background:rgba(255,255,255,0.06);border-radius:99px;overflow:hidden;">
              <div :style="{
                height: '100%',
                borderRadius: '99px',
                width: err.frequency + '%',
                background: err.severity === 'error' ? '#EF4444' : '#F59E0B',
              }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Interpretación -->
      <div style="margin-top:14px;padding:12px 14px;border-radius:10px;background:#161616;border:1px solid rgba(255,255,255,0.05);">
        <div v-if="summary.finalScore >= 80" style="font-size:12px;color:#9CA3AF;line-height:1.5;">
          <span style="color:#1DF412;font-weight:700;">Muy buena técnica. </span>
          Mantén el patrón de movimiento y aumenta la carga progresivamente.
        </div>
        <div v-else-if="summary.finalScore >= 50" style="font-size:12px;color:#9CA3AF;line-height:1.5;">
          <span style="color:#F59E0B;font-weight:700;">Técnica en progreso. </span>
          Trabaja los puntos de la lista antes de añadir más peso.
        </div>
        <div v-else style="font-size:12px;color:#9CA3AF;line-height:1.5;">
          <span style="color:#EF4444;font-weight:700;">Técnica a mejorar. </span>
          Reduce la carga y enfócate en dominar el patrón de movimiento.
        </div>
      </div>

      <!-- Acciones -->
      <div style="display:flex;gap:8px;margin-top:16px;">
        <button @click="analyzeAgain"
          style="flex:1;font-weight:700;background:#1DF412;color:#000;border:none;border-radius:12px;padding:14px;font-size:14px;cursor:pointer;box-shadow:0 4px 16px rgba(29,244,18,0.25);">
          Analizar de nuevo
        </button>
        <button v-if="!saved" @click="saveSummary"
          style="font-weight:700;background:#1A1A1A;color:#9CA3AF;border:1.5px solid rgba(255,255,255,0.1);border-radius:12px;padding:14px 18px;font-size:14px;cursor:pointer;">
          Guardar
        </button>
      </div>

    </div>

    <!-- ═══════════════════════════════════════
         PANTALLA NORMAL (live análisis)
    ════════════════════════════════════════ -->
    <template v-else>

      <!-- Exercise selector (solo antes de iniciar) -->
      <div v-if="!started && !initializing" style="margin-bottom:16px;">
        <div style="font-size:10px;color:#4B5563;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:10px;">Ejercicio a analizar</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;">
          <button v-for="ex in EXERCISES" :key="ex.id"
            @click="selectedExercise = ex.id"
            style="border-radius:999px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
            :style="selectedExercise === ex.id
              ? 'background:#1DF412;color:#000;border-color:#1DF412;'
              : 'background:#1A1A1A;color:#9CA3AF;border-color:rgba(255,255,255,0.08);'">
            {{ ex.label }}
          </button>
        </div>
      </div>

      <!-- Camera view -->
      <div style="position:relative;border-radius:16px;background:#0D0D0D;border:1px solid rgba(255,255,255,0.06);overflow:hidden;aspect-ratio:4/3;margin-bottom:12px;">
        <video ref="videoRef" style="display:none;" playsinline></video>
        <canvas ref="canvasRef"
          style="width:100%;height:100%;object-fit:cover;"
          :style="started && isRunning ? '' : 'display:none;'"
        ></canvas>

        <!-- Placeholder: antes de iniciar -->
        <div v-if="!started && !initializing" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px;">
          <div style="width:56px;height:56px;border-radius:16px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:12px;color:#1DF412;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
          </div>
          <p style="font-size:13px;font-weight:700;color:#fff;margin-bottom:6px;">Cámara lista</p>
          <p style="font-size:12px;color:#9CA3AF;line-height:1.4;">Colócate de perfil a 2–3 m para que capte todo tu cuerpo.</p>
        </div>

        <!-- Spinner cargando -->
        <div v-if="initializing" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;">
          <div style="width:40px;height:40px;border-radius:50%;border:3px solid rgba(29,244,18,0.2);border-top-color:#1DF412;animation:spin 0.9s linear infinite;"></div>
          <p style="font-size:12px;color:#9CA3AF;">Cargando detector de postura...</p>
        </div>

        <!-- Live overlays: score + reps -->
        <div v-if="started && isRunning" style="position:absolute;top:12px;left:12px;background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);border-radius:10px;padding:8px 12px;border:1px solid rgba(255,255,255,0.08);">
          <div style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1px;">Score</div>
          <div class="font-display font-bold" style="font-size:22px;line-height:1;" :style="{ color: scoreColor() }">{{ score }}</div>
        </div>
        <div v-if="started && isRunning" style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);border-radius:10px;padding:8px 12px;border:1px solid rgba(255,255,255,0.08);">
          <div style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1px;">Reps</div>
          <div class="font-display font-bold" style="font-size:22px;line-height:1;color:#fff;">{{ repCount }}</div>
        </div>
      </div>

      <!-- Error con diagnóstico diferenciado -->
      <div v-if="errorMessage" style="margin-bottom:12px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:12px;padding:12px 14px;">
        <p style="font-size:12px;color:#EF4444;margin-bottom:8px;">{{ errorMessage }}</p>
        <div v-if="!hasGetUserMedia" style="font-size:11px;color:#6B7280;margin-bottom:8px;line-height:1.5;">
          🔒 El analizador solo funciona en conexiones seguras.<br>
          Abre <strong style="color:#9CA3AF;">http://localhost:8000</strong> en este mismo dispositivo.
        </div>
        <div v-else-if="!librariesLoaded()" style="font-size:11px;color:#6B7280;margin-bottom:8px;line-height:1.4;">
          💡 Verifica tu conexión a internet — MediaPipe se carga desde CDN.
        </div>
        <button v-if="hasGetUserMedia" @click="startAnalysis"
          style="font-size:12px;font-weight:700;background:rgba(239,68,68,0.15);color:#EF4444;border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:6px 12px;cursor:pointer;">
          Reintentar
        </button>
      </div>

      <!-- Controls -->
      <div style="display:flex;gap:8px;margin-bottom:16px;">
        <button v-if="!started && !initializing" @click="startAnalysis"
          style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px;font-weight:700;background:#1DF412;color:#000;border:none;border-radius:12px;padding:14px;font-size:14px;cursor:pointer;box-shadow:0 4px 20px rgba(29,244,18,0.3);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Iniciar análisis
        </button>
        <template v-if="started">
          <button @click="saveAndFinish"
            style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;font-weight:700;background:#1DF412;color:#000;border:none;border-radius:12px;padding:14px;font-size:14px;cursor:pointer;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Guardar
          </button>
          <button @click="finishSession"
            style="display:flex;align-items:center;justify-content:center;font-weight:700;background:#1A1A1A;color:#EF4444;border:1.5px solid rgba(239,68,68,0.3);border-radius:12px;padding:14px 18px;font-size:14px;cursor:pointer;">
            Detener
          </button>
        </template>
      </div>

      <!-- Live feedback -->
      <div v-if="started">
        <div style="font-size:10px;color:#4B5563;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:10px;">Feedback en vivo</div>
        <div v-if="currentFeedback.length === 0" style="text-align:center;padding:16px 0;">
          <p style="font-size:12px;color:#6B7280;">Posiciónate frente a la cámara para recibir correcciones.</p>
        </div>
        <div v-else style="display:flex;flex-direction:column;gap:8px;">
          <div v-for="(fb, i) in currentFeedback" :key="i"
            style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:10px;"
            :style="fb.severity === 'good'
              ? 'background:rgba(29,244,18,0.06);border:1px solid rgba(29,244,18,0.15);'
              : fb.severity === 'error'
                ? 'background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.15);'
                : 'background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.15);'">
            <div style="flex-shrink:0;margin-top:1px;"
              :style="{ color: fb.severity === 'good' ? '#1DF412' : fb.severity === 'error' ? '#EF4444' : '#F59E0B' }">
              <svg v-if="fb.severity === 'good'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
            </div>
            <span style="font-size:12px;line-height:1.5;color:#fff;">{{ fb.message }}</span>
          </div>
        </div>
      </div>

    </template>
  </div>
</template>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
