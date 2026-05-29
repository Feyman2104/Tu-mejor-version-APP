<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { useMediaPipe, type PoseResults } from '@/Composables/useMediaPipe'
import { usePostureFeedback, type SessionSummary } from '@/Composables/usePostureFeedback'
import { useVoiceCoach } from '@/Composables/useVoiceCoach'
import { EXERCISE_KNOWLEDGE } from '@/data/exerciseKnowledge'

const props = withDefaults(defineProps<{
  initialExercise?: string
  initialMode?: 'live' | 'upload'
}>(), {
  initialExercise: undefined,
  initialMode: 'live',
})

const EXERCISES = [
  { id: 'squat',           label: 'Sentadilla' },
  { id: 'pushup',          label: 'Flexión' },
  { id: 'lunge',           label: 'Zancada' },
  { id: 'glute_bridge',    label: 'Puente glúteo' },
  { id: 'overhead_press',  label: 'Press militar' },
  { id: 'bicep_curl',      label: 'Curl bíceps' },
]

const FACING_KEY = 'posture_facing_mode'

const VALID_EXERCISE_IDS = EXERCISES.map(e => e.id)

const selectedExercise = ref(
  props.initialExercise && VALID_EXERCISE_IDS.includes(props.initialExercise)
    ? props.initialExercise
    : 'squat'
)

type AnalysisMode = 'live' | 'upload'
const analysisMode = ref<AnalysisMode>(props.initialMode ?? 'live')

const started      = ref(false)
const briefing     = ref(false)
const initializing = ref(false)
const summary      = ref<SessionSummary | null>(null)
const saved        = ref(false)

// Video upload state
const uploadProgress   = ref(0)   // 0-100 basado en currentTime/duration
const uploadProcessing = ref(false)
const uploadFileName   = ref<string | null>(null)
const uploadVideoRef   = ref<HTMLVideoElement | null>(null)

function onVideoFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  if (!file.type.startsWith('video/')) {
    alert('Por favor selecciona un archivo de video (MP4, MOV, WebM, etc.)')
    ;(e.target as HTMLInputElement).value = ''
    return
  }
  uploadFileName.value = file.name
  const url = URL.createObjectURL(file)
  analyzeVideoFile(url)
}

async function analyzeVideoFile(fileUrl: string) {
  if (!videoRef.value || !canvasRef.value) return
  uploadProcessing.value = true
  uploadProgress.value = 0
  initializing.value = true
  errorMessage.value = null
  summary.value = null
  saved.value = false
  reset()

  // Progreso basado en eventos timeupdate del <video> oculto
  const vidEl = videoRef.value
  const onTimeUpdate = () => {
    if (vidEl.duration) {
      uploadProgress.value = Math.round((vidEl.currentTime / vidEl.duration) * 100)
    }
  }
  vidEl.addEventListener('timeupdate', onTimeUpdate)

  await startVideoFile(videoRef.value, canvasRef.value, onResults, fileUrl)

  vidEl.removeEventListener('timeupdate', onTimeUpdate)
  initializing.value = false
  uploadProcessing.value = false
  uploadProgress.value = 100

  // Mostrar resumen si hubo actividad (sin narración: el video se evalúa en silencio)
  const snap = getSummary()
  if (snap.totalFrames > 0) {
    summary.value = snap
  } else {
    errorMessage.value = 'No se detectó a ninguna persona en el video. Asegúrate de que el cuerpo se vea completo y bien iluminado.'
  }
}

// Orientación de pantalla — adapta el aspect-ratio de la cámara a portrait/landscape
const isLandscape = ref(
  typeof window !== 'undefined' ? window.innerWidth > window.innerHeight : false,
)
function updateOrientation() {
  isLandscape.value = window.innerWidth > window.innerHeight
}
onMounted(() => window.addEventListener('resize', updateOrientation))
onBeforeUnmount(() => window.removeEventListener('resize', updateOrientation))

// aspect-ratio de la cámara:
//   · Antes de iniciar: 4/3 (preview cuadrado estándar, funciona en cualquier pantalla)
//   · Durante análisis landscape (tablet/PC): 4/3 — pantalla ancha, cámara ancha
//   · Durante análisis portrait (móvil vertical): 3/4 — ve todo el cuerpo en alto
const cameraAspectRatio = computed(() => {
  if (!started.value) return '4/3'
  return isLandscape.value ? '4/3' : '3/4'
})

// Contador de posicionamiento (20 s antes de empezar el análisis)
const COUNTDOWN_SECS = 20
const countdown = ref<number | null>(null) // null = análisis activo | 0-20 = contando
let countdownTimer: ReturnType<typeof setInterval> | null = null

function startCountdown() {
  countdown.value = COUNTDOWN_SECS
  voice.speakNow('Prepárate. Colócate en posición.')
  countdownTimer = setInterval(() => {
    if (countdown.value === null) return
    countdown.value--
    // Cuenta regresiva en voz alta los últimos 3 segundos
    if (countdown.value > 0 && countdown.value <= 3) voice.speakNow(String(countdown.value))
    if (countdown.value <= 0) finishCountdown()
  }, 1000)
}

function finishCountdown() {
  if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null }
  countdown.value = null
  voice.speakNow('¡Empieza!')
}

function skipCountdown() {
  finishCountdown()
}

function clearCountdown() {
  if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null }
  countdown.value = null
}


// Conocimiento (cues + vista recomendada) del ejercicio seleccionado
const knowledge = computed(() => EXERCISE_KNOWLEDGE[selectedExercise.value])
const recommendedView = computed<'front' | 'side'>(() => knowledge.value?.recommended_view ?? 'side')
const viewLabel = computed(() => recommendedView.value === 'front' ? 'de frente' : 'de perfil')

// Cámara frontal ('user', entreno solo) o trasera ('environment', alguien me graba)
const storedFacing = (() => {
  try { return localStorage.getItem(FACING_KEY) } catch { return null }
})()
const facingMode = ref<'user' | 'environment'>(storedFacing === 'environment' ? 'environment' : 'user')

const videoRef  = ref<HTMLVideoElement | null>(null)
const canvasRef = ref<HTMLCanvasElement | null>(null)

const { isRunning, errorMessage, start, startVideoFile, stop, librariesLoaded } = useMediaPipe()
const { currentFeedback, score, repCount, totalFrames, isPersonDetected, processFrame, reset, getSummary } = usePostureFeedback()
const voice = useVoiceCoach()

function onResults(results: PoseResults) {
  if (!results.poseLandmarks) return
  // Durante el contador de posicionamiento la cámara muestra el feed pero no analizamos
  if (countdown.value !== null) return
  processFrame(selectedExercise.value, results.poseLandmarks)

  // La voz y las correcciones habladas son EXCLUSIVAS del modo cámara en vivo.
  // El video subido se analiza en silencio y solo muestra el resumen al final.
  if (analysisMode.value !== 'live') return

  // Voz: anunciar la corrección más importante del frame (error > warning, ignora 'good')
  const fb = currentFeedback.value
  const top = fb.find(f => f.severity === 'error') ?? fb.find(f => f.severity === 'warning')
  if (top) voice.speak(top.message)
}

// Conteo de repeticiones en voz alta — solo en modo cámara en vivo
watch(repCount, (n, old) => {
  if (analysisMode.value !== 'live') return
  if (n > old && n > 0) voice.speakRep(String(n))
})

/** Muestra la tarjeta de técnica y narra las claves antes de arrancar la cámara */
function openBriefing() {
  errorMessage.value = null
  summary.value = null
  saved.value = false
  briefing.value = true

  const k = knowledge.value
  if (!k) return
  const view = recommendedView.value === 'front'
    ? 'Colócate de frente a la cámara.'
    : 'Colócate de perfil a la cámara.'
  const cues = k.execution_cues.slice(0, 2).join('. ')
  voice.speakNow(`${k.name_es}. ${view} ${cues}`)
}

/** Desde la tarjeta de briefing: arranca cámara → espera nextTick → inicia contador */
async function beginFromBriefing() {
  briefing.value = false
  await nextTick() // Vue renderiza video/canvas (estaban en v-else-if="briefing")
  await startAnalysis()
  if (started.value) startCountdown()
}

async function startAnalysis() {
  if (!videoRef.value || !canvasRef.value) return
  initializing.value = true
  errorMessage.value = null
  summary.value = null
  saved.value = false
  reset()

  await start(videoRef.value, canvasRef.value, onResults, facingMode.value)

  initializing.value = false
  if (!errorMessage.value) started.value = true
  // El voice "Prepárate..." lo dice startCountdown() justo después
}

async function stopAnalysis() {
  clearCountdown()
  voice.cancel()
  await stop()
  started.value = false
  briefing.value = false
}

/** Cancela la sesión sin mostrar resumen — salida limpia */
async function cancelAnalysis() {
  clearCountdown()
  voice.cancel()
  await stop()
  reset()
  started.value = false
  briefing.value = false
  summary.value = null
  saved.value = false
}

/** Frase de cierre según el score final */
function speakFinal(finalScore: number) {
  const phrase = finalScore >= 80
    ? 'Excelente técnica'
    : finalScore >= 50
      ? 'Buen trabajo, sigue puliendo la técnica'
      : 'Sigue practicando, baja la carga y enfócate en la técnica'
  voice.speakNow(phrase)
}

/** Alterna cámara frontal/trasera; en caliente reinicia sin perder la sesión */
async function switchCamera() {
  facingMode.value = facingMode.value === 'user' ? 'environment' : 'user'
  try { localStorage.setItem(FACING_KEY, facingMode.value) } catch { /* ignore */ }
  if (started.value && videoRef.value && canvasRef.value) {
    await stop()
    await start(videoRef.value, canvasRef.value, onResults, facingMode.value)
  }
}

/** Detiene la sesión y muestra el resumen (sin guardar).
 *  Si no hubo actividad real (<60 frames y 0 reps), cancela sin mostrar resumen vacío. */
async function finishSession() {
  if (repCount.value === 0 && totalFrames.value < 60) {
    await cancelAnalysis()
    return
  }
  const snap = getSummary()
  summary.value = snap
  await stopAnalysis()
  speakFinal(snap.finalScore)
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
      speakFinal(snap.finalScore)
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

// Estado visual del frame actual: la peor severidad presente
const frameStatus = computed<'error' | 'warning' | 'good'>(() => {
  const fb = currentFeedback.value
  if (fb.some(f => f.severity === 'error')) return 'error'
  if (fb.some(f => f.severity === 'warning')) return 'warning'
  return 'good'
})

function statusColor(s: 'error' | 'warning' | 'good'): string {
  return s === 'error' ? '#EF4444' : s === 'warning' ? '#F59E0B' : '#1DF412'
}

// Exponer para que CoachModal detenga la cámara al cerrar
defineExpose({ stopAnalysis })

onBeforeUnmount(() => { clearCountdown(); voice.cancel(); stop() })

function scoreColor(val?: number): string {
  const v = val ?? score.value
  if (v >= 80) return '#1DF412'
  if (v >= 50) return '#F59E0B'
  return '#EF4444'
}
</script>

<template>
  <div style="height:100%;overflow-y:auto;" :style="started ? 'padding:8px 12px;' : 'padding:20px 16px;'">
  <div style="max-width:560px;margin:0 auto;">

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
        <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:10px;">
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
         BRIEFING DE TÉCNICA (antes de arrancar)
    ════════════════════════════════════════ -->
    <div v-else-if="briefing">

      <!-- Título del ejercicio -->
      <div style="margin-bottom:20px;">
        <div style="font-size:10px;color:#6B7280;text-transform:uppercase;letter-spacing:0.14em;font-weight:700;margin-bottom:6px;">Análisis de técnica</div>
        <div class="font-display" style="font-size:30px;font-weight:900;color:#fff;line-height:1.05;text-transform:uppercase;margin-bottom:10px;">
          {{ knowledge?.name_es }}
        </div>
        <!-- Músculos primarios como badges -->
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <span v-for="m in (knowledge?.muscle_primary ?? [])" :key="m"
            style="font-size:11px;background:rgba(29,244,18,0.1);color:#1DF412;border-radius:99px;padding:3px 10px;font-weight:600;border:1px solid rgba(29,244,18,0.2);text-transform:capitalize;">
            {{ m }}
          </span>
        </div>
      </div>

      <!-- Vista recomendada — banner prominente -->
      <div style="display:flex;align-items:center;gap:14px;background:rgba(29,244,18,0.06);border:1px solid rgba(29,244,18,0.25);border-radius:16px;padding:16px 18px;margin-bottom:24px;">
        <div style="flex-shrink:0;width:44px;height:44px;border-radius:12px;background:rgba(29,244,18,0.12);color:#1DF412;display:flex;align-items:center;justify-content:center;">
          <svg v-if="recommendedView === 'front'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
          <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a4 4 0 1 0-8 0"/><path d="M6 21a6 6 0 0 1 12 0"/><circle cx="10" cy="8" r="0.5" fill="currentColor"/></svg>
        </div>
        <div>
          <div style="font-size:14px;font-weight:700;color:#fff;">
            Colócate <span style="color:#1DF412;">{{ viewLabel }}</span> a la cámara
          </div>
          <div style="font-size:12px;color:#9CA3AF;margin-top:3px;line-height:1.4;">
            A 2–3 m · todo el cuerpo visible en cuadro
          </div>
        </div>
      </div>

      <!-- Puntos clave de ejecución -->
      <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;margin-bottom:12px;">
        Puntos clave de ejecución
      </div>
      <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px;">
        <div v-for="(cue, i) in (knowledge?.execution_cues ?? [])" :key="i"
          style="display:flex;align-items:flex-start;gap:14px;background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:14px 16px;transition:border-color 0.15s;">
          <!-- Número grande verde -->
          <div style="flex-shrink:0;width:34px;height:34px;border-radius:9px;background:#1DF412;color:#000;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:900;line-height:1;">
            {{ i + 1 }}
          </div>
          <!-- Texto de la cue -->
          <p style="margin:0;font-size:13px;color:#E5E7EB;line-height:1.6;font-weight:500;padding-top:6px;">{{ cue }}</p>
        </div>
      </div>

      <!-- Acciones -->
      <div style="display:flex;gap:10px;">
        <button @click="briefing = false"
          style="font-weight:700;background:#1A1A1A;color:#9CA3AF;border:1.5px solid rgba(255,255,255,0.1);border-radius:14px;padding:15px 20px;font-size:14px;cursor:pointer;">
          Volver
        </button>
        <button @click="beginFromBriefing"
          style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px;font-weight:800;background:#1DF412;color:#000;border:none;border-radius:14px;padding:15px;font-size:15px;cursor:pointer;box-shadow:0 4px 24px rgba(29,244,18,0.35);letter-spacing:0.02em;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Empezar análisis
        </button>
      </div>

    </div>

    <!-- ═══════════════════════════════════════
         PANTALLA NORMAL (live análisis)
    ════════════════════════════════════════ -->
    <template v-else>

      <!-- Exercise selector (solo antes de iniciar) -->
      <div v-if="!started && !initializing" style="margin-bottom:16px;">
        <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:10px;">Ejercicio a analizar</div>
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

        <!-- Selector de modo: Cámara en vivo / Subir video -->
        <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin:16px 0 10px;">Modo de análisis</div>
        <div style="display:flex;gap:6px;margin-bottom:12px;">
          <button @click="analysisMode = 'live'"
            style="display:flex;align-items:center;gap:6px;border-radius:999px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
            :style="analysisMode === 'live'
              ? 'background:#1DF412;color:#000;border-color:#1DF412;'
              : 'background:#1A1A1A;color:#9CA3AF;border-color:rgba(255,255,255,0.08);'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7 16 12 23 17z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
            Cámara en vivo
          </button>
          <button @click="analysisMode = 'upload'"
            style="display:flex;align-items:center;gap:6px;border-radius:999px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
            :style="analysisMode === 'upload'
              ? 'background:#1DF412;color:#000;border-color:#1DF412;'
              : 'background:#1A1A1A;color:#9CA3AF;border-color:rgba(255,255,255,0.08);'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Subir video
          </button>
        </div>

        <!-- Uploader — solo visible en modo upload -->
        <div v-if="analysisMode === 'upload'" style="margin-bottom:12px;">
          <label
            style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;background:#0D0D0D;border:1.5px dashed rgba(255,255,255,0.12);border-radius:14px;padding:24px 16px;cursor:pointer;transition:border-color 0.15s;text-align:center;"
            :style="uploadProcessing ? 'pointer-events:none;opacity:0.7;' : 'hover:border-[#1DF412];'"
          >
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(29,244,18,0.08);display:flex;align-items:center;justify-content:center;color:#1DF412;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div>
              <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:3px;">
                {{ uploadFileName ?? 'Selecciona un video' }}
              </div>
              <div style="font-size:11px;color:#6B7280;">MP4, MOV, WebM · El video no se sube al servidor</div>
            </div>
            <input type="file" accept="video/*" style="display:none;" @change="onVideoFileChange" :disabled="uploadProcessing" />
          </label>
          <p style="font-size:11px;color:#6B7280;line-height:1.4;margin-top:8px;">
            Graba <strong style="color:#9CA3AF;">{{ viewLabel }}</strong>, con todo el cuerpo en cuadro. El análisis y el resumen aparecen al terminar el video.
          </p>
        </div>

        <!-- Selector de cámara (solo en modo live) -->
        <template v-if="analysisMode === 'live'">
          <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin:16px 0 10px;">Cámara</div>
          <div style="display:flex;gap:6px;">
            <button @click="facingMode !== 'user' && switchCamera()"
              style="display:flex;align-items:center;gap:6px;border-radius:999px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
              :style="facingMode === 'user'
                ? 'background:#1DF412;color:#000;border-color:#1DF412;'
                : 'background:#1A1A1A;color:#9CA3AF;border-color:rgba(255,255,255,0.08);'">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.66a8 8 0 0 1 10 0"/></svg>
              Frontal
            </button>
            <button @click="facingMode !== 'environment' && switchCamera()"
              style="display:flex;align-items:center;gap:6px;border-radius:999px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
              :style="facingMode === 'environment'
                ? 'background:#1DF412;color:#000;border-color:#1DF412;'
                : 'background:#1A1A1A;color:#9CA3AF;border-color:rgba(255,255,255,0.08);'">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              Trasera
            </button>
          </div>
          <p style="font-size:11px;color:#6B7280;line-height:1.4;margin-top:8px;">
            Usa la <strong style="color:#9CA3AF;">frontal</strong> si entrenas solo, o la
            <strong style="color:#9CA3AF;">trasera</strong> si alguien te graba.
          </p>
        </template>
      </div>

      <!-- Camera view — aspect-ratio responsivo según orientación de pantalla -->
      <div style="position:relative;border-radius:16px;background:#0D0D0D;overflow:hidden;margin-bottom:8px;transition:aspect-ratio 0.35s ease,border-color 0.2s,box-shadow 0.2s;"
        :style="{
          'aspect-ratio': cameraAspectRatio,
          'border': started && isRunning ? `3px solid ${statusColor(frameStatus)}` : '1px solid rgba(255,255,255,0.06)',
          'box-shadow': started && isRunning ? `0 0 22px ${statusColor(frameStatus)}55` : 'none',
        }">
        <video ref="videoRef" style="display:none;" playsinline muted></video>
        <canvas ref="canvasRef"
          style="width:100%;height:100%;object-fit:contain;"
          :style="(started && isRunning) || uploadProcessing ? '' : 'display:none;'"
        ></canvas>

        <!-- Placeholder: antes de iniciar -->
        <div v-if="!started && !initializing" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px;">
          <div style="width:56px;height:56px;border-radius:16px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:12px;color:#1DF412;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
          </div>
          <p style="font-size:13px;font-weight:700;color:#fff;margin-bottom:6px;">Cámara lista</p>
          <p style="font-size:12px;color:#9CA3AF;line-height:1.4;">Colócate {{ viewLabel }} a 2–3 m para que capte todo tu cuerpo.</p>
        </div>

        <!-- Spinner cargando (solo modo cámara en vivo) -->
        <div v-if="initializing && !uploadProcessing" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;">
          <div style="width:40px;height:40px;border-radius:50%;border:3px solid rgba(29,244,18,0.2);border-top-color:#1DF412;animation:spin 0.9s linear infinite;"></div>
          <p style="font-size:12px;color:#9CA3AF;">Cargando detector de postura...</p>
        </div>

        <!-- ═══ PANTALLA "ANALIZANDO TU POSTURA" (modo subir video) ═══ -->
        <div v-if="uploadProcessing"
          style="position:absolute;inset:0;z-index:12;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;padding:18px;background:linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.35) 55%, transparent 100%);">

          <!-- Línea de escaneo animada -->
          <div class="scanline" style="position:absolute;left:0;right:0;height:2px;background:linear-gradient(90deg, transparent, #1DF412, transparent);box-shadow:0 0 12px #1DF412;"></div>

          <!-- Badge superior: detección activa -->
          <div style="position:absolute;top:14px;left:50%;transform:translateX(-50%);display:flex;align-items:center;gap:7px;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);border:1px solid rgba(29,244,18,0.3);border-radius:999px;padding:6px 14px;">
            <div style="width:7px;height:7px;border-radius:50%;background:#1DF412;animation:pulse 1.2s ease-in-out infinite;"></div>
            <span style="font-size:10px;font-weight:700;color:#1DF412;letter-spacing:0.08em;text-transform:uppercase;">Detectando postura</span>
          </div>

          <!-- Bloque inferior: título + progreso -->
          <div style="width:100%;max-width:340px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
              <div style="width:34px;height:34px;border-radius:50%;border:3px solid rgba(29,244,18,0.2);border-top-color:#1DF412;animation:spin 0.9s linear infinite;flex-shrink:0;"></div>
              <div>
                <div class="font-display" style="font-size:18px;font-weight:900;color:#fff;line-height:1.1;text-transform:uppercase;">Analizando tu postura</div>
                <div style="font-size:11px;color:#9CA3AF;margin-top:1px;">{{ knowledge?.name_es }} · procesando en tu dispositivo</div>
              </div>
            </div>
            <!-- Barra de progreso -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
              <span style="font-size:10px;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Progreso del video</span>
              <span style="font-size:13px;color:#1DF412;font-weight:800;">{{ uploadProgress }}%</span>
            </div>
            <div style="height:6px;background:rgba(255,255,255,0.08);border-radius:99px;overflow:hidden;">
              <div :style="{ width: uploadProgress + '%', background: 'linear-gradient(90deg,#15C40F,#1DF412)', height: '100%', borderRadius: '99px', transition: 'width 0.3s ease' }"></div>
            </div>
          </div>
        </div>

        <!-- Hint de vista recomendada (centrado arriba) -->
        <div v-if="started && isRunning && countdown === null" style="position:absolute;top:12px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);border-radius:999px;padding:5px 12px;border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:6px;">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.66a8 8 0 0 1 10 0"/></svg>
          <span style="font-size:10px;color:#D1D5DB;font-weight:600;">Vista {{ viewLabel }}</span>
        </div>

        <!-- Indicador de detección de persona (sobre la pill de estado) -->
        <div v-if="started && isRunning && countdown === null"
          style="position:absolute;bottom:56px;left:50%;transform:translateX(-50%);display:flex;align-items:center;gap:5px;border-radius:999px;padding:4px 10px;backdrop-filter:blur(8px);transition:all 0.3s;"
          :style="isPersonDetected
            ? 'background:rgba(29,244,18,0.12);border:1px solid rgba(29,244,18,0.25);'
            : 'background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.25);'">
          <div style="width:6px;height:6px;border-radius:50%;transition:background 0.3s;"
            :style="isPersonDetected ? 'background:#1DF412;' : 'background:#F59E0B;'"></div>
          <span style="font-size:9px;font-weight:700;letter-spacing:0.08em;transition:color 0.3s;"
            :style="isPersonDetected ? 'color:#1DF412;' : 'color:#F59E0B;'">
            {{ isPersonDetected ? 'DETECTADO' : 'AJUSTA POSICIÓN' }}
          </span>
        </div>

        <!-- Live overlays: score + reps -->
        <div v-if="started && isRunning && countdown === null" style="position:absolute;top:12px;left:12px;background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);border-radius:10px;padding:8px 12px;border:1px solid rgba(255,255,255,0.08);">
          <div style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1px;">Score</div>
          <div class="font-display font-bold" style="font-size:22px;line-height:1;" :style="{ color: scoreColor() }">{{ score }}</div>
        </div>
        <div v-if="started && isRunning && countdown === null" style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);border-radius:10px;padding:8px 12px;border:1px solid rgba(255,255,255,0.08);">
          <div style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1px;">Reps</div>
          <div class="font-display font-bold" style="font-size:22px;line-height:1;color:#fff;">{{ repCount }}</div>
        </div>

        <!-- Pill de estado grande (legible a distancia) -->
        <div v-if="started && isRunning && countdown === null" style="position:absolute;bottom:12px;left:50%;transform:translateX(-50%);">
          <div style="display:flex;align-items:center;gap:8px;border-radius:999px;padding:8px 16px;backdrop-filter:blur(8px);transition:all 0.2s;"
            :style="`background:${statusColor(frameStatus)}26;border:2px solid ${statusColor(frameStatus)};color:${statusColor(frameStatus)};`">
            <svg v-if="frameStatus === 'good'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span class="font-display font-bold" style="font-size:18px;letter-spacing:0.05em;">{{ frameStatus === 'good' ? 'BIEN' : 'CORRIGE' }}</span>
          </div>
        </div>

        <!-- Toggle de voz (esquina inferior izquierda) -->
        <button v-if="started && isRunning && countdown === null && voice.supported.value" @click="voice.toggle()"
          :title="voice.enabled.value ? 'Silenciar voz' : 'Activar voz'"
          style="position:absolute;bottom:12px;left:12px;width:40px;height:40px;border-radius:50%;backdrop-filter:blur(8px);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;"
          :style="voice.enabled.value
            ? 'background:rgba(29,244,18,0.18);border:1.5px solid #1DF412;color:#1DF412;'
            : 'background:rgba(0,0,0,0.65);border:1.5px solid rgba(255,255,255,0.12);color:#9CA3AF;'">
          <svg v-if="voice.enabled.value" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
          <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
        </button>

        <!-- Flip de cámara (esquina inferior derecha) -->
        <button v-if="started && isRunning && countdown === null" @click="switchCamera()"
          :title="facingMode === 'user' ? 'Cambiar a cámara trasera' : 'Cambiar a cámara frontal'"
          style="position:absolute;bottom:12px;right:12px;width:40px;height:40px;border-radius:50%;background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);border:1.5px solid rgba(255,255,255,0.12);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 19H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"/><path d="M13 5h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-5"/><path d="m16 8 3-3-3-3"/><path d="m8 16-3 3 3 3"/></svg>
        </button>

        <!-- Countdown overlay — cámara activa, análisis bloqueado -->
        <div v-if="countdown !== null"
          style="position:absolute;inset:0;z-index:10;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;background:rgba(0,0,0,0.55);backdrop-filter:blur(2px);">
          <p style="font-size:11px;color:#D1D5DB;text-transform:uppercase;letter-spacing:0.12em;font-weight:700;margin:0;">
            Posiciónate frente a la cámara
          </p>
          <!-- SVG progress ring -->
          <div style="position:relative;width:120px;height:120px;">
            <svg width="120" height="120" viewBox="0 0 120 120">
              <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="6"/>
              <circle cx="60" cy="60" r="50" fill="none" stroke="#1DF412" stroke-width="6" stroke-linecap="round"
                :stroke-dasharray="`${(2 * Math.PI * 50 * (countdown ?? 0) / COUNTDOWN_SECS).toFixed(1)} ${(2 * Math.PI * 50).toFixed(1)}`"
                transform="rotate(-90 60 60)"
                style="transition:stroke-dasharray 0.9s linear;"/>
            </svg>
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">
              <span style="font-size:48px;font-weight:900;color:#fff;line-height:1;">{{ countdown }}</span>
            </div>
          </div>
          <button @click="skipCountdown"
            style="display:flex;align-items:center;gap:6px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:99px;padding:8px 18px;color:#D1D5DB;font-size:13px;font-weight:600;cursor:pointer;">
            Omitir
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polygon points="5 4 15 12 5 20 5 4"/><line x1="19" y1="5" x2="19" y2="19"/></svg>
          </button>
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

      <!-- Controls — compactos cuando el análisis está activo -->
      <div style="display:flex;gap:8px;" :style="started ? 'margin-bottom:8px;' : 'margin-bottom:16px;'">
        <button v-if="!started && !initializing && analysisMode === 'live'" @click="openBriefing"
          style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px;font-weight:700;background:#1DF412;color:#000;border:none;border-radius:12px;padding:14px;font-size:14px;cursor:pointer;box-shadow:0 4px 20px rgba(29,244,18,0.3);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Iniciar análisis
        </button>
        <template v-if="started">
          <!-- Cancelar -->
          <button @click="cancelAnalysis"
            style="display:flex;align-items:center;justify-content:center;font-weight:700;background:#1A1A1A;color:#6B7280;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;font-size:13px;cursor:pointer;"
            :style="'padding:' + (started ? '10px 14px' : '14px 16px')">
            Cancelar
          </button>
          <!-- Guardar -->
          <button @click="saveAndFinish"
            style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;font-weight:700;background:#1DF412;color:#000;border:none;border-radius:12px;font-size:13px;cursor:pointer;"
            :style="'padding:' + (started ? '10px 14px' : '14px')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Guardar
          </button>
          <!-- Detener -->
          <button @click="finishSession"
            style="display:flex;align-items:center;justify-content:center;font-weight:700;background:#1A1A1A;color:#EF4444;border:1.5px solid rgba(239,68,68,0.3);border-radius:12px;font-size:13px;cursor:pointer;"
            :style="'padding:' + (started ? '10px 14px' : '14px 16px')">
            Detener
          </button>
        </template>
      </div>

      <!-- Live feedback -->
      <div v-if="started">
        <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:10px;">Feedback en vivo</div>
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
  </div><!-- /max-width wrapper -->
  </div><!-- /scroll container -->
</template>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%      { opacity: 0.4; transform: scale(0.7); }
}
@keyframes scanline {
  0%   { top: 0%;   opacity: 0; }
  15%  { opacity: 1; }
  85%  { opacity: 1; }
  100% { top: 100%; opacity: 0; }
}
.scanline {
  top: 0;
  animation: scanline 2.2s ease-in-out infinite;
}
</style>
