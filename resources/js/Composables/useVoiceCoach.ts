// Coach de voz para el analizador de postura.
// Envuelve la Web Speech API (window.speechSynthesis) para "hablar" las correcciones
// mientras el usuario entrena, sin que tenga que leer la pantalla a 2-3 m de distancia.
//
// Anti-spam: las correcciones llegan ~30 fps, así que speak() aplica un cooldown por mensaje
// y un gap global mínimo, y omite si ya se está hablando. speakNow() (reps / frase final)
// interrumpe y habla de inmediato.

import { ref } from 'vue'

const STORAGE_KEY = 'posture_voice_enabled'

// Preferencia de voces en español latinoamericano, con fallbacks
const VOICE_PREFERENCE = ['es-mx', 'es-us', 'es-419', 'es-co', 'es-ar', 'es-es', 'es']

export function useVoiceCoach() {
  const supported = ref(typeof window !== 'undefined' && 'speechSynthesis' in window)

  // Preferencia persistida (default: activada)
  const stored = (() => {
    try { return localStorage.getItem(STORAGE_KEY) } catch { return null }
  })()
  const enabled = ref(stored === null ? true : stored === 'true')

  let voice: SpeechSynthesisVoice | null = null

  function pickVoice() {
    if (!supported.value) return
    const voices = window.speechSynthesis.getVoices()
    if (!voices.length) return
    for (const code of VOICE_PREFERENCE) {
      const found = voices.find((v) => v.lang.toLowerCase().startsWith(code))
      if (found) { voice = found; return }
    }
    voice = null
  }

  if (supported.value) {
    pickVoice()
    // Las voces pueden cargarse de forma asíncrona (sobre todo en móvil)
    window.speechSynthesis.onvoiceschanged = () => pickVoice()
  }

  // Throttle
  const lastSpokenAt = new Map<string, number>()
  let lastAnyAt = 0
  const PER_MESSAGE_COOLDOWN = 5000 // mismo mensaje no se repite antes de 5 s
  const GLOBAL_GAP = 2500           // gap mínimo entre cualquier par de frases

  function buildUtterance(text: string): SpeechSynthesisUtterance {
    const u = new SpeechSynthesisUtterance(text)
    u.lang = voice?.lang ?? 'es-MX'
    if (voice) u.voice = voice
    u.rate = 1.05
    u.pitch = 1
    return u
  }

  /** Correcciones en tiempo real (con throttle, no interrumpe). */
  function speak(text: string) {
    if (!supported.value || !enabled.value || !text) return
    const now = Date.now()
    if (now - lastAnyAt < GLOBAL_GAP) return
    if (now - (lastSpokenAt.get(text) ?? 0) < PER_MESSAGE_COOLDOWN) return
    if (window.speechSynthesis.speaking || window.speechSynthesis.pending) return

    lastSpokenAt.set(text, now)
    lastAnyAt = now
    window.speechSynthesis.speak(buildUtterance(text))
  }

  /** Reps y frase final: interrumpe lo actual y habla de inmediato. */
  function speakNow(text: string) {
    if (!supported.value || !enabled.value || !text) return
    window.speechSynthesis.cancel()
    lastAnyAt = Date.now()
    window.speechSynthesis.speak(buildUtterance(text))
  }

  /**
   * Conteo de repeticiones: encola sin interrumpir la corrección actual.
   * Si ya hay algo en cola (pending), descarta para no acumular frases.
   */
  function speakRep(text: string) {
    if (!supported.value || !enabled.value || !text) return
    if (window.speechSynthesis.pending) return // ya hay una frase esperando, no apilar
    window.speechSynthesis.speak(buildUtterance(text))
  }

  /** Detiene cualquier locución y limpia el estado de throttle. */
  function cancel() {
    if (!supported.value) return
    window.speechSynthesis.cancel()
    lastSpokenAt.clear()
    lastAnyAt = 0
  }

  function toggle() {
    enabled.value = !enabled.value
    try { localStorage.setItem(STORAGE_KEY, String(enabled.value)) } catch { /* ignore */ }
    if (!enabled.value) cancel()
  }

  return { supported, enabled, speak, speakNow, speakRep, cancel, toggle }
}
