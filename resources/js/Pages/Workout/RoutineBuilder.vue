<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ExerciseSearchModal from '@/Components/Workout/ExerciseSearchModal.vue'
import type { Exercise } from '@/types'

defineOptions({ layout: AppLayout })

interface BuilderRow {
  exercise: Exercise
  sets: number
  reps: string
  rest_seconds: number
}

interface EditPayload {
  id: number
  name: string
  exercises: { exercise: Exercise; sets: number; reps: string; rest_seconds: number }[]
}

const props = defineProps<{
  routine: EditPayload | null
}>()

const isEditing = computed(() => props.routine !== null)
const name = ref(props.routine?.name ?? '')
const rows = reactive<BuilderRow[]>(
  (props.routine?.exercises ?? []).map(e => ({
    exercise: e.exercise,
    sets: e.sets,
    reps: e.reps,
    rest_seconds: e.rest_seconds,
  }))
)

const showSearch = ref(false)
const saving = ref(false)
const errorMsg = ref('')

function onSelectExercise(ex: Exercise): void {
  if (rows.some(r => r.exercise.id === ex.id)) return
  rows.push({ exercise: ex, sets: 3, reps: '10', rest_seconds: 60 })
}

function removeRow(idx: number): void {
  rows.splice(idx, 1)
}

const canSave = computed(() => name.value.trim().length > 0 && rows.length > 0 && !saving.value)

function save(): void {
  errorMsg.value = ''
  if (!canSave.value) {
    errorMsg.value = !name.value.trim()
      ? 'Ponle un nombre a tu rutina.'
      : 'Añade al menos un ejercicio.'
    return
  }
  saving.value = true
  const payload = {
    name: name.value.trim(),
    exercises: rows.map(r => ({
      exercise_id: r.exercise.id,
      sets: Number(r.sets) || 1,
      reps: String(r.reps || '10'),
      rest_seconds: Number(r.rest_seconds) || 60,
    })),
  }

  const opts = {
    onError: () => { errorMsg.value = 'Revisa los datos e inténtalo de nuevo.' },
    onFinish: () => { saving.value = false },
  }

  if (isEditing.value && props.routine) {
    router.patch(route('routines.update', { routine: props.routine.id }), payload, opts)
  } else {
    router.post(route('routines.store'), payload, opts)
  }
}

function cancel(): void {
  router.get(route('workout.index'))
}
</script>

<template>
  <div class="min-h-screen relative" style="background:#000;color:#fff;">
    <div class="relative z-10" style="max-width:760px;margin:0 auto;padding:24px 16px 120px;">

      <!-- Header -->
      <div class="flex items-center gap-3" style="margin-bottom:20px;">
        <button @click="cancel" aria-label="Volver"
          style="width:34px;height:34px;border-radius:10px;background:#161616;border:1px solid rgba(255,255,255,0.08);color:#9CA3AF;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <h1 class="font-display font-bold" style="font-size:22px;letter-spacing:-0.02em;">
          {{ isEditing ? 'Editar rutina' : 'Nueva rutina' }}
        </h1>
      </div>

      <!-- Nombre -->
      <label style="display:block;font-size:12px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Nombre de la rutina</label>
      <input v-model="name" type="text" maxlength="80"
        placeholder="Ej. Torso fuerza, Pierna pesada..."
        style="width:100%;background:#161616;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:14px;color:#fff;font-size:15px;outline:none;caret-color:#1DF412;margin-bottom:24px;"
      />

      <!-- Ejercicios -->
      <div class="flex items-center justify-between" style="margin-bottom:12px;">
        <span style="font-size:12px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Ejercicios ({{ rows.length }})</span>
      </div>

      <div v-if="rows.length === 0"
        style="background:#111;border:1px dashed rgba(255,255,255,0.12);border-radius:14px;padding:28px 16px;text-align:center;color:#6B7280;font-size:13px;margin-bottom:16px;">
        Aún no has añadido ejercicios.
      </div>

      <div v-else class="flex flex-col gap-3" style="margin-bottom:16px;">
        <div v-for="(row, idx) in rows" :key="row.exercise.id"
          style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:14px;">

          <div class="flex items-center gap-3" style="margin-bottom:12px;">
            <div style="width:42px;height:42px;border-radius:9px;overflow:hidden;flex-shrink:0;background:#0A0A0A;display:flex;align-items:center;justify-content:center;">
              <img v-if="row.exercise.gif_url || row.exercise.thumbnail"
                :src="(row.exercise.gif_url ?? row.exercise.thumbnail)!" :alt="row.exercise.name"
                style="width:100%;height:100%;object-fit:cover;" loading="lazy" />
              <span v-else style="font-size:16px;">💪</span>
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ row.exercise.name }}</div>
              <div style="font-size:11px;color:#6B7280;">{{ row.exercise.muscle_group }}</div>
            </div>
            <button @click="removeRow(idx)" aria-label="Quitar ejercicio"
              style="background:transparent;border:none;color:#6B7280;cursor:pointer;padding:4px;flex-shrink:0;"
              onmouseover="this.style.color='#EF4444'" onmouseout="this.style.color='#6B7280'">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
          </div>

          <!-- Series / Reps / Descanso -->
          <div class="grid grid-cols-3 gap-2">
            <div>
              <label style="display:block;font-size:10px;color:#6B7280;font-weight:600;text-transform:uppercase;margin-bottom:4px;">Series</label>
              <input v-model.number="row.sets" type="number" min="1" max="12" inputmode="numeric"
                style="width:100%;background:#0D0D0D;border:1px solid rgba(255,255,255,0.08);border-radius:9px;padding:9px;color:#fff;font-size:14px;text-align:center;outline:none;caret-color:#1DF412;" />
            </div>
            <div>
              <label style="display:block;font-size:10px;color:#6B7280;font-weight:600;text-transform:uppercase;margin-bottom:4px;">Reps</label>
              <input v-model="row.reps" type="text" maxlength="20" placeholder="8-12"
                style="width:100%;background:#0D0D0D;border:1px solid rgba(255,255,255,0.08);border-radius:9px;padding:9px;color:#fff;font-size:14px;text-align:center;outline:none;caret-color:#1DF412;" />
            </div>
            <div>
              <label style="display:block;font-size:10px;color:#6B7280;font-weight:600;text-transform:uppercase;margin-bottom:4px;">Descanso (s)</label>
              <input v-model.number="row.rest_seconds" type="number" min="0" max="600" step="15" inputmode="numeric"
                style="width:100%;background:#0D0D0D;border:1px solid rgba(255,255,255,0.08);border-radius:9px;padding:9px;color:#fff;font-size:14px;text-align:center;outline:none;caret-color:#1DF412;" />
            </div>
          </div>
        </div>
      </div>

      <!-- Añadir ejercicio -->
      <button @click="showSearch = true"
        class="w-full flex items-center justify-center gap-2 font-semibold"
        style="background:#161616;border:1.5px dashed rgba(29,244,18,0.4);border-radius:12px;padding:14px;color:#1DF412;font-size:14px;cursor:pointer;margin-bottom:24px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Añadir ejercicio
      </button>

      <p v-if="errorMsg" style="color:#EF4444;font-size:13px;margin-bottom:12px;text-align:center;">{{ errorMsg }}</p>

      <!-- Guardar -->
      <button @click="save" :disabled="!canSave"
        class="w-full flex items-center justify-center gap-2 font-bold"
        :style="canSave
          ? 'background:#1DF412;color:#000;box-shadow:0 4px 20px rgba(29,244,18,0.25);'
          : 'background:#1A1A1A;color:#6B7280;cursor:not-allowed;'"
        style="border:none;border-radius:14px;padding:16px;font-size:15px;cursor:pointer;">
        {{ saving ? 'Guardando…' : (isEditing ? 'Guardar cambios' : 'Crear rutina') }}
      </button>
    </div>

    <ExerciseSearchModal v-if="showSearch"
      @select="onSelectExercise"
      @close="showSearch = false" />
  </div>
</template>
