<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { Exercise } from '@/types'

defineOptions({ layout: AppLayout })

defineProps<{
  exercise: Exercise & {
    instructions?: string[]
    common_errors?: string[]
    contraindications?: { zone: string; phase: string; recommendation: string }[]
  }
}>()
</script>

<template>
  <div class="min-h-screen relative" style="background:#000;">
    <div class="px-4 md:px-8 py-6 max-w-2xl mx-auto pb-24">

      <!-- Back -->
      <Link :href="route('exercises.index')" class="inline-flex items-center gap-2 mb-6" style="color:#9CA3AF;font-size:14px;text-decoration:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Volver a ejercicios
      </Link>

      <!-- Header -->
      <div class="flex items-start gap-4" style="margin-bottom:24px;">
        <div class="flex items-center justify-center flex-shrink-0" style="width:56px;height:56px;border-radius:14px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);color:#1DF412;">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
        </div>
        <div>
          <h1 class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;line-height:1.1;">{{ exercise.name }}</h1>
          <div class="flex flex-wrap gap-2 mt-2">
            <span style="font-size:11px;color:#1DF412;font-weight:600;text-transform:capitalize;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);border-radius:6px;padding:4px 10px;">{{ exercise.muscle_group }}</span>
            <span style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:capitalize;background:#242424;border-radius:6px;padding:4px 10px;">{{ exercise.level }}</span>
            <span style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:capitalize;background:#242424;border-radius:6px;padding:4px 10px;">{{ exercise.environment }}</span>
          </div>
        </div>
      </div>

      <!-- Description -->
      <p v-if="exercise.description" style="font-size:15px;color:#D1D5DB;line-height:1.6;margin-bottom:24px;">{{ exercise.description }}</p>

      <!-- Instructions -->
      <div v-if="exercise.instructions?.length" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:18px;padding:24px;margin-bottom:16px;">
        <h3 class="font-display font-bold" style="font-size:18px;margin-bottom:16px;">Ejecución</h3>
        <ol class="space-y-3">
          <li v-for="(step, i) in exercise.instructions" :key="i" class="flex gap-3">
            <span class="flex items-center justify-center flex-shrink-0 font-bold" style="width:24px;height:24px;border-radius:50%;background:#1DF412;color:#000;font-size:12px;">{{ i + 1 }}</span>
            <span style="font-size:14px;color:#D1D5DB;line-height:1.5;">{{ step }}</span>
          </li>
        </ol>
      </div>

      <!-- Common errors -->
      <div v-if="exercise.common_errors?.length" style="background:#161616;border:1px solid rgba(245,158,11,0.2);border-radius:18px;padding:24px;margin-bottom:16px;">
        <h3 class="font-display font-bold flex items-center gap-2" style="font-size:18px;margin-bottom:16px;color:#F59E0B;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Errores comunes
        </h3>
        <ul class="space-y-2">
          <li v-for="(err, i) in exercise.common_errors" :key="i" class="flex gap-2" style="font-size:14px;color:#D1D5DB;line-height:1.5;">
            <span style="color:#F59E0B;">•</span> {{ err }}
          </li>
        </ul>
      </div>

      <!-- Contraindications -->
      <div v-if="exercise.contraindications?.length" style="background:#161616;border:1px solid rgba(239,68,68,0.2);border-radius:18px;padding:24px;">
        <h3 class="font-display font-bold flex items-center gap-2" style="font-size:18px;margin-bottom:16px;color:#EF4444;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7v5c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/></svg>
          Contraindicaciones
        </h3>
        <div class="space-y-3">
          <div v-for="(c, i) in exercise.contraindications" :key="i" style="padding:12px 14px;background:#0D0D0D;border-radius:10px;">
            <div style="font-size:12px;color:#EF4444;font-weight:600;text-transform:capitalize;margin-bottom:4px;">{{ c.zone }} · {{ c.phase }}</div>
            <div style="font-size:13px;color:#D1D5DB;line-height:1.5;">{{ c.recommendation }}</div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
