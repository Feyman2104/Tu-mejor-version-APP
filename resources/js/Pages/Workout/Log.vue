<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { PaginatedResponse, WorkoutLog } from '@/types'

defineOptions({ layout: AppLayout })

defineProps<{
  logs: PaginatedResponse<WorkoutLog>
}>()

function formatDate(d: string) {
  return new Date(d).toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long' })
}
</script>

<template>
  <div class="min-h-screen relative" style="background:#000;">
    <div class="px-4 md:px-8 py-6 max-w-3xl mx-auto pb-24">

      <div class="flex justify-between items-center" style="margin-bottom:24px;">
        <div>
          <h1 class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;">Historial</h1>
          <p style="font-size:14px;color:#9CA3AF;">Tus entrenamientos registrados</p>
        </div>
        <Link :href="route('workout.today')"
          class="flex items-center gap-2 font-bold flex-shrink-0"
          style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:12px 18px;font-size:14px;text-decoration:none;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          <span class="hidden md:inline">Entrenar hoy</span>
        </Link>
      </div>

      <!-- Empty -->
      <div v-if="logs.data.length === 0" class="flex flex-col items-center text-center py-20">
        <div style="width:64px;height:64px;border-radius:16px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:16px;color:#1DF412;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <h3 class="font-display font-bold" style="font-size:20px;margin-bottom:8px;">Sin entrenamientos aún</h3>
        <p style="font-size:14px;color:#9CA3AF;">Cuando completes tu primer entrenamiento aparecerá aquí.</p>
      </div>

      <!-- List -->
      <div v-else class="space-y-3">
        <div v-for="log in logs.data" :key="log.id"
          style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:18px 20px;">
          <div class="flex justify-between items-start" style="margin-bottom:12px;">
            <div>
              <div class="font-display font-bold" style="font-size:16px;letter-spacing:-0.01em;text-transform:capitalize;">{{ formatDate(log.date) }}</div>
              <div style="font-size:13px;color:#9CA3AF;">{{ log.sets?.length ?? 0 }} series registradas</div>
            </div>
            <span v-if="log.completed"
              style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);border-radius:6px;padding:4px 10px;">
              Completado
            </span>
            <span v-else
              style="font-size:11px;color:#F59E0B;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:6px;padding:4px 10px;">
              En curso
            </span>
          </div>
          <p v-if="log.notes" style="font-size:13px;color:#9CA3AF;line-height:1.5;">{{ log.notes }}</p>
        </div>
      </div>

    </div>
  </div>
</template>
