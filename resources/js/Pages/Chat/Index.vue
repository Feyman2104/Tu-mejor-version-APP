<script setup lang="ts">
import { ref, nextTick, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { ChatMessage, User } from '@/types'

defineOptions({ layout: AppLayout })

const page = usePage()
const user = page.props.auth.user as User

const props = defineProps<{
  messages: ChatMessage[]
}>()

const history = ref<ChatMessage[]>([...props.messages])
const input = ref('')
const loading = ref(false)
const messagesEnd = ref<HTMLElement | null>(null)

onMounted(() => scrollToBottom())

function scrollToBottom() {
  nextTick(() => {
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' })
  })
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

  history.value.push({ id: Date.now(), role: 'user', content: text, created_at: new Date().toISOString() } as ChatMessage)
  scrollToBottom()

  const assistantMsg: ChatMessage = { id: Date.now() + 1, role: 'assistant', content: '', created_at: new Date().toISOString() } as ChatMessage
  history.value.push(assistantMsg)
  scrollToBottom()

  try {
    const res = await fetch(route('chat.send'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': xsrfToken(),
        'Accept': 'text/event-stream',
      },
      body: JSON.stringify({ message: text }),
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
          try {
            const parsed = JSON.parse(data)
            assistantMsg.content += parsed.text ?? ''
            scrollToBottom()
          } catch {}
        }
        if (!streaming) break
      }
    }
  } catch (err) {
    assistantMsg.content = 'Lo siento, ocurrió un error. Por favor, intenta de nuevo.'
  } finally {
    loading.value = false
    scrollToBottom()
  }
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    send()
  }
}

const QUICK = [
  'Crea mi rutina de esta semana',
  '¿Cuántas proteínas debo comer?',
  'Ejercicios para pierna sin equipamiento',
  'Tips para mejorar mi técnica de sentadilla',
]
</script>

<template>
  <div class="flex flex-col min-h-screen md:h-screen relative" style="background:#000;">

    <!-- ===== MOBILE header ===== -->
    <div class="md:hidden flex items-center gap-3 px-4 py-4 flex-shrink-0" style="border-bottom:1px solid rgba(255,255,255,0.06);">
      <div class="flex items-center justify-center" style="width:40px;height:40px;border-radius:12px;background:rgba(29,244,18,0.08);color:#1DF412;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
      </div>
      <div>
        <div class="font-semibold" style="font-size:15px;">Coach IA</div>
        <div style="font-size:12px;color:#1DF412;">● En línea</div>
      </div>
    </div>

    <!-- ===== DESKTOP header ===== -->
    <div class="hidden md:flex items-center gap-4 px-8 py-5 flex-shrink-0" style="border-bottom:1px solid rgba(255,255,255,0.06);">
      <div class="flex items-center justify-center" style="width:48px;height:48px;border-radius:14px;background:rgba(29,244,18,0.08);color:#1DF412;flex-shrink:0;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
      </div>
      <div>
        <h1 class="font-display font-bold" style="font-size:22px;letter-spacing:-0.01em;">Coach IA</h1>
        <div style="font-size:13px;color:#1DF412;">● En línea · Tu entrenador personal con IA</div>
      </div>
    </div>

    <!-- ===== Messages ===== -->
    <div class="flex-1 overflow-y-auto pb-4" style="padding-bottom:80px;" id="messages-container">
      <div class="px-4 md:px-8 py-4 space-y-4 max-w-3xl mx-auto">

        <!-- Empty state -->
        <div v-if="history.length === 0" class="flex flex-col items-center text-center py-12">
          <div class="flex items-center justify-center" style="width:80px;height:80px;border-radius:20px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);margin-bottom:20px;color:#1DF412;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          </div>
          <h2 class="font-display font-bold" style="font-size:22px;margin-bottom:8px;">¡Hola, {{ (user as any).name?.split(' ')[0] }}! 💪</h2>
          <p style="font-size:14px;color:#9CA3AF;max-width:360px;line-height:1.6;">Soy tu Coach IA. Puedo crear rutinas, explicar ejercicios, darte consejos de nutrición y motivarte cuando lo necesitas.</p>

          <!-- Quick suggestions -->
          <div class="flex flex-wrap justify-center gap-2 mt-6">
            <button v-for="q in QUICK" :key="q"
              @click="input = q; send()"
              style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:999px;padding:8px 16px;font-size:13px;color:#fff;cursor:pointer;transition:all 0.15s;">
              {{ q }}
            </button>
          </div>
        </div>

        <!-- Messages -->
        <div v-for="msg in history" :key="msg.id" class="flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
          <!-- Assistant avatar -->
          <div v-if="msg.role === 'assistant'" class="flex items-end gap-2">
            <div class="flex items-center justify-center flex-shrink-0" style="width:32px;height:32px;border-radius:10px;background:rgba(29,244,18,0.08);color:#1DF412;margin-bottom:4px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            </div>
            <div style="max-width:80%;background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:4px 18px 18px 18px;padding:12px 16px;">
              <div v-if="msg.content" class="whitespace-pre-wrap" style="font-size:14px;line-height:1.65;color:#fff;">{{ msg.content }}</div>
              <div v-else class="flex gap-1 items-center py-1">
                <span v-for="i in 3" :key="i" class="rounded-full" style="width:6px;height:6px;background:#9CA3AF;animation:bounce 1.2s infinite;" :style="`animation-delay:${(i-1)*0.2}s`"></span>
              </div>
            </div>
          </div>

          <!-- User bubble -->
          <div v-if="msg.role === 'user'" style="max-width:80%;background:#1DF412;border-radius:18px 4px 18px 18px;padding:12px 16px;">
            <div class="whitespace-pre-wrap" style="font-size:14px;line-height:1.65;color:#000;font-weight:500;">{{ msg.content }}</div>
          </div>
        </div>

        <div ref="messagesEnd"></div>
      </div>
    </div>

    <!-- ===== Input bar ===== -->
    <div class="flex-shrink-0 px-4 md:px-8 py-3 md:py-4" style="background:#000;border-top:1px solid rgba(255,255,255,0.06);">
      <div class="flex items-end gap-3 max-w-3xl mx-auto">
        <div class="flex-1" style="background:#161616;border:1.5px solid rgba(255,255,255,0.08);border-radius:16px;padding:4px 4px 4px 16px;display:flex;align-items:flex-end;">
          <textarea
            v-model="input"
            @keydown="onKeydown"
            :disabled="loading"
            placeholder="Escribe tu pregunta..."
            rows="1"
            class="flex-1 bg-transparent border-none outline-none resize-none"
            style="padding:10px 0;color:#fff;font-size:15px;line-height:1.5;max-height:120px;overflow-y:auto;font-family:inherit;"
          ></textarea>
          <button
            @click="send"
            :disabled="!input.trim() || loading"
            class="flex items-center justify-center flex-shrink-0"
            style="width:40px;height:40px;border-radius:12px;background:#1DF412;border:none;cursor:pointer;margin:2px;transition:all 0.15s;"
            :style="(!input.trim() || loading) ? 'opacity:0.4;cursor:not-allowed;' : ''"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
        </div>
      </div>
      <p class="text-center mt-2" style="font-size:11px;color:#6B7280;">Coach IA · MiniMax · Claude Haiku · Gemini Flash</p>
    </div>

  </div>
</template>

<style scoped>
@keyframes bounce {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-6px); }
}
</style>
