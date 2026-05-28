<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import ChatPanel from './ChatPanel.vue'
import PosturePanel from './PosturePanel.vue'

const props = withDefaults(defineProps<{
  open: boolean
  initialTab?: 'chat' | 'posture'
  initialExercise?: string
  initialMode?: 'live' | 'upload'
}>(), {
  initialTab: 'chat',
  initialExercise: undefined,
  initialMode: 'live',
})

const emit = defineEmits<{
  close: []
}>()

type Tab = 'chat' | 'posture'
const activeTab = ref<Tab>(props.initialTab)
const postureRef = ref<InstanceType<typeof PosturePanel> | null>(null)

// Al abrir, aplicar la pestaña/ejercicio/modo indicados
watch(() => props.open, async (isOpen) => {
  if (!isOpen) {
    await postureRef.value?.stopAnalysis()
    document.body.style.overflow = ''
  } else {
    activeTab.value = props.initialTab
    document.body.style.overflow = 'hidden'
  }
})

watch(activeTab, async (tab) => {
  if (tab !== 'posture') {
    await postureRef.value?.stopAnalysis()
  }
})

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.open) emit('close')
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open"
        style="position:fixed;inset:0;z-index:110;display:flex;align-items:stretch;justify-content:center;"
        @click.self="emit('close')">

        <!-- Backdrop -->
        <div
          style="position:absolute;inset:0;background:rgba(0,0,0,0.8);backdrop-filter:blur(6px);"
          @click="emit('close')"
        />

        <!-- Sheet (pantalla completa) -->
        <div style="position:relative;z-index:1;width:100%;max-width:100%;height:100dvh;background:#0A0A0A;border-radius:0;overflow:hidden;display:flex;flex-direction:column;">

          <!-- Handle + Header -->
          <div style="flex-shrink:0;padding:12px 20px 0;">
            <!-- Handle -->
            <div style="display:flex;justify-content:center;margin-bottom:14px;">
              <div style="width:36px;height:4px;border-radius:2px;background:#2A2A2A;"></div>
            </div>

            <!-- Tab bar -->
            <div style="display:flex;align-items:center;gap:0;background:#161616;border-radius:14px;padding:4px;margin-bottom:4px;">
              <button @click="activeTab = 'chat'"
                style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;border:none;cursor:pointer;border-radius:10px;padding:10px 12px;font-size:13px;font-weight:700;transition:all 0.2s;"
                :style="activeTab === 'chat'
                  ? 'background:#1DF412;color:#000;'
                  : 'background:transparent;color:#6B7280;'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                Coach IA
              </button>
              <button @click="activeTab = 'posture'"
                style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;border:none;cursor:pointer;border-radius:10px;padding:10px 12px;font-size:13px;font-weight:700;transition:all 0.2s;"
                :style="activeTab === 'posture'
                  ? 'background:#1DF412;color:#000;'
                  : 'background:transparent;color:#6B7280;'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                Postura
              </button>
            </div>
          </div>

          <!-- Content panels — flex:1 so they fill remaining height -->
          <div style="flex:1;overflow:hidden;position:relative;">
            <div v-show="activeTab === 'chat'" style="position:absolute;inset:0;display:flex;flex-direction:column;">
              <ChatPanel />
            </div>
            <div v-show="activeTab === 'posture'" style="position:absolute;inset:0;overflow-y:auto;">
              <PosturePanel
                ref="postureRef"
                :initial-exercise="initialExercise"
                :initial-mode="initialMode"
              />
            </div>
          </div>

          <!-- Close button -->
          <button @click="emit('close')"
            style="position:absolute;top:16px;right:16px;width:32px;height:32px;border-radius:50%;background:#1A1A1A;border:1px solid rgba(255,255,255,0.08);color:#9CA3AF;cursor:pointer;display:flex;align-items:center;justify-content:center;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
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
  transition: transform 0.28s cubic-bezier(0.32, 0.72, 0, 1);
}
.modal-leave-active > div:last-child {
  transition: transform 0.2s ease-in;
}
.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
  transform: translateY(100%);
}
</style>
