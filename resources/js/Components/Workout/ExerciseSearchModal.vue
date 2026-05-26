<script setup lang="ts">
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue'
import type { Exercise } from '@/types'
import ExerciseModal from './ExerciseModal.vue'

const emit = defineEmits<{
  select: [exercise: Exercise]
  close: []
}>()

const query        = ref('')
const muscleGroup  = ref('')
const results      = ref<Exercise[]>([])
const loading      = ref(false)
const searchInput  = ref<HTMLInputElement | null>(null)

// ─── Selección múltiple + detalle ─────────────────────────────────────────────
const selected       = ref<Exercise[]>([])
const detailExercise = ref<Exercise | null>(null)

function isSelected(id: number): boolean {
  return selected.value.some(e => e.id === id)
}

function toggleSelect(ex: Exercise): void {
  if (isSelected(ex.id)) selected.value = selected.value.filter(e => e.id !== ex.id)
  else                   selected.value.push(ex)
}

function openDetail(ex: Exercise): void {
  detailExercise.value = ex
}

function closeDetail(): void {
  detailExercise.value = null
  // ExerciseModal resetea el scroll del body al cerrarse (vía su watcher); el
  // buscador sigue abierto, así que re-afirmamos el bloqueo tras ese flush.
  nextTick(() => { document.body.style.overflow = 'hidden' })
}

function addFromDetail(): void {
  if (detailExercise.value) toggleSelect(detailExercise.value)
  closeDetail()
}

function confirmAdd(): void {
  selected.value.forEach(ex => emit('select', ex))
  emit('close')
}

// Grupos musculares disponibles (coinciden con los valores de la BD)
const MUSCLE_GROUPS = [
  { value: '',          label: 'Todos' },
  { value: 'chest',     label: 'Pecho' },
  { value: 'back',      label: 'Espalda' },
  { value: 'shoulders', label: 'Hombros' },
  { value: 'biceps',    label: 'Bíceps' },
  { value: 'triceps',   label: 'Tríceps' },
  { value: 'legs',      label: 'Piernas' },
  { value: 'glutes',    label: 'Glúteos' },
  { value: 'core',      label: 'Core' },
  { value: 'cardio',    label: 'Cardio' },
]

let debounceTimer: ReturnType<typeof setTimeout> | null = null

async function doSearch() {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (query.value.trim())  params.set('q', query.value.trim())
    if (muscleGroup.value)   params.set('muscle_group', muscleGroup.value)

    const res  = await fetch(`/exercises/search?${params}`, { credentials: 'same-origin' })
    results.value = res.ok ? await res.json() : []
  } catch {
    results.value = []
  } finally {
    loading.value = false
  }
}

watch([query, muscleGroup], () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(doSearch, 280)
})

function onKeydown(e: KeyboardEvent) {
  if (e.key !== 'Escape') return
  if (detailExercise.value) { closeDetail(); return }  // cierra solo el detalle
  emit('close')
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
  document.body.style.overflow = 'hidden'
  doSearch() // carga inicial — todos los ejercicios
  setTimeout(() => searchInput.value?.focus(), 80)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
  if (debounceTimer) clearTimeout(debounceTimer)
})
</script>

<template>
  <Teleport to="body">
    <div style="position:fixed;inset:0;z-index:200;display:flex;align-items:flex-end;"
      @click.self="emit('close')">

      <!-- Backdrop -->
      <div style="position:absolute;inset:0;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);"
        @click="emit('close')" />

      <!-- Sheet -->
      <div style="position:relative;z-index:1;width:100%;background:#0D0D0D;border-radius:20px 20px 0 0;max-height:88dvh;display:flex;flex-direction:column;border-top:1px solid rgba(255,255,255,0.08);overflow:hidden;">

        <!-- Handle -->
        <div style="display:flex;justify-content:center;padding:12px 0 4px;flex-shrink:0;">
          <div style="width:36px;height:4px;border-radius:2px;background:#2A2A2A;"></div>
        </div>

        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 18px 12px;flex-shrink:0;">
          <span style="font-size:15px;font-weight:700;color:#fff;">Agregar ejercicio</span>
          <button @click="emit('close')"
            style="width:28px;height:28px;border-radius:50%;background:#1A1A1A;border:1px solid rgba(255,255,255,0.08);color:#9CA3AF;cursor:pointer;display:flex;align-items:center;justify-content:center;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>

        <!-- Search input -->
        <div style="padding:0 16px 12px;flex-shrink:0;">
          <div style="display:flex;align-items:center;gap:10px;background:#161616;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:10px 14px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input ref="searchInput" v-model="query" type="text"
              placeholder="Buscar ejercicio o músculo..."
              style="flex:1;background:transparent;border:none;outline:none;color:#fff;font-size:14px;caret-color:#1DF412;"
            />
            <button v-if="query" @click="query = ''"
              style="color:#6B7280;background:transparent;border:none;cursor:pointer;padding:0;line-height:1;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
        </div>

        <!-- Muscle group filter chips -->
        <div style="display:flex;gap:6px;padding:0 16px 12px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;flex-shrink:0;">
          <button v-for="mg in MUSCLE_GROUPS" :key="mg.value"
            @click="muscleGroup = mg.value"
            style="flex-shrink:0;border-radius:999px;padding:5px 12px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid;white-space:nowrap;"
            :style="muscleGroup === mg.value
              ? 'background:#1DF412;color:#000;border-color:#1DF412;'
              : 'background:#1A1A1A;color:#9CA3AF;border-color:rgba(255,255,255,0.08);'">
            {{ mg.label }}
          </button>
        </div>

        <!-- Results -->
        <div style="flex:1;min-height:0;overflow-y:auto;padding:0 16px 24px;">

          <!-- Loading -->
          <div v-if="loading" style="display:flex;justify-content:center;padding:24px;">
            <div style="width:28px;height:28px;border-radius:50%;border:2.5px solid rgba(29,244,18,0.2);border-top-color:#1DF412;animation:spin 0.8s linear infinite;"></div>
          </div>

          <!-- Empty -->
          <div v-else-if="results.length === 0"
            style="text-align:center;padding:32px 16px;color:#6B7280;font-size:13px;">
            No se encontraron ejercicios
          </div>

          <!-- List -->
          <div v-else style="display:flex;flex-direction:column;gap:8px;">
            <div v-for="ex in results" :key="ex.id"
              @click="openDetail(ex)"
              style="width:100%;display:flex;align-items:center;gap:12px;background:#161616;border:1px solid;border-radius:12px;padding:10px 12px;cursor:pointer;text-align:left;transition:border-color 0.15s;"
              :style="isSelected(ex.id) ? 'border-color:rgba(29,244,18,0.4);' : 'border-color:rgba(255,255,255,0.06);'">

              <!-- Thumbnail -->
              <div style="flex-shrink:0;width:44px;height:44px;border-radius:8px;overflow:hidden;background:#0A0A0A;display:flex;align-items:center;justify-content:center;">
                <img v-if="ex.gif_url || ex.thumbnail"
                  :src="(ex.gif_url ?? ex.thumbnail)!"
                  :alt="ex.name"
                  style="width:100%;height:100%;object-fit:cover;"
                  loading="lazy"
                />
                <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4B5563" stroke-width="2" stroke-linecap="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
              </div>

              <!-- Info -->
              <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ ex.name }}</div>
                <div style="font-size:11px;color:#6B7280;margin-top:2px;display:flex;align-items:center;gap:6px;">
                  <span>{{ ex.muscle_group }}</span>
                  <span style="opacity:0.4;">·</span>
                  <span>{{ ex.level }}</span>
                </div>
              </div>

              <!-- Botón seleccionar / quitar -->
              <button @click.stop="toggleSelect(ex)"
                style="flex-shrink:0;width:30px;height:30px;border-radius:50%;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.15s;"
                :style="isSelected(ex.id)
                  ? 'background:#1DF412;color:#000;'
                  : 'background:rgba(29,244,18,0.1);color:#1DF412;'"
                :aria-label="isSelected(ex.id) ? 'Quitar de la selección' : 'Agregar a la selección'">
                <svg v-if="isSelected(ex.id)" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Barra inferior — confirmar selección -->
        <div v-if="selected.length"
          style="flex-shrink:0;padding:12px 16px;padding-bottom:max(16px,env(safe-area-inset-bottom,16px));background:#0D0D0D;border-top:1px solid rgba(255,255,255,0.08);">
          <button @click="confirmAdd()"
            class="w-full flex items-center justify-center gap-2 font-bold"
            style="background:#1DF412;color:#000;border:none;border-radius:14px;padding:15px;font-size:15px;cursor:pointer;box-shadow:0 4px 20px rgba(29,244,18,0.25);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Agregar {{ selected.length }} ejercicio{{ selected.length === 1 ? '' : 's' }}
          </button>
        </div>

      </div>
    </div>

    <!-- Detalle "cómo se realiza" — por encima del buscador (z-index 260) -->
    <ExerciseModal
      :exercise="detailExercise"
      :addable="true"
      :selected="detailExercise ? isSelected(detailExercise.id) : false"
      @add="addFromDetail"
      @close="closeDetail"
    />
  </Teleport>
</template>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }
div::-webkit-scrollbar { display: none; }
</style>
