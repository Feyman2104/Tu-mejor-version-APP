<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { RoutineCard } from '@/types'

defineOptions({ layout: AppLayout })

const props = defineProps<{
  cards: RoutineCard[]
}>()

const menuOpenFor = ref<number | null>(null)

const focusColor = (focus: string) => ({
  push: '#F59E0B', pull: '#3B82F6', legs: '#1DF412',
  full_body: '#8B5CF6', cardio: '#EF4444', custom: '#1DF412',
} as Record<string, string>)[focus] ?? '#9CA3AF'

const customCount = computed(() => props.cards.filter(c => c.is_custom).length)

function startEmpty(): void {
  router.get(route('workout.empty'))
}

function startRoutine(card: RoutineCard): void {
  router.get(route('workout.session', { routineDay: card.routine_day_id }))
}

function newRoutine(): void {
  router.get(route('routines.create'))
}

function editRoutine(card: RoutineCard): void {
  menuOpenFor.value = null
  router.get(route('routines.edit', { routine: card.routine_id }))
}

function deleteRoutine(card: RoutineCard): void {
  menuOpenFor.value = null
  if (!confirm(`¿Eliminar la rutina "${card.name}"? Esta acción no se puede deshacer.`)) return
  router.delete(route('routines.destroy', { routine: card.routine_id }), { preserveScroll: true })
}

function toggleMenu(id: number): void {
  menuOpenFor.value = menuOpenFor.value === id ? null : id
}
</script>

<template>
  <div class="min-h-screen relative" style="background:#000;color:#fff;" @click="menuOpenFor = null">

    <!-- Glow -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
      <div class="absolute rounded-full" style="top:-5%;right:-25%;width:420px;height:420px;background:radial-gradient(circle,rgba(29,244,18,0.12) 0%,transparent 65%);filter:blur(40px);"></div>
    </div>

    <div class="relative z-10" style="max-width:760px;margin:0 auto;padding:24px 16px 96px;">

      <!-- Header -->
      <div class="flex items-center justify-between" style="margin-bottom:20px;">
        <h1 class="font-display font-bold" style="font-size:26px;letter-spacing:-0.02em;">Entrenamiento</h1>
      </div>

      <!-- Empezar entrenamiento vacío -->
      <button @click="startEmpty"
        class="w-full flex items-center gap-3"
        style="background:#161616;border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:16px 18px;cursor:pointer;color:#fff;margin-bottom:24px;text-align:left;"
        onmouseover="this.style.borderColor='rgba(29,244,18,0.4)'"
        onmouseout="this.style.borderColor='rgba(255,255,255,0.08)'">
        <span style="width:32px;height:32px;border-radius:9px;background:rgba(29,244,18,0.12);color:#1DF412;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </span>
        <span style="font-size:15px;font-weight:700;">Empezar Entrenamiento Vacío</span>
      </button>

      <!-- Sección Rutinas -->
      <div class="flex items-center justify-between" style="margin-bottom:12px;">
        <h2 class="font-display font-bold" style="font-size:19px;letter-spacing:-0.01em;">Rutinas</h2>
      </div>

      <div class="grid grid-cols-2 gap-3" style="margin-bottom:20px;">
        <button @click="newRoutine"
          class="flex items-center justify-center gap-2 font-semibold"
          style="background:#161616;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:14px;cursor:pointer;color:#fff;font-size:14px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
          Nueva Rutina
        </button>
        <button disabled
          class="flex items-center justify-center gap-2 font-semibold"
          style="background:#111;border:1px solid rgba(255,255,255,0.05);border-radius:12px;padding:14px;color:#6B7280;font-size:14px;cursor:not-allowed;"
          title="Próximamente">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Explorar
        </button>
      </div>

      <!-- Mis rutinas -->
      <div class="flex items-center gap-2" style="margin-bottom:12px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        <span style="font-size:13px;color:#9CA3AF;font-weight:600;">Mis rutinas ({{ cards.length }})</span>
      </div>

      <!-- Empty state -->
      <div v-if="cards.length === 0" class="flex flex-col items-center text-center"
        style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:18px;padding:40px 24px;">
        <div style="width:56px;height:56px;border-radius:14px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:14px;color:#1DF412;">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
        </div>
        <h3 class="font-display font-bold" style="font-size:18px;margin-bottom:6px;">Aún no tienes rutinas</h3>
        <p style="font-size:13px;color:#9CA3AF;margin-bottom:18px;">Crea tu primera rutina o genera una con el Coach IA.</p>
        <button @click="newRoutine" class="inline-flex items-center gap-2 font-bold"
          style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:12px 20px;font-size:14px;cursor:pointer;">
          Crear rutina
        </button>
      </div>

      <!-- Cards -->
      <div v-else class="flex flex-col gap-3">
        <div v-for="card in cards" :key="card.routine_day_id"
          style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px 18px;">

          <div class="flex items-start justify-between" style="margin-bottom:8px;">
            <div class="flex items-center gap-2" style="min-width:0;">
              <span :style="{ color: focusColor(card.focus) }" style="font-size:12px;">●</span>
              <h3 class="font-display font-bold" style="font-size:17px;letter-spacing:-0.01em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ card.name }}</h3>
              <span v-if="card.is_custom" style="font-size:10px;font-weight:700;color:#1DF412;background:rgba(29,244,18,0.12);border-radius:6px;padding:2px 6px;flex-shrink:0;">PROPIA</span>
            </div>

            <!-- Menú (solo rutinas propias) -->
            <div v-if="card.is_custom" class="relative" style="flex-shrink:0;">
              <button @click.stop="toggleMenu(card.routine_day_id)"
                style="background:transparent;border:none;color:#9CA3AF;cursor:pointer;padding:2px 4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
              </button>
              <div v-if="menuOpenFor === card.routine_day_id" @click.stop
                style="position:absolute;right:0;top:26px;z-index:20;background:#1A1A1A;border:1px solid rgba(255,255,255,0.1);border-radius:10px;overflow:hidden;min-width:130px;box-shadow:0 8px 24px rgba(0,0,0,0.5);">
                <button @click="editRoutine(card)"
                  class="w-full text-left" style="padding:10px 14px;font-size:13px;color:#fff;background:transparent;border:none;cursor:pointer;">Editar</button>
                <button @click="deleteRoutine(card)"
                  class="w-full text-left" style="padding:10px 14px;font-size:13px;color:#EF4444;background:transparent;border:none;cursor:pointer;border-top:1px solid rgba(255,255,255,0.06);">Eliminar</button>
              </div>
            </div>
          </div>

          <!-- Preview de ejercicios -->
          <p style="font-size:13px;color:#9CA3AF;line-height:1.5;margin-bottom:14px;">
            <template v-if="card.preview.length">{{ card.preview.join(', ') }}<span v-if="card.exercise_count > card.preview.length">…</span></template>
            <template v-else>Sin ejercicios todavía</template>
          </p>

          <button @click="startRoutine(card)"
            class="w-full flex items-center justify-center gap-2 font-bold"
            style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:13px;font-size:15px;cursor:pointer;box-shadow:0 4px 16px rgba(29,244,18,0.2);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            Empezar Rutina
          </button>
        </div>
      </div>

    </div>
  </div>
</template>
