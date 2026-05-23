// Gestiona el ciclo de vida de MediaPipe Pose (cargado vía CDN en app.blade.php).
// Expone start/stop de la cámara y un callback por cada frame con resultados.

import { ref, shallowRef } from 'vue'
import type { Landmark } from '@/composables/useAngleCalculator'

// Globals inyectados por los scripts CDN de MediaPipe
declare global {
  interface Window {
    Pose: any
    Camera: any
    drawConnectors: any
    drawLandmarks: any
    POSE_CONNECTIONS: any
  }
}

export interface PoseResults {
  poseLandmarks?: Landmark[]
  image: CanvasImageSource
}

export function useMediaPipe() {
  const isReady = ref(false)
  const isRunning = ref(false)
  const errorMessage = ref<string | null>(null)

  const pose = shallowRef<any>(null)
  const camera = shallowRef<any>(null)

  let onResultsCallback: ((results: PoseResults) => void) | null = null

  function librariesLoaded(): boolean {
    return typeof window.Pose !== 'undefined' && typeof window.Camera !== 'undefined'
  }

  async function waitForLibraries(timeoutMs = 10000): Promise<boolean> {
    const start = Date.now()
    while (!librariesLoaded()) {
      if (Date.now() - start > timeoutMs) return false
      await new Promise((r) => setTimeout(r, 100))
    }
    return true
  }

  /**
   * Inicializa MediaPipe Pose y arranca la cámara sobre el <video> dado,
   * dibujando el esqueleto sobre el <canvas>.
   */
  async function start(
    videoEl: HTMLVideoElement,
    canvasEl: HTMLCanvasElement,
    onResults: (results: PoseResults) => void,
  ): Promise<void> {
    errorMessage.value = null
    onResultsCallback = onResults

    // Pre-check: getUserMedia solo existe en HTTPS o localhost.
    // Interceptamos ANTES de instanciar Camera para evitar el window.alert() interno de MediaPipe.
    if (!navigator.mediaDevices?.getUserMedia) {
      errorMessage.value =
        'El analizador requiere una conexión segura (HTTPS). ' +
        'Accede desde http://localhost:8000 en este dispositivo, ' +
        'o configura HTTPS para usarlo desde otros equipos de la red.'
      return
    }

    const loaded = await waitForLibraries()
    if (!loaded) {
      errorMessage.value = 'No se pudieron cargar las librerías de detección. Recarga la página.'
      return
    }

    try {
      pose.value = new window.Pose({
        locateFile: (file: string) =>
          `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}`,
      })

      pose.value.setOptions({
        modelComplexity: 1,
        smoothLandmarks: true,
        enableSegmentation: false,
        minDetectionConfidence: 0.5,
        minTrackingConfidence: 0.5,
      })

      const ctx = canvasEl.getContext('2d')!

      pose.value.onResults((results: PoseResults) => {
        canvasEl.width = videoEl.videoWidth || 640
        canvasEl.height = videoEl.videoHeight || 480

        ctx.save()
        ctx.clearRect(0, 0, canvasEl.width, canvasEl.height)
        ctx.drawImage(results.image, 0, 0, canvasEl.width, canvasEl.height)

        if (results.poseLandmarks) {
          // Esqueleto
          window.drawConnectors(ctx, results.poseLandmarks, window.POSE_CONNECTIONS, {
            color: 'rgba(29, 244, 18, 0.8)',
            lineWidth: 3,
          })
          window.drawLandmarks(ctx, results.poseLandmarks, {
            color: '#FFFFFF',
            fillColor: '#1DF412',
            lineWidth: 1,
            radius: 4,
          })
        }
        ctx.restore()

        onResultsCallback?.(results)
      })

      camera.value = new window.Camera(videoEl, {
        onFrame: async () => {
          await pose.value.send({ image: videoEl })
        },
        width: 640,
        height: 480,
      })

      await camera.value.start()
      isReady.value = true
      isRunning.value = true
    } catch (err: any) {
      errorMessage.value =
        err?.name === 'NotAllowedError'
          ? 'Permiso de cámara denegado. Habilítalo en tu navegador para usar el analizador.'
          : 'Error al iniciar la cámara: ' + (err?.message ?? 'desconocido')
      isRunning.value = false
    }
  }

  async function stop(): Promise<void> {
    try {
      if (camera.value) {
        await camera.value.stop()
        camera.value = null
      }
      if (pose.value) {
        pose.value.close?.()
        pose.value = null
      }
    } catch {
      // ignore cleanup errors
    }
    isRunning.value = false
  }

  return {
    isReady,
    isRunning,
    errorMessage,
    librariesLoaded,
    start,
    stop,
  }
}
