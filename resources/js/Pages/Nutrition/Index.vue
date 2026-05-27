<script setup lang="ts">
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { User } from '@/types'

defineOptions({ layout: AppLayout })

const page = usePage()
const plan = computed(() => page.props.plan as any)
const meals = computed(() => page.props.meals as any[])
const foods = computed(() => page.props.foods as any[])

const macroTotals = computed(() => {
  let totals = { kcal: 0, protein_g: 0, fat_g: 0, carbs_g: 0 }
  for (const meal of meals.value) {
    for (const item of meal.items ?? []) {
      totals.kcal += item.kcal
      totals.protein_g += item.protein_g
      totals.fat_g += item.fat_g
      totals.carbs_g += item.carbs_g
    }
  }
  return totals
})

const progressPercent = computed(() => {
  const target = plan.value?.daily_kcal_target ?? 2000
  return Math.min(100, Math.round((macroTotals.value.kcal / target) * 100))
})

const proteinProgress = computed(() => {
  const target = plan.value?.protein_g_target ?? 150
  return Math.min(100, Math.round((macroTotals.value.protein_g / target) * 100))
})

const foodCategories = computed(() => {
  const cats: Record<string, any[]> = {}
  for (const f of foods.value) {
    if (!cats[f.category]) cats[f.category] = []
    cats[f.category].push(f)
  }
  return cats
})

function regenerate(): void {
  router.post(route('nutrition.regenerate'))
}

function formatNumber(n: number): string {
  return Math.round(n).toLocaleString('es-CO')
}
</script>

<template>
  <div class="min-h-screen" style="background:#000;color:#fff;">

    <!-- Header -->
    <div style="padding:20px 20px 0;">
      <div class="flex items-center justify-between mb-5">
        <div>
          <h1 class="font-black" style="font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:900;text-transform:uppercase;letter-spacing:-0.01em;">
            Nutrición
          </h1>
          <p style="font-size:13px;color:#9CA3AF;margin-top:2px;">Tu plan calórico personalizado</p>
        </div>
        <button @click="regenerate"
          class="flex items-center gap-2 text-xs font-semibold px-4 py-2 rounded-xl transition-all"
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
              {{ formatNumber(plan?.daily_kcal_target ?? 0) }}
              <span style="font-size:16px;color:#6B7280;font-weight:400;">kcal</span>
            </div>
          </div>
          <div class="text-right">
            <div style="font-size:11px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;">Consumido</div>
            <div style="font-size:20px;font-weight:800;" :style="macroTotals.kcal > (plan?.daily_kcal_target ?? 0) ? 'color:#F87171;' : 'color:#1DF412;'">
              {{ formatNumber(macroTotals.kcal) }}
            </div>
          </div>
        </div>

        <!-- Barra de progreso -->
        <div style="height:6px;background:rgba(255,255,255,0.08);border-radius:3px;overflow:hidden;">
          <div style="height:100%;width:60%;background:#1DF412;border-radius:3px;transition:width 0.3s;"></div>
        </div>
        <div style="font-size:11px;color:#6B7280;margin-top:4px;text-align:right;">{{ progressPercent }}%</div>
      </div>

      <!-- Macros -->
      <div class="grid grid-cols-3 gap-3 mb-5">
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:14px;text-align:center;">
          <div style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;margin-bottom:4px;">Proteína</div>
          <div style="font-size:20px;font-weight:800;color:#fff;">{{ formatNumber(macroTotals.protein_g) }}</div>
          <div style="font-size:10px;color:#6B7280;">/ {{ formatNumber(plan?.protein_g_target ?? 0) }}g</div>
          <div style="height:3px;background:rgba(255,255,255,0.08);border-radius:2px;margin-top:8px;overflow:hidden;">
            <div :style="`height:100%;width:${proteinProgress}%;background:#3B82F6;border-radius:2px;`"></div>
          </div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:14px;text-align:center;">
          <div style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;margin-bottom:4px;">Grasa</div>
          <div style="font-size:20px;font-weight:800;color:#fff;">{{ formatNumber(macroTotals.fat_g) }}</div>
          <div style="font-size:10px;color:#6B7280;">/ {{ formatNumber(plan?.fat_g_target ?? 0) }}g</div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:14px;text-align:center;">
          <div style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;margin-bottom:4px;">Carbos</div>
          <div style="font-size:20px;font-weight:800;color:#fff;">{{ formatNumber(macroTotals.carbs_g) }}</div>
          <div style="font-size:10px;color:#6B7280;">/ {{ formatNumber(plan?.carbs_g_target ?? 0) }}g</div>
        </div>
      </div>
    </div>

    <!-- Comidas -->
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
                <div style="font-size:15px;font-weight:700;color:#fff;">{{ meal.name }}</div>
                <div style="font-size:11px;color:#6B7280;">{{ meal.time }}</div>
              </div>
            </div>
            <div style="font-size:13px;font-weight:700;color:#1DF412;">
              {{ Math.round(meal.items?.reduce((s: number, i: any) => s + i.kcal, 0) ?? 0) }} kcal
            </div>
          </div>

          <!-- Ítems de la comida -->
          <div v-if="meal.items?.length" style="padding:8px 16px 12px;">
            <div v-for="item in meal.items" :key="item.id"
              class="flex items-center justify-between"
              style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.03);">
              <div class="flex items-center gap-3">
                <div style="width:6px;height:6px;border-radius:50%;background:#1DF412;flex-shrink:0;"></div>
                <span style="font-size:14px;color:#E5E7EB;">{{ item.food?.name ?? 'Alimento' }}</span>
              </div>
              <div style="font-size:12px;color:#9CA3AF;">
                {{ item.portion_g }}g · {{ Math.round(item.kcal) }} kcal
              </div>
            </div>
          </div>

          <!-- Vacío -->
          <div v-else style="padding:16px;text-align:center;">
            <p style="font-size:13px;color:#4B5563;">Sin alimentos asignados</p>
          </div>
        </div>
      </div>

      <!-- Info científica -->
      <div style="margin-top:20px;padding:16px;background:rgba(29,244,18,0.04);border:1px solid rgba(29,244,18,0.12);border-radius:14px;">
        <div style="display:flex;gap:12px;align-items:flex-start;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <div>
            <p style="font-size:12px;color:#D1FAE5;line-height:1.5;margin:0;">
              <strong>Cómo se calculó:</strong> Tu meta calórica se basa en tu BMR (Mifflin-St Jeor), ajustada por tu nivel de actividad y objetivo usando las fórmulas de Morton 2018 y ACSM.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>