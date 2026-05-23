<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineOptions({ layout: GuestLayout })

function retry() {
  window.location.reload()
}
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center text-center px-6" style="background:#000;color:#fff;">
    <div class="flex items-center justify-center mb-6" style="width:80px;height:80px;border-radius:20px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);color:#1DF412;">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.58 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
    </div>
    <h1 class="font-display font-bold" style="font-size:28px;margin-bottom:8px;">Sin conexión</h1>
    <p style="font-size:15px;color:#9CA3AF;line-height:1.6;max-width:340px;margin-bottom:28px;">
      No hay conexión a internet. Algunas funciones no están disponibles sin conexión.
    </p>
    <button @click="retry"
      class="inline-flex items-center gap-2 font-bold"
      style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:14px 28px;font-size:15px;cursor:pointer;">
      Reintentar
    </button>
  </div>
</template>
