<script setup lang="ts">
import { ref, computed, nextTick, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useWorkoutSession } from '@/Composables/useWorkoutSession'
import { usePostureSession } from '@/Composables/usePostureSession'
import type { User } from '@/types'

const page = usePage()
const user = page.props.auth?.user as User | null
const workoutSession = useWorkoutSession()
const postureSession = usePostureSession()

interface LocalMessage {
  id: number
  role: 'user' | 'assistant'
  content: string
}

const history = ref<LocalMessage[]>([])
const input = ref('')
const loading = ref(false)
const messagesEnd = ref<HTMLElement | null>(null)

const firstName = user?.name?.split(' ')[0] ?? 'campeón'

// Memoria por sesión: la conversación visible se mantiene mientras dure la
// sesión del navegador (sobrevive a cerrar/reabrir el modal y a recargar la
// página) y se limpia al cerrar el navegador o cerrar sesión. El backend sigue
// guardando todo en BD, así que el coach recuerda lo hablado aunque se limpie.
const STORAGE_KEY = `coach-chat:${user?.id ?? 'guest'}`

onMounted(() => {
  try {
    const saved = sessionStorage.getItem(STORAGE_KEY)
    if (saved) {
      history.value = JSON.parse(saved)
      scrollToBottom()
    }
  } catch {}
})

watch(history, (val) => {
  try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(val)) } catch {}
}, { deep: true })

// Sugerencias rápidas adaptadas según si hay sesión activa
const QUICK = computed(() =>
  workoutSession.sessionContext.value.active
    ? [
        '¿Cómo optimizo mi técnica en este entrenamiento?',
        '¿Cuánto tiempo de descanso necesito?',
        'Analiza mi progreso de hoy',
        '¿Debo aumentar el peso en el próximo set?',
      ]
    : [
        'Dame un consejo para hoy',
        '¿Cuántas proteínas debo comer?',
        'Ejercicios para pierna en casa',
        'Cómo mejorar mi sentadilla',
      ],
)

function scrollToBottom() {
  nextTick(() => messagesEnd.value?.scrollIntoView({ behavior: 'smooth' }))
}

function xsrfToken(): string {
  const match = document.cookie.split(';').find(c => c.trim().startsWith('XSRF-TOKEN='))
  return match ? decodeURIComponent(match.split('=').slice(1).join('=')) : ''
}

async function send() {
  const text = input.value.trim()
  if (!text || loading.value) return

  input.value = ''
  loading.value = true

  history.value.push({ id: Date.now(), role: 'user', content: text })
  scrollToBottom()

  const assistantMsg: LocalMessage = { id: Date.now() + 1, role: 'assistant', content: '' }
  history.value.push(assistantMsg)
  scrollToBottom()

  try {
    const res = await fetch(route('chat.send'), {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': xsrfToken(),
        'Accept': 'text/event-stream',
      },
      body: JSON.stringify({
        message: text,
        ...(workoutSession.contextString.value
          ? { workout_context: workoutSession.contextString.value }
          : {}),
        ...(postureSession.contextString.value
          ? { posture_context: postureSession.contextString.value }
          : {}),
      }),
    })

    if (!res.ok) throw new Error(`HTTP ${res.status}`)

    const reader = res.body?.getReader()
    const decoder = new TextDecoder()
    if (!reader) throw new Error('No reader')

    let buffer = ''
    let streaming = true
    while (streaming) {
      const { done, value } = await reader.read()
      if (done) break
      buffer += decoder.decode(value, { stream: true })

      // SSE events are separated by a blank line. Process only complete
      // events and keep any trailing partial event in the buffer.
      let sep
      while ((sep = buffer.indexOf('\n\n')) !== -1) {
        const event = buffer.slice(0, sep)
        buffer = buffer.slice(sep + 2)
        for (const line of event.split('\n')) {
          if (!line.startsWith('data: ')) continue
          const data = line.slice(6)
          if (data === '[DONE]') { streaming = false; break }
          try { assistantMsg.content += (JSON.parse(data).text ?? ''); scrollToBottom() } catch {}
        }
        if (!streaming) break
      }
    }
  } catch {
    assistantMsg.content = 'Lo siento, ocurrió un error. Intenta de nuevo.'
  } finally {
    loading.value = false
    scrollToBottom()
  }
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send() }
}
</script>

<template>
  <div style="display:flex;flex-direction:column;height:100%;">

    <!-- Messages area -->
    <div style="flex:1;overflow-y:auto;padding:16px;" id="coach-messages">

      <!-- Empty state -->
      <div v-if="history.length === 0" style="display:flex;flex-direction:column;align-items:center;text-align:center;padding:24px 16px;">
        <div style="width:56px;height:56px;border-radius:16px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:#1DF412;">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        </div>
        <p style="font-size:14px;font-weight:700;color:#fff;margin-bottom:4px;">¡Hola, {{ firstName }}! 💪</p>
        <p style="font-size:12px;color:#9CA3AF;line-height:1.5;margin-bottom:10px;">Soy tu Coach IA. Pregúntame cualquier cosa.</p>
        <!-- Badge sesión activa -->
        <div v-if="workoutSession.sessionContext.value.active"
          style="display:inline-flex;align-items:center;gap:6px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);border-radius:99px;padding:5px 12px;margin-bottom:14px;">
          <span style="width:6px;height:6px;border-radius:50%;background:#1DF412;animation:pulse 1.5s infinite;"></span>
          <span style="font-size:11px;color:#1DF412;font-weight:700;">Sesión activa — {{
            workoutSession.sessionContext.value.completedSets
          }}/{{ workoutSession.sessionContext.value.totalSets }} series</span>
        </div>
        <div v-else style="margin-bottom:14px;"></div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center;">
          <button v-for="q in QUICK" :key="q"
            @click="input = q; send()"
            style="background:#161616;border:1px solid rgba(255,255,255,0.08);border-radius:999px;padding:7px 14px;font-size:12px;color:#fff;cursor:pointer;">
            {{ q }}
          </button>
        </div>
      </div>

      <!-- Messages -->
      <div v-for="msg in history" :key="msg.id"
        style="display:flex;margin-bottom:12px;"
        :style="msg.role === 'user' ? 'justify-content:flex-end' : 'justify-content:flex-start'">

        <!-- Assistant -->
        <div v-if="msg.role === 'assistant'" style="display:flex;align-items:flex-end;gap:8px;max-width:85%;">
          <div style="width:28px;height:28px;border-radius:8px;background:rgba(29,244,18,0.08);color:#1DF412;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-bottom:2px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          </div>
          <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:4px 16px 16px 16px;padding:10px 14px;">
            <div v-if="msg.content" style="font-size:13px;line-height:1.65;color:#fff;white-space:pre-wrap;">{{ msg.content }}</div>
            <div v-else style="display:flex;gap:4px;align-items:center;padding:2px 0;">
              <span v-for="i in 3" :key="i" style="width:6px;height:6px;border-radius:50%;background:#6B7280;" :style="`animation:bounce 1.2s infinite;animation-delay:${(i-1)*0.2}s`"></span>
            </div>
          </div>
        </div>

        <!-- User -->
        <div v-if="msg.role === 'user'"
          style="max-width:85%;background:#1DF412;border-radius:16px 4px 16px 16px;padding:10px 14px;">
          <div style="font-size:13px;line-height:1.65;color:#000;font-weight:500;white-space:pre-wrap;">{{ msg.content }}</div>
        </div>
      </div>

      <div ref="messagesEnd"></div>
    </div>

    <!-- Input -->
    <div style="flex-shrink:0;padding:12px 16px;border-top:1px solid rgba(255,255,255,0.06);background:#0A0A0A;">
      <div style="display:flex;align-items:flex-end;gap:8px;">
        <div style="flex:1;background:#161616;border:1.5px solid rgba(255,255,255,0.08);border-radius:14px;padding:4px 4px 4px 14px;display:flex;align-items:flex-end;">
          <textarea
            v-model="input"
            @keydown="onKeydown"
            :disabled="loading"
            placeholder="Escribe tu pregunta..."
            rows="1"
            style="flex:1;background:transparent;border:none;outline:none;resize:none;padding:8px 0;color:#fff;font-size:14px;line-height:1.5;max-height:100px;overflow-y:auto;font-family:inherit;"
          ></textarea>
          <button @click="send" :disabled="!input.trim() || loading"
            style="width:36px;height:36px;border-radius:10px;background:#1DF412;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin:2px;transition:opacity 0.15s;"
            :style="(!input.trim() || loading) ? 'opacity:0.4;cursor:not-allowed;' : ''">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes bounce {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-5px); }
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.35; }
}
</style>
