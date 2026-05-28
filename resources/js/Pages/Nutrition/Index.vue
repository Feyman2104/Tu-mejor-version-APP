<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { NutritionFood, DietMeal, DietMealItem, DietPlan } from '@/types'

defineOptions({ layout: AppLayout })

const page = usePage()
const plan  = computed(() => page.props.plan  as DietPlan)
const meals = computed(() => page.props.meals as DietMeal[])
const foods = computed(() => page.props.foods as NutritionFood[])

// ─── Totales ──────────────────────────────────────────────────────────────────
const macroTotals = computed(() => {
  let t = { kcal: 0, protein_g: 0, fat_g: 0, carbs_g: 0 }
  for (const meal of meals.value) {
    for (const item of meal.items ?? []) {
      t.kcal      += item.kcal
      t.protein_g += item.protein_g
      t.fat_g     += item.fat_g
      t.carbs_g   += item.carbs_g
    }
  }
  return t
})

const progressPercent   = computed(() => Math.min(100, Math.round((macroTotals.value.kcal / (plan.value?.daily_kcal_target ?? 2000)) * 100)))
const proteinProgress   = computed(() => Math.min(100, Math.round((macroTotals.value.protein_g / (plan.value?.protein_g_target ?? 150)) * 100)))

// ─── Conversión de unidades (espejo del servidor) ─────────────────────────────
type Unit = 'g' | 'kg' | 'oz' | 'ml' | 'l' | 'unidad'

function toGrams(qty: number, unit: Unit, food: NutritionFood): number {
  const density = food.density ?? 1.0
  switch (unit) {
    case 'g':      return qty
    case 'kg':     return qty * 1000
    case 'oz':     return qty * 28.35
    case 'ml':     return qty * density
    case 'l':      return qty * 1000 * density
    case 'unidad': return qty * (food.grams_per_unit ?? food.portion_g)
    default:       return qty
  }
}

function macrosForGrams(food: NutritionFood, grams: number) {
  const f = grams / 100
  return {
    kcal:      Math.round(food.kcal      * f),
    protein_g: Math.round(food.protein_g * f * 10) / 10,
    fat_g:     Math.round(food.fat_g     * f * 10) / 10,
    carbs_g:   Math.round(food.carbs_g   * f * 10) / 10,
  }
}

function availableUnits(food: NutritionFood): Unit[] {
  const units: Unit[] = ['g', 'kg', 'oz']
  if (['bebidas', 'sopas'].includes(food.category)) units.push('ml', 'l')
  if (food.grams_per_unit && food.grams_per_unit > 0) units.push('unidad')
  return units
}

// ─── Modal: añadir alimento ───────────────────────────────────────────────────
const addModal    = ref<{ open: boolean; mealId: number | null }>({ open: false, mealId: null })
const searchQuery = ref('')
const addFood     = ref<NutritionFood | null>(null)
const addQty      = ref<number>(100)
const addUnit     = ref<Unit>('g')
const addLoading  = ref(false)

const filteredFoods = computed(() => {
  const q = searchQuery.value.toLowerCase().trim()
  if (!q) return foods.value.slice(0, 30)
  return foods.value.filter(f =>
    f.name.toLowerCase().includes(q) || f.category.toLowerCase().includes(q)
  ).slice(0, 30)
})

const addPreview = computed(() => {
  if (!addFood.value || !addQty.value || addQty.value <= 0) return null
  const grams = toGrams(addQty.value, addUnit.value, addFood.value)
  return macrosForGrams(addFood.value, grams)
})

function openAddModal(mealId: number): void {
  addModal.value = { open: true, mealId }
  searchQuery.value = ''
  addFood.value = null
  addQty.value = 100
  addUnit.value = 'g'
}

function closeAddModal(): void {
  addModal.value = { open: false, mealId: null }
}

function selectFood(food: NutritionFood): void {
  addFood.value = food
  addUnit.value = availableUnits(food)[0]
  addQty.value = food.portion_g > 0 ? food.portion_g : 100
}

watch(addFood, (food) => {
  if (!food) return
  const units = availableUnits(food)
  if (!units.includes(addUnit.value)) addUnit.value = units[0]
})

function confirmAdd(): void {
  if (!addModal.value.mealId || !addFood.value || !addQty.value) return
  addLoading.value = true
  router.post(
    route('nutrition.items.store', { meal: addModal.value.mealId }),
    { food_id: addFood.value.id, quantity: addQty.value, unit: addUnit.value },
    { onFinish: () => { addLoading.value = false; closeAddModal() } }
  )
}

// ─── Edición inline ───────────────────────────────────────────────────────────
const editItem  = ref<DietMealItem | null>(null)
const editQty   = ref<number>(0)
const editUnit  = ref<Unit>('g')
const editLoading = ref(false)

const editPreview = computed(() => {
  if (!editItem.value?.food || !editQty.value || editQty.value <= 0) return null
  const grams = toGrams(editQty.value, editUnit.value, editItem.value.food)
  return macrosForGrams(editItem.value.food, grams)
})

function startEdit(item: DietMealItem): void {
  editItem.value = item
  editQty.value  = item.quantity ?? item.portion_g
  editUnit.value = (item.unit as Unit) ?? 'g'
}

function cancelEdit(): void {
  editItem.value = null
}

function saveEdit(): void {
  if (!editItem.value || !editQty.value) return
  editLoading.value = true
  router.patch(
    route('nutrition.items.update', { item: editItem.value.id }),
    { quantity: editQty.value, unit: editUnit.value },
    { onFinish: () => { editLoading.value = false; editItem.value = null } }
  )
}

function destroyItem(item: DietMealItem): void {
  if (!confirm(`¿Eliminar "${item.food?.name ?? 'este alimento'}"?`)) return
  router.delete(route('nutrition.items.destroy', { item: item.id }))
}

// ─── Helpers UI ──────────────────────────────────────────────────────────────
function regenerate(): void {
  router.post(route('nutrition.regenerate'))
}

function fmt(n: number): string {
  return Math.round(n).toLocaleString('es-CO')
}

function mealKcal(meal: DietMeal): number {
  return Math.round((meal.items ?? []).reduce((s, i) => s + i.kcal, 0))
}
</script>

<template>
  <div class="min-h-screen" style="background:#000;color:#fff;">

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
    <div style="padding:20px 20px 0;">
      <div class="flex items-center justify-between mb-5">
        <div>
          <h1 class="font-black" style="font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:900;text-transform:uppercase;letter-spacing:-0.01em;">
            Nutrición
          </h1>
          <p style="font-size:13px;color:#9CA3AF;margin-top:2px;">Tu plan calórico personalizado</p>
        </div>
        <button @click="regenerate"
          class="flex items-center gap-2 text-xs font-semibold px-4 py-2 rounded-xl"
          style="background:#161616;color:#9CA3AF;border:1px solid rgba(255,255,255,0.08);">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
          Recalcular
        </button>
      </div>

      <!-- Resumen calórico -->
      <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:20px;margin-bottom:16px;">
        <div class="flex items-center justify-between mb-3">
          <div>
            <div style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;">Meta diaria</div>
            <div class="font-black" style="font-size:32px;font-weight:900;letter-spacing:-0.02em;">
              {{ fmt(plan?.daily_kcal_target ?? 0) }}
              <span style="font-size:16px;color:#6B7280;font-weight:400;">kcal</span>
            </div>
          </div>
          <div class="text-right">
            <div style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;">Consumido</div>
            <div style="font-size:20px;font-weight:800;"
              :style="macroTotals.kcal > (plan?.daily_kcal_target ?? 0) ? 'color:#F87171;' : 'color:#1DF412;'">
              {{ fmt(macroTotals.kcal) }}
            </div>
          </div>
        </div>
        <!-- Barra de progreso (FIX: was hardcoded 60%) -->
        <div style="height:6px;background:rgba(255,255,255,0.08);border-radius:3px;overflow:hidden;">
          <div :style="`height:100%;width:${progressPercent}%;background:#1DF412;border-radius:3px;transition:width 0.3s;`"></div>
        </div>
        <div style="font-size:11px;color:#6B7280;margin-top:4px;text-align:right;">{{ progressPercent }}%</div>
      </div>

      <!-- Macros -->
      <div class="grid grid-cols-3 gap-3 mb-5">
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:14px;text-align:center;">
          <div style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;margin-bottom:4px;">Proteína</div>
          <div style="font-size:20px;font-weight:800;">{{ fmt(macroTotals.protein_g) }}</div>
          <div style="font-size:10px;color:#6B7280;">/ {{ fmt(plan?.protein_g_target ?? 0) }}g</div>
          <div style="height:3px;background:rgba(255,255,255,0.08);border-radius:2px;margin-top:8px;overflow:hidden;">
            <div :style="`height:100%;width:${proteinProgress}%;background:#3B82F6;border-radius:2px;`"></div>
          </div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:14px;text-align:center;">
          <div style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;margin-bottom:4px;">Grasa</div>
          <div style="font-size:20px;font-weight:800;">{{ fmt(macroTotals.fat_g) }}</div>
          <div style="font-size:10px;color:#6B7280;">/ {{ fmt(plan?.fat_g_target ?? 0) }}g</div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:14px;text-align:center;">
          <div style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;margin-bottom:4px;">Carbos</div>
          <div style="font-size:20px;font-weight:800;">{{ fmt(macroTotals.carbs_g) }}</div>
          <div style="font-size:10px;color:#6B7280;">/ {{ fmt(plan?.carbs_g_target ?? 0) }}g</div>
        </div>
      </div>
    </div>

    <!-- ── Comidas ──────────────────────────────────────────────────────────── -->
    <div style="padding:0 20px 100px;">
      <h2 style="font-size:12px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">Comidas del día</h2>

      <div class="flex flex-col gap-3">
        <div v-for="meal in meals" :key="meal.id"
          style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;overflow:hidden;">

          <!-- Header comida -->
          <div class="flex items-center justify-between" style="padding:14px 16px;border-bottom:1px solid rgba(255,255,255,0.04);">
            <div class="flex items-center gap-3">
              <div style="width:36px;height:36px;border-radius:10px;background:rgba(29,244,18,0.1);display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              </div>
              <div>
                <div style="font-size:15px;font-weight:700;">{{ meal.name }}</div>
                <div style="font-size:11px;color:#6B7280;">{{ meal.time }}</div>
              </div>
            </div>
            <div style="font-size:13px;font-weight:700;color:#1DF412;">
              {{ mealKcal(meal) }} kcal
            </div>
          </div>

          <!-- Ítems -->
          <div v-if="meal.items?.length" style="padding:8px 16px 4px;">
            <div v-for="item in meal.items" :key="item.id">

              <!-- Vista normal -->
              <div v-if="editItem?.id !== item.id"
                class="flex items-center justify-between"
                style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.03);">
                <div class="flex items-center gap-2" style="flex:1;min-width:0;">
                  <div style="width:6px;height:6px;border-radius:50%;background:#1DF412;flex-shrink:0;"></div>
                  <div style="min-width:0;">
                    <div style="font-size:13px;color:#E5E7EB;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                      {{ item.food?.name ?? 'Alimento' }}
                    </div>
                    <div style="font-size:11px;color:#6B7280;">
                      {{ item.quantity }}{{ item.unit }} · P {{ item.protein_g }}g G {{ item.fat_g }}g C {{ item.carbs_g }}g
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-2" style="flex-shrink:0;">
                  <div style="font-size:12px;color:#9CA3AF;font-weight:600;">{{ Math.round(item.kcal) }} kcal</div>
                  <!-- Editar -->
                  <button @click="startEdit(item)"
                    style="width:28px;height:28px;border-radius:6px;background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <!-- Eliminar -->
                  <button @click="destroyItem(item)"
                    style="width:28px;height:28px;border-radius:6px;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#F87171" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                  </button>
                </div>
              </div>

              <!-- Vista edición inline -->
              <div v-else style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                <div style="font-size:12px;color:#9CA3AF;margin-bottom:8px;font-weight:600;">
                  Editando: {{ item.food?.name }}
                </div>
                <div class="flex items-center gap-2 mb-2">
                  <input v-model.number="editQty" type="number" min="0.01" step="any"
                    style="width:90px;background:#222;border:1px solid rgba(255,255,255,0.12);border-radius:8px;padding:6px 10px;color:#fff;font-size:14px;text-align:center;" />
                  <select v-model="editUnit"
                    style="background:#222;border:1px solid rgba(255,255,255,0.12);border-radius:8px;padding:6px 10px;color:#fff;font-size:14px;">
                    <option v-for="u in availableUnits(item.food!)" :key="u" :value="u">{{ u }}</option>
                  </select>
                  <div v-if="editPreview" style="font-size:11px;color:#1DF412;font-weight:600;">
                    {{ editPreview.kcal }} kcal
                  </div>
                </div>
                <div v-if="editPreview" style="font-size:11px;color:#6B7280;margin-bottom:8px;">
                  P {{ editPreview.protein_g }}g · G {{ editPreview.fat_g }}g · C {{ editPreview.carbs_g }}g
                </div>
                <div class="flex gap-2">
                  <button @click="saveEdit" :disabled="editLoading"
                    style="flex:1;padding:6px;border-radius:8px;background:#1DF412;color:#000;font-size:12px;font-weight:700;">
                    {{ editLoading ? 'Guardando…' : 'Guardar' }}
                  </button>
                  <button @click="cancelEdit"
                    style="padding:6px 14px;border-radius:8px;background:rgba(255,255,255,0.06);color:#9CA3AF;font-size:12px;">
                    Cancelar
                  </button>
                </div>
              </div>

            </div>
          </div>

          <!-- Vacío -->
          <div v-else style="padding:12px 16px 4px;">
            <p style="font-size:13px;color:#6B7280;">Sin alimentos asignados</p>
          </div>

          <!-- Botón añadir alimento -->
          <div style="padding:10px 16px 12px;">
            <button @click="openAddModal(meal.id)"
              class="flex items-center gap-2 w-full justify-center"
              style="padding:8px;border-radius:8px;background:rgba(29,244,18,0.06);border:1px dashed rgba(29,244,18,0.25);color:#1DF412;font-size:12px;font-weight:600;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Añadir alimento
            </button>
          </div>
        </div>
      </div>

      <!-- Info científica -->
      <div style="margin-top:20px;padding:16px;background:rgba(29,244,18,0.04);border:1px solid rgba(29,244,18,0.12);border-radius:14px;">
        <div style="display:flex;gap:12px;align-items:flex-start;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <p style="font-size:12px;color:#D1FAE5;line-height:1.5;margin:0;">
            <strong>Cómo se calculó:</strong> Tu meta calórica usa Mifflin-St Jeor (BMR) ajustado por actividad y objetivo, con proteína 2 g/kg (Morton 2018 · ACSM).
          </p>
        </div>
      </div>
    </div>

    <!-- ── Modal: buscar y añadir alimento ────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="addModal.open"
        style="position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.85);display:flex;align-items:flex-end;"
        @click.self="closeAddModal">

        <div style="width:100%;max-height:90vh;background:#111;border-radius:20px 20px 0 0;display:flex;flex-direction:column;overflow:hidden;">

          <!-- Drag handle + título -->
          <div style="padding:12px 20px 16px;border-bottom:1px solid rgba(255,255,255,0.06);">
            <div style="width:40px;height:4px;background:rgba(255,255,255,0.15);border-radius:2px;margin:0 auto 12px;"></div>
            <div class="flex items-center justify-between">
              <h3 style="font-size:16px;font-weight:700;">Añadir alimento</h3>
              <button @click="closeAddModal" style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </div>

          <!-- Buscador -->
          <div style="padding:12px 16px;border-bottom:1px solid rgba(255,255,255,0.04);">
            <div style="position:relative;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2"
                style="position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              <input v-model="searchQuery" type="text" placeholder="Buscar alimento…" autofocus
                style="width:100%;background:#1a1a1a;border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:10px 12px 10px 36px;color:#fff;font-size:14px;" />
            </div>
          </div>

          <!-- Panel: selección de alimento -->
          <div v-if="!addFood" style="flex:1;overflow-y:auto;padding:8px 0;">
            <div v-for="food in filteredFoods" :key="food.id"
              @click="selectFood(food)"
              style="padding:10px 16px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.03);">
              <div class="flex items-center justify-between">
                <div>
                  <div style="font-size:14px;font-weight:600;color:#E5E7EB;">{{ food.name }}</div>
                  <div style="font-size:11px;color:#6B7280;text-transform:capitalize;">{{ food.category }}</div>
                </div>
                <div style="text-align:right;font-size:11px;color:#9CA3AF;line-height:1.6;">
                  <div style="color:#fff;font-weight:700;">{{ Math.round(food.kcal) }} kcal</div>
                  <div>P {{ food.protein_g }}g · G {{ food.fat_g }}g · C {{ food.carbs_g }}g</div>
                  <div style="font-size:10px;color:#4B5563;">por 100 g</div>
                </div>
              </div>
            </div>
            <div v-if="!filteredFoods.length" style="text-align:center;padding:32px;color:#6B7280;font-size:13px;">
              No se encontraron alimentos
            </div>
          </div>

          <!-- Panel: configurar porción -->
          <div v-else style="flex:1;overflow-y:auto;padding:16px;">

            <!-- Alimento seleccionado -->
            <div style="background:#1a1a1a;border-radius:12px;padding:14px;margin-bottom:16px;">
              <div class="flex items-center justify-between">
                <div>
                  <div style="font-size:15px;font-weight:700;">{{ addFood.name }}</div>
                  <div style="font-size:11px;color:#6B7280;text-transform:capitalize;">{{ addFood.category }}</div>
                </div>
                <button @click="addFood = null"
                  style="font-size:12px;color:#6B7280;background:rgba(255,255,255,0.06);padding:4px 10px;border-radius:6px;">
                  Cambiar
                </button>
              </div>
              <div style="margin-top:10px;font-size:11px;color:#6B7280;display:flex;gap:12px;">
                <span>P {{ addFood.protein_g }}g</span>
                <span>G {{ addFood.fat_g }}g</span>
                <span>C {{ addFood.carbs_g }}g</span>
                <span style="color:#4B5563;">por 100 g</span>
              </div>
            </div>

            <!-- Cantidad + Unidad -->
            <div style="margin-bottom:16px;">
              <div style="font-size:12px;font-weight:600;color:#9CA3AF;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.06em;">Porción</div>
              <div class="flex gap-3">
                <input v-model.number="addQty" type="number" min="0.01" step="any"
                  style="flex:1;background:#1a1a1a;border:1px solid rgba(255,255,255,0.12);border-radius:10px;padding:12px;color:#fff;font-size:18px;font-weight:700;text-align:center;" />
                <select v-model="addUnit"
                  style="background:#1a1a1a;border:1px solid rgba(255,255,255,0.12);border-radius:10px;padding:12px;color:#fff;font-size:15px;font-weight:600;min-width:80px;">
                  <option v-for="u in availableUnits(addFood)" :key="u" :value="u">{{ u }}</option>
                </select>
              </div>
            </div>

            <!-- Preview de macros -->
            <div v-if="addPreview" style="background:rgba(29,244,18,0.05);border:1px solid rgba(29,244,18,0.15);border-radius:12px;padding:14px;margin-bottom:16px;">
              <div style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px;">Macros de esta porción</div>
              <div class="grid grid-cols-4 gap-2" style="text-align:center;">
                <div>
                  <div style="font-size:18px;font-weight:800;color:#1DF412;">{{ addPreview.kcal }}</div>
                  <div style="font-size:10px;color:#6B7280;">kcal</div>
                </div>
                <div>
                  <div style="font-size:18px;font-weight:800;">{{ addPreview.protein_g }}</div>
                  <div style="font-size:10px;color:#6B7280;">P (g)</div>
                </div>
                <div>
                  <div style="font-size:18px;font-weight:800;">{{ addPreview.fat_g }}</div>
                  <div style="font-size:10px;color:#6B7280;">G (g)</div>
                </div>
                <div>
                  <div style="font-size:18px;font-weight:800;">{{ addPreview.carbs_g }}</div>
                  <div style="font-size:10px;color:#6B7280;">C (g)</div>
                </div>
              </div>
            </div>

            <!-- Confirmar -->
            <button @click="confirmAdd" :disabled="addLoading || !addQty || addQty <= 0"
              class="w-full"
              style="padding:14px;border-radius:12px;background:#1DF412;color:#000;font-size:15px;font-weight:800;letter-spacing:-0.01em;"
              :style="(addLoading || !addQty || addQty <= 0) ? 'opacity:0.5;' : ''">
              {{ addLoading ? 'Añadiendo…' : 'Añadir al plan' }}
            </button>
          </div>

        </div>
      </div>
    </Teleport>

  </div>
</template>
