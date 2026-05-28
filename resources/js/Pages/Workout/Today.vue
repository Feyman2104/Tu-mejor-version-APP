<script setup lang="ts">
import { ref, computed, reactive, watch, onMounted, onUnmounted, nextTick, inject } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import Sortable from 'sortablejs'
import AppLayout from '@/Layouts/AppLayout.vue'
import ExerciseModal from '@/Components/Workout/ExerciseModal.vue'
import ExerciseSearchModal from '@/Components/Workout/ExerciseSearchModal.vue'
import { useWorkoutSession } from '@/Composables/useWorkoutSession'
import { useToasts } from '@/Composables/useToasts'
import { resolvePostureExercise } from '@/data/postureMap'
import type { Routine, RoutineDay, RoutineExercise, WorkoutLog, Exercise } from '@/types'
import type { CoachOpenParams } from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const openCoach = inject<(params?: CoachOpenParams) => void>('openCoach')
const toast = useToasts()

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps<{
  routine:          Routine | null
  todayDay:         RoutineDay | null
  isRestDay:        boolean
  todayLog:         WorkoutLog | null
  prevSets:         Record<number, { weight_kg: number | null; reps_done: number | null }>
  suggestedWeights?: Record<number, number>
  autostart?:       boolean
  emptyMode?:       boolean
}>()

// ─── Types ────────────────────────────────────────────────────────────────────
type SetType      = 'working' | 'warmup' | 'dropset' | 'failure'
type SupersetType = 'superset' | 'biset' | 'prefatiga'

interface ActiveSet {
  setNumber:    number
  type:         SetType
  weightKg:     string
  repsDone:     string
  completed:    boolean
  savedId:      number | null
  saving:       boolean
  isDropChild?: boolean
}

interface SupersetGroup {
  type:        SupersetType
  exerciseIds: number[]
}

interface SetTypeMeta { label: string; color: string; bg: string; name: string; description: string }

const SET_TYPE_META: Record<SetType, SetTypeMeta> = {
  working: { label: 'T',  color: '#1DF412', bg: 'rgba(29,244,18,0.15)',  name: 'Trabajo',       description: 'Serie estándar con 1–2 RIR. Úsala para el volumen principal.' },
  warmup:  { label: 'WU', color: '#F59E0B', bg: 'rgba(245,158,11,0.15)', name: 'Calentamiento', description: '40–60% 1RM, sin fatiga. No cuenta para el volumen.' },
  dropset: { label: 'DS', color: '#8B5CF6', bg: 'rgba(139,92,246,0.15)', name: 'Drop Set',      description: 'Reduce el peso 20–30% al llegar al fallo y continúa.' },
  failure: { label: 'F',  color: '#EF4444', bg: 'rgba(239,68,68,0.15)',  name: 'Fallo',         description: 'Lleva al fallo muscular. Solo en la última serie del grupo.' },
}

const SUPERSET_META: Record<SupersetType, { name: string; description: string; restSuggested: number }> = {
  superset:  { name: 'Superserie', description: 'Grupos antagonistas (pecho + espalda). Descanso: 60–90s al final.', restSuggested: 75 },
  biset:     { name: 'Biserie',    description: 'Mismo grupo muscular, 2 ejercicios distintos. Descanso: 90–120s.',  restSuggested: 105 },
  prefatiga: { name: 'Pre-fatiga', description: 'Aislamiento antes de compuesto. Maximiza fatiga muscular. 90s.',    restSuggested: 90 },
}

// ─── Unidad de peso (kg / lbs) ────────────────────────────────────────────────
type WeightUnit = 'kg' | 'lbs'
const KG_TO_LBS = 2.20462
const unit      = ref<WeightUnit>(
  (localStorage.getItem('workout_unit') as WeightUnit | null) ?? 'kg'
)

function kgToDisplay(kg: number | string | null, u: WeightUnit): string {
  const n = typeof kg === 'string' ? parseFloat(kg) : (kg ?? NaN)
  if (isNaN(n)) return ''
  return u === 'lbs' ? (n * KG_TO_LBS).toFixed(1) : n.toFixed(1)
}

function displayToKg(display: string, u: WeightUnit): string {
  const n = parseFloat(display)
  if (isNaN(n)) return ''
  return u === 'lbs' ? (n / KG_TO_LBS).toFixed(2) : n.toFixed(2)
}

function setUnit(newUnit: WeightUnit): void {
  if (newUnit === unit.value) return
  for (const sets of Object.values(exerciseSets)) {
    for (const set of sets) {
      if (!set.weightKg) continue
      const asKg = displayToKg(set.weightKg, unit.value)
      set.weightKg = kgToDisplay(asKg, newUnit)
    }
  }
  unit.value = newUnit
  localStorage.setItem('workout_unit', newUnit)
}

function fmtAnterior(exerciseId: number): string {
  const p = props.prevSets[exerciseId]
  if (!p || p.weight_kg == null || p.reps_done == null) return '—'
  return `${kgToDisplay(p.weight_kg, unit.value)} × ${p.reps_done}`
}

// ─── Selector de tipo de serie ────────────────────────────────────────────────
const activePicker = ref<{ reId: number; idx: number; x: number; y: number } | null>(null)

const pickerSet = computed(() =>
  activePicker.value
    ? (exerciseSets[activePicker.value.reId]?.[activePicker.value.idx] ?? null)
    : null
)

function openPicker(reId: number, idx: number, e: MouseEvent): void {
  e.stopPropagation()
  if (activePicker.value?.reId === reId && activePicker.value?.idx === idx) {
    activePicker.value = null; return
  }
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  activePicker.value = {
    reId, idx,
    x: Math.min(rect.left, window.innerWidth - 274),
    y: Math.min(rect.bottom + 6, window.innerHeight - 290),
  }
}

function selectType(type: SetType): void {
  if (!activePicker.value) return
  const { reId, idx } = activePicker.value
  exerciseSets[reId][idx].type = type
  activePicker.value = null
  // Drop set: ofrecer agregar series si no hay drops debajo ya
  if (type === 'dropset') {
    const next = exerciseSets[reId]?.[idx + 1]
    if (!next || next.type !== 'dropset') {
      dropCount.value = 2
      dropDialog.value = { reId, idx }
    }
  }
}

function deleteSetFromPicker(): void {
  if (!activePicker.value) return
  const { reId, idx } = activePicker.value
  const re = allExercises.value.find(e => e.id === reId)
  if (re) deleteSet(re, idx)
  activePicker.value = null
}

function onDocKeyDown(e: KeyboardEvent): void {
  if (e.key === 'Escape') {
    activePicker.value    = null
    restPickerRe.value    = null
    dropDialog.value      = null
    activeMenu.value      = null
    linkingSource.value   = null
    deleteConfirmRe.value = null
  }
}

function onDocClickCapture(e: MouseEvent): void {
  const t = e.target as Element
  if (!t.closest('.type-picker'))    activePicker.value  = null
  if (!t.closest('.exercise-menu'))  activeMenu.value    = null
  if (!t.closest('.rest-sheet') && !t.closest('.rest-trigger')) restPickerRe.value = null
}

// ─── Descanso editable por ejercicio ─────────────────────────────────────────
const restOverrides = reactive<Record<number, number>>({})

function effectiveRest(re: RoutineExercise): number {
  return restOverrides[re.id] ?? re.rest_seconds ?? 60
}

function restLabel(re: RoutineExercise): string {
  const s = effectiveRest(re)
  if (s === 0) return 'APAGADO'
  const m   = Math.floor(s / 60)
  const sec = s % 60
  return m > 0 ? `${m}:${String(sec).padStart(2, '0')} descanso` : `${s}s descanso`
}

// ─── Bottom sheet picker de descanso ─────────────────────────────────────────
const restPickerRe = ref<RoutineExercise | null>(null)
const REST_OPTIONS = [0, ...Array.from({ length: 60 }, (_, i) => (i + 1) * 5)]

function openRestPicker(re: RoutineExercise): void {
  restPickerRe.value = re
}
function closeRestPicker(): void {
  restPickerRe.value = null
}
function selectRestTime(seconds: number): void {
  if (!restPickerRe.value) return
  restOverrides[restPickerRe.value.id] = seconds
  closeRestPicker()
}

// ─── Drop set dialog ──────────────────────────────────────────────────────────
const dropDialog = ref<{ reId: number; idx: number } | null>(null)
const dropCount  = ref(2)

function confirmDropSets(): void {
  if (!dropDialog.value) return
  const { reId, idx } = dropDialog.value
  const sets = exerciseSets[reId]
  const base = sets[idx]
  const newSets: ActiveSet[] = Array.from({ length: dropCount.value }, () => ({
    setNumber:   0,
    type:        'dropset' as SetType,
    weightKg:    base.weightKg,
    repsDone:    base.repsDone,
    completed:   false,
    savedId:     null,
    saving:      false,
    isDropChild: true,
  }))
  sets.splice(idx + 1, 0, ...newSets)
  sets.forEach((s, i) => { s.setNumber = i + 1 })
  dropDialog.value = null
}

function deleteSet(re: RoutineExercise, idx: number): void {
  exerciseSets[re.id].splice(idx, 1)
  exerciseSets[re.id].forEach((s, i) => { s.setNumber = i + 1 })
}

// ─── Menú ⋮ por ejercicio ─────────────────────────────────────────────────────
const activeMenu  = ref<number | null>(null)
const menuCoords  = ref({ x: 0, y: 0 })
const reorderMode = ref(false)

// Colapsa las tarjetas mientras se arrastra para que el reordenamiento funcione
// aunque la tabla de series esté abierta (un card alto rompe el drag de SortableJS).
const collapsedForDrag = ref(false)
let pressTimer: ReturnType<typeof setTimeout> | null = null

function startDragPress(): void {
  if (pressTimer) clearTimeout(pressTimer)
  // Colapsa justo antes de que SortableJS inicie el drag (su delay es 300 ms),
  // así el clon "fantasma" se genera ya en tamaño compacto.
  pressTimer = setTimeout(() => { collapsedForDrag.value = true }, 250)
}

function endDragPress(): void {
  if (pressTimer) { clearTimeout(pressTimer); pressTimer = null }
  collapsedForDrag.value = false
}

function isExpanded(re: RoutineExercise): boolean {
  if (collapsedForDrag.value || reorderMode.value) return false
  return !!exerciseSets[re.id] && (exerciseStatus(re) !== 'pending' || re.id === activeReId.value)
}

function toggleMenu(reId: number, e: MouseEvent): void {
  e.stopPropagation()
  if (activeMenu.value === reId) { activeMenu.value = null; return }
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  menuCoords.value = {
    x: Math.min(rect.right - 180, window.innerWidth - 196),
    y: Math.min(rect.bottom + 4,  window.innerHeight - 200),
  }
  activeMenu.value = reId
}

// ─── Reemplazar ejercicio ─────────────────────────────────────────────────────
const replacingRe = ref<RoutineExercise | null>(null)

function handleReplaceExercise(newEx: Exercise): void {
  if (!replacingRe.value) return
  const day = selectedDay.value
  if (day) {
    const found = day.exercises.find(e => e.id === replacingRe.value!.id)
    if (found) { found.exercise = newEx; found.exercise_id = newEx.id }
  }
  const extra = extraExercises.value.find(e => e.id === replacingRe.value!.id)
  if (extra) { extra.exercise = newEx; extra.exercise_id = newEx.id }
  const oldId = replacingRe.value.id
  exerciseSets[oldId] = Array.from({ length: replacingRe.value.sets ?? 3 }, (_, i) => ({
    setNumber: i + 1, type: 'working' as SetType,
    weightKg: '', repsDone: '', completed: false, savedId: null, saving: false,
  }))
  replacingRe.value  = null
  activeMenu.value   = null
}

// ─── Superseries / Biseries ───────────────────────────────────────────────────
const supersetGroups = reactive<Record<number, SupersetGroup>>({})
const linkingSource  = ref<RoutineExercise | null>(null)
const linkingType    = ref<SupersetType>('superset')

function findSupersetGroup(reId: number): SupersetGroup | null {
  const group = supersetGroups[reId]
  return group ?? null
}

function startLinking(re: RoutineExercise): void {
  linkingSource.value = re
  activeMenu.value    = null
}

function confirmLink(targetRe: RoutineExercise): void {
  if (!linkingSource.value || linkingSource.value.id === targetRe.id) return
  const group: SupersetGroup = {
    type:        linkingType.value,
    exerciseIds: [linkingSource.value.id, targetRe.id],
  }
  supersetGroups[linkingSource.value.id] = group
  supersetGroups[targetRe.id]            = group
  linkingSource.value = null
}

function removeFromSuperset(reId: number): void {
  const group = findSupersetGroup(reId)
  if (!group) return
  group.exerciseIds.forEach(id => { delete supersetGroups[id] })
}

// ─── Eliminar ejercicio ─────────────────────────────────────────────────────────
const deleteConfirmRe = ref<RoutineExercise | null>(null)

function askDeleteExercise(reId: number): void {
  deleteConfirmRe.value = allExercises.value.find(e => e.id === reId) ?? null
  activeMenu.value = null
}

function confirmDeleteExercise(): void {
  const re = deleteConfirmRe.value
  if (!re) return
  if (supersetGroups[re.id]) removeFromSuperset(re.id)
  delete exerciseSets[re.id]
  if (activeReId.value === re.id) activeReId.value = null

  if (re.id < 0) {
    // Ejercicio extra de la sesión: solo local
    extraExercises.value = extraExercises.value.filter(e => e.id !== re.id)
  } else {
    // Ejercicio de la rutina: quitar de la vista y desactivar en BD (reversible)
    if (selectedDay.value) {
      selectedDay.value.exercises = selectedDay.value.exercises.filter(e => e.id !== re.id)
    }
    fetch(route('routine.exercises.deactivate', { routineExercise: re.id }), {
      method:      'PATCH',
      credentials: 'same-origin',
      headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
    }).catch(() => { /* UI optimista, igual que saveExerciseOrder */ })
  }
  deleteConfirmRe.value = null
}

// ─── Notas por ejercicio (solo sesión) ───────────────────────────────────────
const exerciseNotes = reactive<Record<number, string>>({})

// ─── State ────────────────────────────────────────────────────────────────────
const activeLog      = ref<WorkoutLog | null>(props.todayLog)
const logCreating    = ref(false)
const completing     = ref(false)
const selectedDay    = ref<RoutineDay | null>(props.todayDay ?? props.routine?.days?.[0] ?? null)
const showDayPicker  = ref(false)
const activeReId     = ref<number | null>(null)
const mood           = ref<string | null>(null)
const sessionNotes   = ref('')
const modalExercise  = ref<Exercise | null>(null)
const showExSearch   = ref(false)

// Ejercicios extra añadidos durante la sesión (no forman parte de la rutina original)
// IDs negativos para distinguirlos de los routine_exercises reales.
const extraExercises = ref<RoutineExercise[]>([])
let extraIdCounter   = -1

function addExtraExercise(ex: Exercise) {
  const id = extraIdCounter--
  const fakeRe: RoutineExercise = {
    id,
    routine_day_id: selectedDay.value?.id ?? 0,
    exercise_id:    ex.id,
    exercise:       ex,
    sets:           3,
    reps:           '10',
    rest_seconds:   60,
    rir:            null,
    order:          999,
  }
  extraExercises.value.push(fakeRe)
  exerciseSets[id] = Array.from({ length: 3 }, (_, i) => ({
    setNumber: i + 1,
    type:      'working' as SetType,
    weightKg:  '',
    repsDone:  '10',
    completed: false,
    savedId:   null,
    saving:    false,
  }))
  activeReId.value = id
}

// Rest timer
const restSeconds  = ref(0)
const restRunning  = ref(false)
let restInterval: ReturnType<typeof setInterval> | null = null

// Elapsed time
const elapsedSeconds = ref(0)
let elapsedInterval: ReturnType<typeof setInterval> | null = null

// Sets por routine_exercise_id
const exerciseSets = reactive<Record<number, ActiveSet[]>>({})

// ─── Drag-to-reorder ejercicios ───────────────────────────────────────────────
let sortableInstance: Sortable | null = null
const exerciseListRef = ref<HTMLElement | null>(null)

function initSortable(): void {
  nextTick(() => {
    if (!exerciseListRef.value) return
    if (sortableInstance) { sortableInstance.destroy(); sortableInstance = null }
    sortableInstance = Sortable.create(exerciseListRef.value, {
      handle:            '.drag-handle',
      animation:         150,
      delay:             300,          // long-press para iniciar drag
      delayOnTouchOnly:  true,         // solo en móvil
      onStart() {
        collapsedForDrag.value = true
      },
      onEnd(evt) {
        collapsedForDrag.value = false
        const oldIdx = evt.oldIndex
        const newIdx = evt.newIndex
        if (oldIdx === undefined || newIdx === undefined || oldIdx === newIdx) return
        const dayExs   = [...(selectedDay.value?.exercises ?? [])]
        const extraExs = [...extraExercises.value]
        const all      = [...dayExs, ...extraExs]
        const [moved]  = all.splice(oldIdx, 1)
        all.splice(newIdx, 0, moved)
        const dayLen = dayExs.length
        if (selectedDay.value) selectedDay.value.exercises = all.slice(0, dayLen)
        extraExercises.value = all.slice(dayLen)
        saveExerciseOrder()
      },
    })
  })
}

async function saveExerciseOrder(): Promise<void> {
  if (!selectedDay.value) return
  const order = selectedDay.value.exercises.filter(e => e.id > 0).map(e => e.id)
  if (!order.length) return
  try {
    await fetch(route('routine.exercises.reorder'), {
      method:      'PATCH',
      credentials: 'same-origin',
      headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body:        JSON.stringify({ order }),
    })
  } catch { /* silent fail */ }
}

function csrf(): string {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
}

// ─── Init sets ────────────────────────────────────────────────────────────────
function initSets(day: RoutineDay | null) {
  if (!day) return
  day.exercises.forEach((re) => {
    if (exerciseSets[re.id]) return
    const prev        = props.prevSets[re.exercise.id]
    const suggested   = props.suggestedWeights?.[re.exercise.id]
    const defaultReps = (re.reps ?? '').split(/[-x]/)[0].trim()
    // Use previous weight, or suggested weight (marked as hint), or empty
    const weightKg = prev?.weight_kg != null
      ? kgToDisplay(prev.weight_kg, unit.value)
      : suggested != null
        ? kgToDisplay(suggested, unit.value)
        : ''
    exerciseSets[re.id] = Array.from({ length: re.sets ?? 3 }, (_, i) => ({
      setNumber: i + 1,
      type:      'working' as SetType,
      weightKg,
      repsDone:  defaultReps || '',
      completed: false,
      savedId:   null,
      saving:    false,
    }))
  })
}

// ─── Contexto para el Coach IA ───────────────────────────────────────────────
const workoutSession = useWorkoutSession()

// watch de workoutSession → se registra después de los computeds (ver abajo)

onMounted(() => {
  initSets(selectedDay.value)
  if (activeLog.value && !activeLog.value.completed) {
    startElapsed()
    setFirstActiveExercise()
  } else if (props.autostart && !activeLog.value) {
    // "Empezar Rutina" desde la pantalla de rutinas: iniciar sesión automáticamente
    startWorkout()
  }
  initSortable()
  document.addEventListener('keydown', onDocKeyDown)
  document.addEventListener('click', onDocClickCapture, true)
  document.addEventListener('pointerup', endDragPress)
  document.addEventListener('touchend', endDragPress)
  document.addEventListener('pointercancel', endDragPress)
})

onUnmounted(() => {
  if (restInterval)    clearInterval(restInterval)
  if (elapsedInterval) clearInterval(elapsedInterval)
  if (pressTimer)      clearTimeout(pressTimer)
  if (sortableInstance) { sortableInstance.destroy(); sortableInstance = null }
  workoutSession.clear()
  document.removeEventListener('keydown', onDocKeyDown)
  document.removeEventListener('click', onDocClickCapture, true)
  document.removeEventListener('pointerup', endDragPress)
  document.removeEventListener('touchend', endDragPress)
  document.removeEventListener('pointercancel', endDragPress)
})

function setFirstActiveExercise() {
  const day = selectedDay.value
  if (!day) return
  for (const re of day.exercises) {
    const sets = exerciseSets[re.id]
    if (sets && sets.some(s => !s.completed)) {
      activeReId.value = re.id
      return
    }
  }
}

function startElapsed() {
  if (elapsedInterval) clearInterval(elapsedInterval)
  elapsedInterval = setInterval(() => { elapsedSeconds.value++ }, 1000)
}

// ─── Rest Timer ───────────────────────────────────────────────────────────────
function startRestTimer(seconds: number) {
  if (restInterval) clearInterval(restInterval)
  restSeconds.value = seconds
  restRunning.value = true
  restInterval = setInterval(() => {
    if (restSeconds.value <= 0) {
      stopRestTimer()
      toast.info('¡Tiempo de descanso terminado! Empieza la siguiente serie.', { duration: 5000 })
      return
    }
    restSeconds.value--
  }, 1000)
}

function stopRestTimer() {
  if (restInterval) clearInterval(restInterval)
  restInterval    = null
  restRunning.value = false
  restSeconds.value = 0
}

function adjustRestTimer(delta: number) {
  if (!restRunning.value) return
  restSeconds.value = Math.max(5, restSeconds.value + delta)
}

// ─── Day select ───────────────────────────────────────────────────────────────
function selectDay(day: RoutineDay) {
  selectedDay.value   = day
  showDayPicker.value = false
  initSets(day)
}

// ─── Computed ─────────────────────────────────────────────────────────────────
// Todos los ejercicios visibles (rutina del día + extras añadidos en sesión)
const allExercises = computed<RoutineExercise[]>(() => [
  ...(selectedDay.value?.exercises ?? []),
  ...extraExercises.value,
])

const totalSets = computed(() =>
  allExercises.value.reduce((sum, re) =>
    sum + (exerciseSets[re.id]?.length ?? re.sets ?? 0), 0)
)

const completedCount = computed(() =>
  Object.values(exerciseSets).reduce((sum, sets) => sum + sets.filter(s => s.completed).length, 0)
)

const progress = computed(() =>
  totalSets.value > 0 ? (completedCount.value / totalSets.value) * 100 : 0
)

const allDone = computed(() =>
  totalSets.value > 0 && completedCount.value >= totalSets.value
)

const totalVolume = computed(() =>
  Object.values(exerciseSets).reduce((sum, sets) =>
    sum + sets.filter(s => s.completed).reduce((s2, set) =>
      s2 + (parseFloat(set.weightKg) || 0) * (parseInt(set.repsDone) || 0), 0), 0)
)

const completedExercises = computed(() => {
  return allExercises.value.filter(re => {
    const sets = exerciseSets[re.id]
    return sets && sets.length > 0 && sets.every(s => s.completed)
  }).length
})

const elapsedFormatted = computed(() => {
  const m = Math.floor(elapsedSeconds.value / 60)
  const s = elapsedSeconds.value % 60
  return `${m}:${String(s).padStart(2, '0')}`
})

const restFormatted = computed(() => {
  const m = Math.floor(restSeconds.value / 60)
  const s = restSeconds.value % 60
  return `${m}:${String(s).padStart(2, '0')}`
})

// ─── Watch Coach IA — DESPUÉS de los computeds para evitar TDZ ───────────────
watch(
  () => ({
    log:       activeLog.value,
    exercises: allExercises.value,
    sets:      { ...exerciseSets },
    done:      completedCount.value,
    total:     totalSets.value,
    vol:       totalVolume.value,
    elapsed:   elapsedSeconds.value,
  }),
  ({ log, exercises }) => {
    if (!log || log.completed) {
      workoutSession.clear()
      return
    }
    workoutSession.update({
      active:         true,
      dayFocus:       selectedDay.value?.focus ?? 'general',
      exercises:      exercises.map(re => {
        const sets     = exerciseSets[re.id] ?? []
        const done     = sets.filter(s => s.completed)
        const lastDone = done.at(-1)
        const lastSet  = lastDone
          ? [
              lastDone.weightKg ? `${lastDone.weightKg} kg` : null,
              lastDone.repsDone ? `${lastDone.repsDone} reps` : null,
            ].filter(Boolean).join(' × ') || null
          : null
        return {
          name:      re.exercise?.name ?? '',
          setsDone:  done.length,
          setsTotal: sets.length,
          lastSet,
        }
      }),
      completedSets:  completedCount.value,
      totalSets:      totalSets.value,
      volumeKg:       totalVolume.value,
      elapsedMinutes: Math.floor(elapsedSeconds.value / 60),
    })
  },
  { immediate: true, deep: false },
)

// ─── Exercise status ──────────────────────────────────────────────────────────
function exerciseStatus(re: RoutineExercise): 'done' | 'active' | 'pending' {
  const sets = exerciseSets[re.id]
  if (!sets) return 'pending'
  if (sets.every(s => s.completed)) return 'done'
  if (re.id === activeReId.value || sets.some(s => s.completed)) return 'active'
  return 'pending'
}

// ─── PR detection ─────────────────────────────────────────────────────────────
function hasPR(re: RoutineExercise): boolean {
  const prev = props.prevSets[re.exercise.id]
  if (!prev?.weight_kg) return false
  const sets = exerciseSets[re.id]
  if (!sets) return false
  return sets.some(s => s.completed && parseFloat(s.weightKg) > (prev.weight_kg ?? 0))
}

// ─── Actions ──────────────────────────────────────────────────────────────────
async function startWorkout() {
  if (logCreating.value) return
  if (!selectedDay.value && !props.emptyMode) return
  logCreating.value = true
  try {
    const res  = await fetch(route('workout.logs.store'), {
      method:      'POST',
      credentials: 'same-origin',
      headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body:        JSON.stringify({ routine_day_id: selectedDay.value?.id ?? null }),
    })
    const data = await res.json()
    activeLog.value = data.log
    startElapsed()
    setFirstActiveExercise()
  } finally {
    logCreating.value = false
  }
}

async function toggleSet(re: RoutineExercise, idx: number) {
  if (!activeLog.value) return
  const set = exerciseSets[re.id]?.[idx]
  if (!set) return

  // Desmarcar — solo UI
  if (set.completed) { set.completed = false; return }

  if (!set.repsDone || parseInt(set.repsDone) <= 0) {
    set.repsDone = re.reps?.split(/[-x]/)[0].trim() || '1'
  }

  set.saving    = true
  set.completed = true
  activeReId.value = re.id

  // Toast de progresión: solo en la 1ª serie del ejercicio y si hay sugerencia diferente al actual
  if (idx === 0) {
    const suggested = props.suggestedWeights?.[re.exercise?.id ?? 0]
    const current   = parseFloat(set.weightKg)
    if (suggested && suggested > current) {
      toast.info(`Sugerencia: podrías subir a ${suggested} kg en este ejercicio.`, { duration: 6000 })
    }
  }

  // Iniciar timer de descanso (con lógica de superserie)
  const ssGroup = findSupersetGroup(re.id)
  if (ssGroup) {
    const myPos    = ssGroup.exerciseIds.indexOf(re.id)
    const nextReId = ssGroup.exerciseIds[myPos + 1]
    const nextPending = nextReId ? exerciseSets[nextReId]?.some(s => !s.completed) : false
    if (nextReId && nextPending) {
      activeReId.value = nextReId   // flujo automático al siguiente ejercicio
    } else {
      startRestTimer(SUPERSET_META[ssGroup.type].restSuggested)
    }
  } else if (re.rest_seconds || restOverrides[re.id]) {
    startRestTimer(effectiveRest(re))
  }

  try {
    const res = await fetch(route('workout.sets.store'), {
      method:      'POST',
      credentials: 'same-origin',
      headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body:        JSON.stringify({
        workout_log_id:      activeLog.value.id,
        // IDs negativos = ejercicios extra añadidos en sesión (sin routine_exercise)
        routine_exercise_id: re.id > 0 ? re.id : null,
        set_number:          set.setNumber,
        reps_done:           parseInt(set.repsDone) || null,
        weight_kg:           set.weightKg ? (parseFloat(displayToKg(set.weightKg, unit.value)) || null) : null,
      }),
    })
    if (res.ok) {
      const data  = await res.json()
      set.savedId = data.set?.id ?? null
    }
  } catch {
    // Rollback optimista: desmarcar la serie
    set.completed = false
    toast.error('No se pudo guardar la serie. Verifica tu conexión.')
  } finally {
    set.saving = false
  }
}

function addSet(re: RoutineExercise) {
  const sets = exerciseSets[re.id]
  if (!sets) return
  const last = sets[sets.length - 1]
  sets.push({
    setNumber: sets.length + 1,
    type:      last?.type ?? 'working',
    weightKg:  last?.weightKg ?? '',
    repsDone:  last?.repsDone ?? '',
    completed: false,
    savedId:   null,
    saving:    false,
  })
}

function removeLastSet(re: RoutineExercise) {
  const sets = exerciseSets[re.id]
  if (!sets || sets.length <= 1) return
  if (!sets[sets.length - 1].completed) sets.pop()
}

async function discardWorkout() {
  if (!activeLog.value || completing.value) return
  if (!confirm('¿Descartar esta sesión? El progreso no se guardará.')) return
  completing.value = true
  stopRestTimer()
  if (elapsedInterval) clearInterval(elapsedInterval)
  router.delete(
    route('workout.logs.destroy', { workoutLog: activeLog.value.id }),
    { onFinish: () => { completing.value = false } }
  )
}

async function completeWorkout() {
  if (!activeLog.value || completing.value) return
  completing.value = true
  stopRestTimer()
  if (elapsedInterval) clearInterval(elapsedInterval)
  const durationMinutes = Math.max(1, Math.round(elapsedSeconds.value / 60))
  router.patch(
    route('workout.logs.complete', { workoutLog: activeLog.value.id }),
    { duration_minutes: durationMinutes },
    { onFinish: () => { completing.value = false } }
  )
}

// ─── Analiza mi técnica ───────────────────────────────────────────────────────
function openTechniqueCoach(re: RoutineExercise) {
  const name = re.exercise?.name ?? ''
  const postureId = resolvePostureExercise(name)
  if (openCoach) {
    openCoach({ tab: 'posture', exercise: postureId, mode: 'live' })
  }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function prevLabel(exId: number): string {
  const p = props.prevSets[exId]
  if (!p) return 'Primera vez'
  const parts: string[] = []
  if (p.weight_kg) parts.push(`${p.weight_kg}kg`)
  if (p.reps_done) parts.push(`× ${p.reps_done}`)
  return parts.length ? parts.join(' ') : '—'
}

function focusColor(focus: string): string {
  const map: Record<string, string> = {
    push: '#F59E0B', pull: '#3B82F6', legs: '#1DF412',
    full_body: '#8B5CF6', cardio: '#EF4444', rest: '#6B7280',
  }
  return map[focus] ?? '#9CA3AF'
}

const MOODS = [
  { id: 'great', label: 'Excelente', emoji: '💪' },
  { id: 'good',  label: 'Bien',      emoji: '😊' },
  { id: 'ok',    label: 'Normal',    emoji: '😐' },
  { id: 'bad',   label: 'Difícil',   emoji: '😓' },
  { id: 'awful', label: 'Mal',       emoji: '😩' },
]

// ─── Título de sesión ─────────────────────────────────────────────────────────
const routineTitle = computed(() =>
  props.routine?.name ?? (props.emptyMode ? 'Entrenamiento libre' : 'Sin rutina')
)

// ─── Badges de lesión ─────────────────────────────────────────────────────────
const page = usePage()

// Mapeo zona ES (contraindications) → EN (user.injuries)
const ZONE_MAP: Record<string, string> = {
  hombro:  'shoulder',
  rodilla: 'knee',
  lumbar:  'back',
  espalda: 'back',
  muneca:  'wrist',
  muñeca:  'wrist',
  cadera:  'hip',
  tobillo: 'ankle',
  cuello:  'neck',
}

function injuryZones(): string[] {
  const user = page.props.auth?.user
  if (!user?.injuries) return []
  return (user.injuries as { zone: string }[]).map(i => i.zone.toLowerCase())
}

function injuryWarning(re: RoutineExercise): string | null {
  if (!re.exercise?.contraindications?.length) return null
  const userZones = injuryZones()
  if (!userZones.length) return null
  for (const c of re.exercise.contraindications) {
    const zoneEs = c.body_zone?.toLowerCase() ?? ''
    const zoneEn = ZONE_MAP[zoneEs] ?? zoneEs
    if (userZones.includes(zoneEn)) {
      return zoneEs.charAt(0).toUpperCase() + zoneEs.slice(1)
    }
  }
  return null
}
</script>

<template>
  <div class="min-h-screen" style="background:#000;color:#fff;">
    <div class="px-4 md:px-8 py-6 max-w-2xl mx-auto pb-32">

      <!-- Sin rutina ─────────────────────────────────────────────────────────── -->
      <div v-if="!routine && !emptyMode" class="flex flex-col items-center text-center py-20">
        <div style="width:72px;height:72px;border-radius:18px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/>
            <path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/>
          </svg>
        </div>
        <h1 class="font-display font-bold" style="font-size:26px;margin-bottom:8px;">Sin rutina asignada</h1>
        <p style="font-size:14px;color:#9CA3AF;line-height:1.6;margin-bottom:24px;">
          Habla con el Coach IA para generar tu plan personalizado.
        </p>
        <Link :href="route('chat.index')"
          class="inline-flex items-center gap-2 font-bold"
          style="background:#1DF412;color:#000;border-radius:12px;padding:14px 24px;font-size:15px;text-decoration:none;">
          Generar rutina con Coach IA
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </Link>
      </div>

      <!-- Con rutina o modo vacío ────────────────────────────────────────────── -->
      <template v-else>

        <!-- Header ──────────────────────────────────────────────────────────── -->
        <div style="margin-bottom:20px;">
          <div style="font-size:11px;color:#1DF412;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;margin-bottom:6px;">
            {{ isRestDay && !activeLog ? 'Día de descanso' : 'Entrenamiento de hoy' }}
          </div>
          <div class="flex items-center justify-between" style="margin-bottom:6px;">
            <h1 class="font-display font-bold" style="font-size:24px;letter-spacing:-0.02em;">
              {{ routineTitle }}
            </h1>
            <!-- Elapsed time (only when log active) -->
            <div v-if="activeLog && !activeLog.completed"
              style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:5px 12px;text-align:center;min-width:72px;">
              <div style="font-size:9px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:1px;">⏱ Tiempo</div>
              <div class="font-display font-bold" style="font-size:22px;color:#fff;font-variant-numeric:tabular-nums;letter-spacing:-0.02em;line-height:1;">{{ elapsedFormatted }}</div>
            </div>
          </div>

          <!-- Selector de día (oculto en modo vacío o cuando hay un solo día) -->
          <button v-if="selectedDay && routine && routine.days && routine.days.length > 1" @click="showDayPicker = !showDayPicker"
            class="flex items-center gap-2"
            style="background:none;border:none;cursor:pointer;padding:0;color:#9CA3AF;font-size:13px;">
            <span :style="{ color: focusColor(selectedDay.focus) }">●</span>
            {{ selectedDay.name }}
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
              :style="showDayPicker ? 'transform:rotate(180deg);' : ''">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
            <span v-if="isRestDay && !activeLog" style="font-size:11px;background:rgba(107,114,128,0.15);color:#6B7280;border-radius:6px;padding:2px 7px;margin-left:4px;">
              día libre
            </span>
          </button>

          <!-- Dropdown de días -->
          <div v-if="showDayPicker"
            style="margin-top:8px;background:#161616;border:1px solid rgba(255,255,255,0.08);border-radius:12px;overflow:hidden;z-index:10;position:relative;">
            <button v-for="day in routine.days" :key="day.id"
              @click="selectDay(day)"
              class="flex items-center gap-3 w-full text-left"
              style="padding:12px 16px;background:none;border:none;border-bottom:1px solid rgba(255,255,255,0.05);cursor:pointer;color:#fff;font-size:14px;"
              :style="selectedDay?.id === day.id ? 'background:rgba(29,244,18,0.06);' : ''">
              <span :style="{ color: focusColor(day.focus) }">●</span>
              {{ day.name }}
              <svg v-if="selectedDay?.id === day.id" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left:auto;"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
          </div>
        </div>

        <!-- Stats row (solo cuando hay log activo) ───────────────────────────── -->
        <div v-if="activeLog && !activeLog.completed"
          style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px;">
          <div v-for="stat in [
            { label: 'Ejercicios', value: completedExercises + '/' + allExercises.length },
            { label: 'Series',     value: completedCount + '/' + totalSets },
            { label: 'Volumen',    value: totalVolume > 0 ? Math.round(totalVolume) + 'kg' : '—' },
          ]" :key="stat.label"
            style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:12px;text-align:center;">
            <div class="font-display font-bold" style="font-size:18px;color:#fff;margin-bottom:2px;">
              {{ stat.value }}
            </div>
            <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">
              {{ stat.label }}
            </div>
          </div>
        </div>

        <!-- Barra de progreso ────────────────────────────────────────────────── -->
        <div v-if="activeLog && !activeLog.completed" style="margin-bottom:16px;">
          <div class="flex justify-between items-center" style="margin-bottom:6px;">
            <span style="font-size:12px;color:#9CA3AF;">Progreso de sesión</span>
            <span class="font-display font-bold" style="font-size:13px;color:#1DF412;">
              {{ Math.round(progress) }}%
            </span>
          </div>
          <div style="height:6px;background:#1A1A1A;border-radius:3px;overflow:hidden;">
            <div style="height:100%;border-radius:3px;background:#1DF412;box-shadow:0 0 10px rgba(29,244,18,0.4);transition:width 0.4s ease;"
              :style="{ width: progress + '%' }"/>
          </div>
        </div>

        <!-- Rest timer banner ───────────────────────────────────────────────── -->
        <div v-if="restRunning"
          style="background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);border-radius:14px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:center;gap:14px;">
          <div style="width:48px;height:48px;border-radius:12px;background:#1DF412;color:#000;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 20px rgba(29,244,18,0.35);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div style="flex:1;">
            <div style="font-size:10px;color:#1DF412;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:2px;">DESCANSO</div>
            <div class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;line-height:1;font-variant-numeric:tabular-nums;">
              {{ restFormatted }}
            </div>
          </div>
          <button @click="adjustRestTimer(-5)"
            style="background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#9CA3AF;cursor:pointer;padding:8px 12px;font-size:12px;font-weight:600;">
            −5s
          </button>
          <button @click="stopRestTimer"
            style="background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#9CA3AF;cursor:pointer;padding:8px 12px;font-size:12px;font-weight:600;">
            Saltar
          </button>
          <button @click="adjustRestTimer(5)"
            style="background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#9CA3AF;cursor:pointer;padding:8px 12px;font-size:12px;font-weight:600;">
            +5s
          </button>
        </div>

        <!-- Botón empezar ───────────────────────────────────────────────────── -->
        <div v-if="!activeLog && (selectedDay || emptyMode)" style="margin-bottom:24px;">
          <button @click="startWorkout" :disabled="logCreating"
            class="w-full flex items-center justify-center gap-2 font-bold"
            style="background:#1DF412;color:#000;border:none;border-radius:14px;padding:16px 24px;font-size:16px;cursor:pointer;box-shadow:0 4px 24px rgba(29,244,18,0.25);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            {{ logCreating ? 'Iniciando...' : 'Empezar entrenamiento' }}
          </button>
        </div>

        <!-- Lista de ejercicios ─────────────────────────────────────────────── -->
        <!-- Banner de modo reorden -->
        <div v-if="reorderMode" style="display:flex;align-items:center;justify-content:space-between;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.25);border-radius:12px;padding:10px 14px;margin-bottom:10px;">
          <span style="font-size:12px;color:#3B82F6;font-weight:600;">↕ Arrastra los ejercicios para reordenar</span>
          <button @click="reorderMode = false" style="background:none;border:none;color:#3B82F6;font-size:12px;font-weight:700;cursor:pointer;">Listo</button>
        </div>
        <!-- Banner de modo vinculación superset -->
        <div v-if="linkingSource" style="display:flex;align-items:center;justify-content:space-between;background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.25);border-radius:12px;padding:10px 14px;margin-bottom:10px;">
          <div>
            <div style="font-size:12px;color:#8B5CF6;font-weight:600;">Selecciona el tipo de vinculación:</div>
            <div style="display:flex;gap:6px;margin-top:6px;">
              <button v-for="st in (['superset','biset','prefatiga'] as SupersetType[])" :key="st"
                @click="linkingType = st"
                style="font-size:10px;font-weight:700;border-radius:6px;padding:3px 8px;cursor:pointer;border:1.5px solid;"
                :style="linkingType === st ? 'background:rgba(139,92,246,0.2);border-color:#8B5CF6;color:#8B5CF6;' : 'background:transparent;border-color:rgba(139,92,246,0.2);color:#6B7280;'">
                {{ SUPERSET_META[st].name }}
              </button>
            </div>
            <div style="font-size:11px;color:#6B7280;margin-top:4px;">
              Vinculando: <strong style="color:#8B5CF6;">{{ linkingSource.exercise?.name }}</strong> → toca otro ejercicio
            </div>
          </div>
          <button @click="linkingSource = null" style="background:none;border:none;color:#6B7280;font-size:18px;cursor:pointer;">×</button>
        </div>

        <div v-if="selectedDay || emptyMode" ref="exerciseListRef" class="space-y-3">

          <div v-for="re in allExercises" :key="re.id"
            style="border-radius:18px;overflow:hidden;transition:all 0.2s;"
            :style="[
              exerciseStatus(re) === 'active'
                ? 'background:rgba(29,244,18,0.04);border:1px solid rgba(29,244,18,0.25);'
                : exerciseStatus(re) === 'done'
                  ? 'background:#111;border:1px solid rgba(255,255,255,0.04);opacity:0.8;'
                  : 'background:#161616;border:1px solid rgba(255,255,255,0.06);',
              supersetGroups[re.id] ? 'border-left:3px solid #3B82F6 !important;' : '',
              linkingSource && linkingSource.id !== re.id ? 'cursor:pointer;' : '',
            ].join('')"
            @click="linkingSource && linkingSource.id !== re.id ? confirmLink(re) : null">

            <!-- Handle de drag (siempre visible; mantén pulsado para arrastrar) -->
            <div class="drag-handle"
              @pointerdown="startDragPress"
              style="display:flex;align-items:center;justify-content:center;padding:6px 0 0;cursor:grab;touch-action:none;"
              :style="reorderMode ? 'color:#3B82F6;' : 'color:#2A2A2A;'">
              <svg width="18" height="10" viewBox="0 0 18 10" fill="currentColor">
                <circle cx="4" cy="2" r="1.5"/><circle cx="9" cy="2" r="1.5"/><circle cx="14" cy="2" r="1.5"/>
                <circle cx="4" cy="8" r="1.5"/><circle cx="9" cy="8" r="1.5"/><circle cx="14" cy="8" r="1.5"/>
              </svg>
            </div>

            <!-- Cabecera del ejercicio ──────────────────────────────────────── -->
            <div class="flex items-start gap-3" style="padding:14px 14px 10px;">

              <!-- Thumbnail — abre modal al tocar -->
              <button @click="modalExercise = re.exercise"
                style="width:52px;height:52px;border-radius:12px;overflow:hidden;flex-shrink:0;background:#1A1A1A;border:none;cursor:pointer;padding:0;">
                <img v-if="re.exercise?.thumbnail || re.exercise?.gif_url"
                  :src="(re.exercise.thumbnail || re.exercise.gif_url)!"
                  :alt="re.exercise.name"
                  style="width:100%;height:100%;object-fit:cover;"
                  loading="lazy"
                />
                <div v-else
                  style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6.5 6.5 11 11"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/>
                    <path d="m3 10 7-7"/><path d="m14 21 7-7"/>
                  </svg>
                </div>
              </button>

              <div style="flex:1;min-width:0;">
                <!-- Nombre + PR + ⋮ -->
                <div class="flex items-start justify-between gap-2" style="margin-bottom:3px;">
                  <button @click="modalExercise = re.exercise"
                    class="text-left font-display font-bold"
                    style="font-size:15px;letter-spacing:-0.01em;line-height:1.2;background:none;border:none;color:#fff;cursor:pointer;padding:0;text-decoration:underline;text-decoration-color:rgba(29,244,18,0.3);text-underline-offset:3px;">
                    {{ re.exercise?.name }}
                  </button>
                  <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                    <!-- PR badge -->
                    <span v-if="hasPR(re)"
                      style="font-size:9px;font-weight:700;background:rgba(245,158,11,0.15);color:#F59E0B;border-radius:5px;padding:2px 6px;text-transform:uppercase;letter-spacing:0.08em;">
                      🏆 PR
                    </span>
                    <!-- Injury badge -->
                    <span v-if="injuryWarning(re)"
                      :title="`Cuida tu ${injuryWarning(re)} en este ejercicio`"
                      style="font-size:9px;font-weight:700;background:rgba(239,68,68,0.12);color:#EF4444;border-radius:5px;padding:2px 6px;letter-spacing:0.06em;cursor:help;">
                      ⚠️ {{ injuryWarning(re) }}
                    </span>
                    <!-- Superset badge -->
                    <span v-if="supersetGroups[re.id]"
                      style="font-size:9px;font-weight:700;background:rgba(59,130,246,0.15);color:#3B82F6;border-radius:5px;padding:2px 6px;letter-spacing:0.06em;">
                      {{ SUPERSET_META[supersetGroups[re.id].type].name.toUpperCase() }}
                    </span>
                    <!-- Botón ⋮ -->
                    <button v-if="activeLog && !activeLog.completed"
                      @click.stop="toggleMenu(re.id, $event)"
                      style="background:none;border:none;cursor:pointer;padding:2px 4px;color:#6B7280;line-height:1;font-size:18px;letter-spacing:0.05em;">
                      ···
                    </button>
                  </div>
                </div>
                <!-- Subtexto info -->
                <div style="font-size:11px;color:#6B7280;margin-bottom:4px;">
                  {{ re.sets }} series · {{ re.reps }} reps
                </div>
                <!-- Botón analiza técnica -->
                <button v-if="activeLog && !activeLog.completed"
                  @click.stop="openTechniqueCoach(re)"
                  style="display:inline-flex;align-items:center;gap:5px;background:rgba(29,244,18,0.06);border:1px solid rgba(29,244,18,0.2);border-radius:999px;padding:3px 10px;cursor:pointer;margin-bottom:3px;transition:all 0.15s;"
                  onmouseover="this.style.background='rgba(29,244,18,0.12)'"
                  onmouseout="this.style.background='rgba(29,244,18,0.06)'">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7 16 12 23 17z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                  <span style="font-size:10px;color:#1DF412;font-weight:700;letter-spacing:0.04em;">Analiza mi técnica</span>
                </button>
                <!-- Fila de descanso prominente -->
                <button class="rest-trigger" @click.stop="openRestPicker(re)"
                  style="display:flex;align-items:center;gap:5px;background:none;border:none;cursor:pointer;padding:0;margin-bottom:3px;">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                  </svg>
                  <span style="font-size:12px;font-weight:700;"
                    :style="effectiveRest(re) === 0 ? 'color:#6B7280;' : 'color:#1DF412;'">
                    {{ restLabel(re) }}
                  </span>
                </button>
                <div style="font-size:11px;color:#6B7280;margin-top:2px;">
                  Anterior: {{ prevLabel(re.exercise.id) }}
                </div>
              </div>

              <!-- Status icon — también activa el ejercicio -->
              <button @click="activeReId = re.id; showDayPicker = false"
                style="flex-shrink:0;margin-left:4px;background:none;border:none;cursor:pointer;padding:0;">
                <!-- Done -->
                <div v-if="exerciseStatus(re) === 'done'"
                  style="width:30px;height:30px;border-radius:50%;background:#1DF412;display:flex;align-items:center;justify-content:center;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <!-- Active -->
                <div v-else-if="exerciseStatus(re) === 'active'"
                  style="width:30px;height:30px;border-radius:50%;background:transparent;border:1.5px solid #1DF412;display:flex;align-items:center;justify-content:center;">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="#1DF412"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                </div>
                <!-- Pending -->
                <div v-else
                  style="width:30px;height:30px;border-radius:50%;background:#1A1A1A;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#6B7280;">
                  {{ re.id < 0 ? '+' : allExercises.findIndex(e => e.id === re.id) + 1 }}
                </div>
              </button>
            </div>

            <!-- Tabla de series (solo si el ejercicio está activo o tiene series hechas) -->
            <div v-if="isExpanded(re)"
              style="padding:0 14px;">

              <!-- Header columnas -->
              <div class="grid"
                style="grid-template-columns:40px 70px 1fr 1fr 36px;gap:6px;padding-bottom:6px;font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;text-align:center;">
                <span></span>
                <span>ANT.</span>
                <span
                  @click="setUnit(unit === 'kg' ? 'lbs' : 'kg')"
                  style="cursor:pointer;user-select:none;text-decoration:underline dotted;text-underline-offset:3px;"
                  :style="unit === 'lbs' ? 'color:#F59E0B;' : 'color:#9CA3AF;'">
                  {{ unit === 'kg' ? 'KG' : 'LBS' }}
                </span>
                <span>Reps</span>
                <span></span>
              </div>

              <!-- Filas de series -->
              <div v-for="(set, idx) in exerciseSets[re.id]" :key="idx"
                style="position:relative;margin-bottom:6px;border-radius:8px;"
                :style="set.isDropChild ? 'border-left:2px solid rgba(139,92,246,0.45);margin-left:10px;' : ''">
                <!-- Fila de serie -->
                <div
                  style="display:grid;grid-template-columns:40px 70px 1fr 1fr 36px;gap:6px;align-items:center;padding:2px 0;transition:background 0.15s;"
                  :style="{
                    background: set.completed ? 'rgba(29,244,18,0.06)' : '#161616',
                  }">

                  <!-- Nº serie + tipo → abre selector (picker vía Teleport) -->
                  <div>
                    <div @click="activeLog && !activeLog.completed ? openPicker(re.id, idx, $event) : null"
                      style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;cursor:pointer;min-height:40px;user-select:none;">
                      <span class="font-display font-bold" style="font-size:13px;line-height:1;"
                        :style="{color: set.completed ? '#1DF412' : SET_TYPE_META[set.type].color}">
                        {{ set.setNumber }}
                      </span>
                      <span style="font-size:8px;font-weight:700;border-radius:3px;padding:1px 4px;line-height:1.4;"
                        :style="{background: SET_TYPE_META[set.type].bg, color: SET_TYPE_META[set.type].color}">
                        {{ SET_TYPE_META[set.type].label }}
                      </span>
                    </div>
                  </div>

                  <!-- ANTERIOR (columna 2) -->
                  <div style="font-size:11px;color:rgba(255,255,255,0.28);text-align:center;font-variant-numeric:tabular-nums;line-height:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding:2px 0;">
                    {{ fmtAnterior(re.exercise.id) }}
                  </div>

                  <!-- Peso (columna 3) -->
                  <input v-model="set.weightKg" type="number" min="0" step="0.5"
                    placeholder="—" :disabled="!activeLog"
                    class="text-center font-bold rounded-lg"
                    style="background:#0D0D0D;border:1px solid rgba(255,255,255,0.07);color:#fff;padding:8px 4px;font-size:14px;outline:none;width:100%;-moz-appearance:textfield;"
                  />

                  <!-- Reps (columna 4) -->
                  <input v-model="set.repsDone" type="number" min="0" step="1"
                    placeholder="—" :disabled="!activeLog"
                    class="text-center font-bold rounded-lg"
                    style="background:#0D0D0D;border:1px solid rgba(255,255,255,0.07);color:#fff;padding:8px 4px;font-size:14px;outline:none;width:100%;-moz-appearance:textfield;"
                  />

                  <!-- Check (columna 5) -->
                  <button @click="toggleSet(re, idx)" :disabled="!activeLog || set.saving"
                    class="flex items-center justify-center transition-all"
                    style="width:36px;height:36px;border-radius:10px;border:1.5px solid;cursor:pointer;flex-shrink:0;"
                    :style="set.completed
                      ? 'background:#1DF412;border-color:#1DF412;'
                      : 'background:#0D0D0D;border-color:rgba(255,255,255,0.1);'">
                    <svg v-if="set.completed" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    <div v-else-if="set.saving" style="width:12px;height:12px;border:2px solid #1DF412;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;"/>
                    <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </button>
                </div>
              </div>

              <!-- Botones agregar/quitar serie + toggle kg/lbs -->
              <div v-if="activeLog && !activeLog.completed"
                class="flex items-center gap-2" style="padding:6px 0 14px;">
                <button @click="addSet(re)"
                  class="flex items-center gap-1.5 font-bold"
                  style="background:transparent;border:1.5px dashed rgba(29,244,18,0.3);color:#1DF412;border-radius:8px;padding:7px 12px;font-size:12px;cursor:pointer;">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  Añadir serie
                </button>
                <button
                  v-if="exerciseSets[re.id].length > 1 && !exerciseSets[re.id][exerciseSets[re.id].length - 1].completed"
                  @click="removeLastSet(re)"
                  style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);color:#6B7280;border-radius:8px;padding:7px 10px;font-size:12px;cursor:pointer;">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
              </div>
            </div>

            <!-- Notas del ejercicio (sesión + nota fija de rutina) -->
            <div v-if="!collapsedForDrag && !reorderMode && (exerciseStatus(re) !== 'pending' || exerciseNotes[re.id] !== undefined)"
              style="padding:0 16px 12px;">
              <div v-if="re.notes" style="font-size:12px;color:#6B7280;line-height:1.5;margin-bottom:6px;">
                💡 {{ re.notes }}
              </div>
              <textarea
                v-model="exerciseNotes[re.id]"
                placeholder="Añadir nota para este ejercicio..."
                rows="2"
                style="width:100%;background:#0D0D0D;border:1px solid rgba(255,255,255,0.06);border-radius:8px;padding:8px 10px;color:#fff;font-size:12px;outline:none;resize:none;line-height:1.5;font-family:inherit;"
              />
            </div>
            <!-- Botón para abrir notas cuando está pendiente y no hay nota aún -->
            <button v-if="!collapsedForDrag && !reorderMode && exerciseStatus(re) === 'pending' && exerciseNotes[re.id] === undefined"
              @click.stop="exerciseNotes[re.id] = ''"
              style="background:none;border:none;color:#6B7280;font-size:11px;cursor:pointer;padding:0 16px 10px;display:block;">
              + Nota
            </button>
          </div>

          <!-- Botón agregar ejercicio extra (solo cuando el entrenamiento está activo) -->
          <button v-if="activeLog && !activeLog.completed"
            @click="showExSearch = true"
            class="w-full flex items-center justify-center gap-2 font-bold"
            style="border-radius:14px;padding:13px;font-size:13px;cursor:pointer;background:transparent;border:1.5px dashed rgba(255,255,255,0.12);color:#6B7280;margin-top:4px;transition:all 0.15s;"
            onmouseover="this.style.borderColor='rgba(29,244,18,0.4)';this.style.color='#1DF412'"
            onmouseout="this.style.borderColor='rgba(255,255,255,0.12)';this.style.color='#6B7280'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Agregar ejercicio
          </button>

        </div>

        <!-- Mood + notas (cuando log activo, abajo) ──────────────────────────── -->
        <div v-if="activeLog && !activeLog.completed" style="margin-top:24px;">

          <!-- Mood selector -->
          <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:10px;">
            ¿Cómo te sientes hoy?
          </div>
          <div style="display:flex;gap:8px;margin-bottom:20px;">
            <button v-for="m in MOODS" :key="m.id" @click="mood = m.id"
              style="flex:1;padding:10px 4px;border-radius:12px;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;border:1.5px solid;transition:all 0.15s;"
              :style="mood === m.id
                ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;'
                : 'background:#161616;border-color:rgba(255,255,255,0.06);'">
              <span style="font-size:20px;">{{ m.emoji }}</span>
              <span style="font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"
                :style="mood === m.id ? 'color:#1DF412;' : 'color:#6B7280;'">
                {{ m.label }}
              </span>
            </button>
          </div>

          <!-- Notas -->
          <textarea v-model="sessionNotes"
            placeholder="Notas de la sesión (opcional)..."
            style="width:100%;min-height:80px;background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:14px;color:#fff;font-size:14px;outline:none;resize:vertical;line-height:1.5;margin-bottom:20px;font-family:inherit;"
          />
        </div>

        <!-- Botón finalizar ─────────────────────────────────────────────────── -->
        <div v-if="activeLog && !activeLog.completed">
          <button @click="completeWorkout" :disabled="completing"
            class="w-full flex items-center justify-center gap-2 font-bold"
            style="border-radius:14px;padding:16px 24px;font-size:16px;cursor:pointer;border:none;transition:all 0.2s;margin-bottom:8px;"
            :style="allDone
              ? 'background:#1DF412;color:#000;box-shadow:0 4px 24px rgba(29,244,18,0.3);'
              : 'background:#161616;color:#1DF412;border:1.5px solid rgba(29,244,18,0.35);'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ completing ? 'Guardando...' : allDone ? '¡Finalizar entrenamiento! 🔥' : 'Finalizar entrenamiento' }}
          </button>
          <button @click="discardWorkout"
            class="w-full flex items-center justify-center gap-2 font-bold"
            style="border-radius:14px;padding:12px 24px;font-size:14px;cursor:pointer;border:none;transition:all 0.2s;background:transparent;color:#9CA3AF;border:1px solid rgba(255,255,255,0.06);">
            Descartar sesión
          </button>
          <p v-if="!allDone && completedCount > 0" class="text-center"
            style="font-size:12px;color:#6B7280;margin-top:8px;">
            {{ totalSets - completedCount }} series pendientes
          </p>
        </div>

        <!-- Entrenamiento completado ────────────────────────────────────────── -->
        <div v-if="activeLog?.completed"
          class="flex flex-col items-center text-center"
          style="margin-top:24px;background:#111;border:1px solid rgba(29,244,18,0.2);border-radius:20px;padding:32px;">
          <div style="width:72px;height:72px;border-radius:18px;background:#1DF412;display:flex;align-items:center;justify-content:center;margin-bottom:16px;box-shadow:0 0 40px rgba(29,244,18,0.4);">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h2 class="font-display font-bold" style="font-size:24px;margin-bottom:16px;">¡Entrenamiento completado!</h2>

          <!-- Stats finales -->
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;width:100%;margin-bottom:20px;">
            <div v-for="s in [
              { label: 'Ejercicios', value: allExercises.length },
              { label: 'Series',     value: completedCount },
              { label: 'Volumen',    value: Math.round(totalVolume) + 'kg' },
            ]" :key="s.label"
              style="background:rgba(29,244,18,0.06);border:1px solid rgba(29,244,18,0.12);border-radius:12px;padding:14px;">
              <div class="font-display font-bold" style="font-size:20px;color:#1DF412;">{{ s.value }}</div>
              <div style="font-size:10px;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-top:2px;">{{ s.label }}</div>
            </div>
          </div>

          <p style="font-size:14px;color:#9CA3AF;margin-bottom:4px;">
            Excelente trabajo. Mañana seguimos 💪
          </p>
          <p style="font-size:12px;color:#6B7280;">
            Los datos de esta sesión se han guardado en tu historial.
          </p>
        </div>

      </template>
    </div>

    <!-- Modal de ejercicio ───────────────────────────────────────────────────── -->
    <ExerciseModal
      :exercise="modalExercise"
      @close="modalExercise = null"
    />

    <!-- Buscador de ejercicios — agregar extra ──────────────────────────────── -->
    <ExerciseSearchModal
      v-if="showExSearch"
      @select="addExtraExercise"
      @close="showExSearch = false"
    />

    <!-- Buscador de ejercicios — reemplazar desde menú ⋮ ───────────────────── -->
    <ExerciseSearchModal
      v-if="replacingRe"
      @select="handleReplaceExercise"
      @close="replacingRe = null"
    />
  </div>

  <!-- Picker de tipo de serie (Teleport para escapar overflow:hidden del card) -->
  <Teleport to="body">
    <div v-if="activePicker && pickerSet"
      class="type-picker" @click.stop
      :style="{
        position:'fixed',
        top: activePicker.y + 'px',
        left: activePicker.x + 'px',
        width:'264px',
        zIndex:200,
        background:'#161616',
        border:'1px solid rgba(255,255,255,0.1)',
        borderRadius:'14px',
        padding:'6px',
        boxShadow:'0 8px 32px rgba(0,0,0,0.8)',
        maxHeight:'280px',
        overflowY:'auto',
      }">
      <div v-for="typeKey in (['working','warmup','dropset','failure'] as SetType[])" :key="typeKey"
        @click="selectType(typeKey)"
        style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;margin-bottom:2px;transition:background 0.1s;"
        :style="{
          background: pickerSet.type === typeKey ? SET_TYPE_META[typeKey].bg : 'transparent',
          border: pickerSet.type === typeKey ? `1px solid ${SET_TYPE_META[typeKey].color}40` : '1px solid transparent',
        }">
        <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;letter-spacing:-0.02em;"
          :style="{background: SET_TYPE_META[typeKey].bg, color: SET_TYPE_META[typeKey].color}">
          {{ SET_TYPE_META[typeKey].label }}
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;margin-bottom:2px;line-height:1.2;"
            :style="{color: pickerSet.type === typeKey ? SET_TYPE_META[typeKey].color : '#fff'}">
            {{ SET_TYPE_META[typeKey].name }}
          </div>
          <div style="font-size:11px;color:#9CA3AF;line-height:1.4;">{{ SET_TYPE_META[typeKey].description }}</div>
        </div>
        <svg v-if="pickerSet.type === typeKey" width="14" height="14" viewBox="0 0 24 24" fill="none"
          :stroke="SET_TYPE_META[typeKey].color" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>
      <!-- Separador + Eliminar serie -->
      <div style="height:1px;background:rgba(255,255,255,0.06);margin:4px 6px;"/>
      <div @click="deleteSetFromPicker()"
        style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;transition:background 0.1s;"
        onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'">
        <div style="width:32px;height:32px;border-radius:8px;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;">
          🗑
        </div>
        <span style="font-size:13px;font-weight:700;color:#EF4444;">Eliminar esta serie</span>
      </div>
    </div>
  </Teleport>

  <!-- Menú ⋮ por ejercicio -->
  <Teleport to="body">
    <div v-if="activeMenu !== null" class="exercise-menu" @click.stop
      :style="{
        position:'fixed',
        top: menuCoords.y + 'px',
        left: menuCoords.x + 'px',
        width:'192px',
        zIndex:210,
        background:'#161616',
        border:'1px solid rgba(255,255,255,0.1)',
        borderRadius:'14px',
        padding:'6px',
        boxShadow:'0 8px 32px rgba(0,0,0,0.9)',
      }">
      <!-- Reordenar -->
      <div @click="reorderMode = !reorderMode; activeMenu = null"
        style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;transition:background 0.1s;"
        onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
        <span style="font-size:16px;">↕</span>
        <span style="font-size:13px;font-weight:600;color:#fff;">{{ reorderMode ? 'Terminar reorden' : 'Reordenar' }}</span>
      </div>
      <!-- Reemplazar ejercicio -->
      <div @click="replacingRe = allExercises.find(e => e.id === activeMenu) ?? null; activeMenu = null"
        style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;transition:background 0.1s;"
        onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
        <span style="font-size:16px;">🔄</span>
        <span style="font-size:13px;font-weight:600;color:#fff;">Reemplazar ejercicio</span>
      </div>
      <!-- Vincular superserie -->
      <div @click="startLinking(allExercises.find(e => e.id === activeMenu)!)"
        style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;transition:background 0.1s;"
        onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
        <span style="font-size:16px;">🔗</span>
        <span style="font-size:13px;font-weight:600;color:#fff;">Vincular superserie</span>
      </div>
      <!-- Desvincular (solo si está en superset) -->
      <div v-if="activeMenu !== null && supersetGroups[activeMenu]"
        @click="removeFromSuperset(activeMenu!); activeMenu = null"
        style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;transition:background 0.1s;"
        onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="font-size:16px;">✂️</span>
        <span style="font-size:13px;font-weight:600;color:#EF4444;">Desvincular superserie</span>
      </div>
      <!-- Separador + Eliminar ejercicio -->
      <div style="height:1px;background:rgba(255,255,255,0.06);margin:4px 6px;"/>
      <div @click="askDeleteExercise(activeMenu!)"
        style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;cursor:pointer;transition:background 0.1s;"
        onmouseover="this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.background='transparent'">
        <span style="font-size:16px;">🗑</span>
        <span style="font-size:13px;font-weight:600;color:#EF4444;">Eliminar ejercicio</span>
      </div>
    </div>
  </Teleport>

  <!-- Confirmar eliminar ejercicio -->
  <Teleport to="body">
    <div v-if="deleteConfirmRe" style="position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:400;display:flex;align-items:center;justify-content:center;padding:20px;">
      <div style="background:#161616;border:1px solid rgba(255,255,255,0.1);border-radius:18px;padding:24px;width:100%;max-width:320px;text-align:center;">
        <div style="width:56px;height:56px;border-radius:14px;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:24px;">
          🗑
        </div>
        <div style="font-size:16px;font-weight:700;color:#fff;margin-bottom:6px;">¿Eliminar ejercicio?</div>
        <div style="font-size:13px;color:#9CA3AF;margin-bottom:20px;line-height:1.6;">
          <strong style="color:#fff;">{{ deleteConfirmRe.exercise?.name }}</strong> se quitará de tu rutina.
          <span v-if="deleteConfirmRe.id > 0" style="color:#6B7280;display:block;margin-top:4px;">Tus datos guardados se conservan por si quieres volver a añadirlo.</span>
        </div>
        <div style="display:flex;gap:10px;">
          <button @click="deleteConfirmRe = null"
            style="flex:1;padding:13px;border:1px solid rgba(255,255,255,0.1);border-radius:10px;background:transparent;color:#6B7280;cursor:pointer;font-size:14px;font-weight:600;">
            Cancelar
          </button>
          <button @click="confirmDeleteExercise()"
            style="flex:1;padding:13px;background:#EF4444;border:none;border-radius:10px;color:#fff;font-weight:700;cursor:pointer;font-size:14px;">
            Eliminar
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Drop set dialog -->
  <Teleport to="body">
    <div v-if="dropDialog" style="position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:400;display:flex;align-items:center;justify-content:center;padding:20px;">
      <div style="background:#161616;border:1px solid rgba(255,255,255,0.1);border-radius:18px;padding:24px;width:100%;max-width:300px;text-align:center;">
        <div style="font-size:16px;font-weight:700;color:#8B5CF6;margin-bottom:6px;">Drop Set</div>
        <div style="font-size:12px;color:#9CA3AF;margin-bottom:20px;line-height:1.6;">
          Baja el peso ~20% por bajada.<br>
          <span style="color:#6B7280;">Recomendado: 2 bajadas (máx. 3)</span>
        </div>
        <!-- Selector 1 / 2 / 3 -->
        <div style="display:flex;gap:12px;justify-content:center;margin-bottom:20px;">
          <button v-for="n in [1,2,3]" :key="n" @click="dropCount = n"
            style="width:64px;height:64px;border-radius:14px;border:1.5px solid;cursor:pointer;font-size:22px;font-weight:700;transition:all 0.15s;"
            :style="dropCount === n
              ? 'background:rgba(139,92,246,0.15);border-color:#8B5CF6;color:#8B5CF6;'
              : 'background:#0D0D0D;border-color:rgba(255,255,255,0.1);color:#6B7280;'">
            {{ n }}
          </button>
        </div>
        <div style="font-size:11px;color:#6B7280;margin-bottom:18px;">bajadas adicionales</div>
        <div style="display:flex;gap:10px;">
          <button @click="dropDialog = null"
            style="flex:1;padding:13px;border:1px solid rgba(255,255,255,0.1);border-radius:10px;background:transparent;color:#6B7280;cursor:pointer;font-size:14px;font-weight:600;">
            Cancelar
          </button>
          <button @click="confirmDropSets()"
            style="flex:1;padding:13px;background:#8B5CF6;border:none;border-radius:10px;color:#fff;font-weight:700;cursor:pointer;font-size:14px;">
            Agregar
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Bottom sheet — picker de tiempo de descanso -->
  <Teleport to="body">
    <!-- Overlay -->
    <div v-if="restPickerRe" @click="closeRestPicker"
      style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:300;"/>
    <!-- Sheet -->
    <div v-if="restPickerRe" class="rest-sheet"
      style="position:fixed;bottom:0;left:0;right:0;z-index:301;background:#161616;border-radius:20px 20px 0 0;padding-bottom:env(safe-area-inset-bottom,16px);">
      <!-- Handle -->
      <div style="width:40px;height:4px;background:rgba(255,255,255,0.15);border-radius:2px;margin:12px auto 0;"/>
      <!-- Título -->
      <div style="padding:14px 20px 4px;font-size:13px;font-weight:700;color:#9CA3AF;text-align:center;">
        Tiempo de descanso — {{ restPickerRe.exercise?.name }}
      </div>
      <!-- Rueda scroll -->
      <div style="position:relative;height:220px;overflow:hidden;">
        <!-- Indicador central -->
        <div style="position:absolute;top:50%;left:16px;right:16px;height:44px;transform:translateY(-50%);background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;pointer-events:none;z-index:1;"/>
        <div style="height:220px;overflow-y:scroll;scroll-snap-type:y mandatory;padding:88px 0;">
          <div v-for="s in REST_OPTIONS" :key="s"
            @click="selectRestTime(s)"
            style="scroll-snap-align:center;height:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:color 0.1s;"
            :style="effectiveRest(restPickerRe) === s
              ? 'color:#1DF412;font-size:20px;font-weight:700;'
              : 'color:#6B7280;font-size:16px;font-weight:600;'">
            {{ s === 0 ? '— APAGADO' : s < 60 ? s + 's' : Math.floor(s/60) + ':' + String(s%60).padStart(2,'0') }}
          </div>
        </div>
      </div>
      <div style="padding:8px 16px 16px;">
        <button @click="closeRestPicker"
          style="width:100%;padding:14px;background:#1DF412;color:#000;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;">
          Confirmar
        </button>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; }
input[type=number]:focus { border-color: rgba(29,244,18,0.5) !important; }
textarea:focus { border-color: rgba(29,244,18,0.4) !important; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
