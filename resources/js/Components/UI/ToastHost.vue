<script setup lang="ts">
import { useToasts } from '@/Composables/useToasts'
import { animate } from 'animejs'

const { toasts, remove } = useToasts()

function onEnter(el: Element) {
  animate(el, {
    translateX: [40, 0],
    opacity: [0, 1],
    duration: 320,
    ease: 'cubicBezier(0.34, 1.56, 0.64, 1)',
  })
}

function onLeave(el: Element, done: () => void) {
  animate(el, {
    translateX: [0, 40],
    opacity: [1, 0],
    duration: 220,
    ease: 'easeInQuad',
    onComplete: done,
  })
}

function iconFor(type: string) {
  if (type === 'success') return '<polyline points="20 6 9 17 4 12"/>'
  if (type === 'warn')    return '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>'
  if (type === 'error')   return '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'
  // info
  return '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'
}

function colorFor(type: string): string {
  if (type === 'success') return '#1DF412'
  if (type === 'warn')    return '#F59E0B'
  if (type === 'error')   return '#EF4444'
  return '#60A5FA'
}
</script>

<template>
  <Teleport to="body">
    <div
      style="position:fixed;top:20px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none;max-width:320px;width:calc(100vw - 32px);">
      <TransitionGroup name="toast" :css="false" @enter="onEnter" @leave="onLeave">
        <div
          v-for="t in toasts"
          :key="t.id"
          style="pointer-events:auto;display:flex;align-items:flex-start;gap:10px;border-radius:14px;padding:12px 14px;backdrop-filter:blur(12px);cursor:pointer;"
          :style="{
            background: `rgba(10,10,10,0.95)`,
            border: `1px solid ${colorFor(t.type)}33`,
            boxShadow: `0 4px 24px rgba(0,0,0,0.5), 0 0 0 1px ${colorFor(t.type)}22`,
          }"
          @click="remove(t.id)"
        >
          <!-- Icono -->
          <div style="flex-shrink:0;margin-top:1px;" :style="{ color: colorFor(t.type) }">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              v-html="iconFor(t.type)" />
          </div>
          <!-- Texto -->
          <span style="font-size:13px;font-weight:600;color:#E5E7EB;line-height:1.45;flex:1;">
            {{ t.message }}
          </span>
          <!-- X -->
          <button
            style="flex-shrink:0;background:none;border:none;cursor:pointer;color:#6B7280;padding:0;line-height:1;margin-top:1px;"
            @click.stop="remove(t.id)">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="2.5" stroke-linecap="round">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-enter-active {
  transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(40px) scale(0.9);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(40px) scale(0.9);
}
.toast-move {
  transition: transform 0.25s ease;
}
</style>
