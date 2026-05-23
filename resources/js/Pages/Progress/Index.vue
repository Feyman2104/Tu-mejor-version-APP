<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { ProgressEntry } from '@/types'

defineOptions({ layout: AppLayout })

const props = defineProps<{
  entries: ProgressEntry[]
}>()

const showForm = ref(false)

const form = useForm({
  weight_kg: '',
  body_fat_pct: '',
  muscle_mass_kg: '',
  notes: '',
})

function submit() {
  form.post(route('progress.store'), {
    onSuccess: () => {
      form.reset()
      showForm.value = false
    },
  })
}

// Solo entradas con mediciones reales de peso (excluye notas JSON de postura)
const weightEntries = computed(() =>
  [...props.entries]
    .filter(e => e.weight_kg != null)
    .sort((a, b) => new Date(a.recorded_at).getTime() - new Date(b.recorded_at).getTime())
)

const weights = computed(() => weightEntries.value.map(e => Number(e.weight_kg)))

const latest = computed(() => weightEntries.value.at(-1) ?? null)
const first = computed(() => weightEntries.value.at(0) ?? null)

const weightDelta = computed(() => {
  if (!latest.value || !first.value) return 0
  return Number(latest.value.weight_kg) - Number(first.value.weight_kg)
})

// SVG line chart geometry
const chartPath = computed(() => {
  const data = weights.value
  if (data.length < 2) return { line: '', area: '', points: [] as { x: number; y: number }[] }

  const w = 100, h = 50, pad = 4
  const max = Math.max(...data) * 1.05
  const min = Math.min(...data) * 0.95
  const range = max - min || 1

  const points = data.map((v, i) => ({
    x: pad + (i / (data.length - 1)) * (w - 2 * pad),
    y: pad + (1 - (v - min) / range) * (h - 2 * pad),
  }))

  const line = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ')
  const area = `${line} L ${points.at(-1)!.x} ${h - pad} L ${pad} ${h - pad} Z`

  return { line, area, points }
})

function formatDate(d: string) {
  return new Date(d).toLocaleDateString('es', { day: 'numeric', month: 'short' })
}
</script>

<template>
  <div class="min-h-screen relative" style="background:#000;">
    <div class="px-4 md:px-8 py-6 max-w-4xl mx-auto pb-24">

      <!-- Header -->
      <div class="flex justify-between items-center" style="margin-bottom:24px;">
        <div>
          <h1 class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;">Mi Progreso</h1>
          <p style="font-size:14px;color:#9CA3AF;">Registra y visualiza tu evolución</p>
        </div>
        <button @click="showForm = !showForm"
          class="flex items-center gap-2 font-bold flex-shrink-0"
          style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:12px 18px;font-size:14px;cursor:pointer;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          <span class="hidden md:inline">Registrar medida</span>
        </button>
      </div>

      <!-- Record form -->
      <div v-if="showForm" style="background:#161616;border:1px solid rgba(29,244,18,0.2);border-radius:18px;padding:24px;margin-bottom:24px;">
        <h3 class="font-display font-bold" style="font-size:18px;margin-bottom:16px;">Nueva medida</h3>
        <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">Peso (kg)</label>
            <input v-model="form.weight_kg" type="number" step="0.1" placeholder="72.5"
              style="width:100%;background:#0D0D0D;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 14px;color:#fff;font-size:15px;outline:none;" />
          </div>
          <div>
            <label style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">% Grasa</label>
            <input v-model="form.body_fat_pct" type="number" step="0.1" placeholder="18"
              style="width:100%;background:#0D0D0D;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 14px;color:#fff;font-size:15px;outline:none;" />
          </div>
          <div>
            <label style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">Masa muscular (kg)</label>
            <input v-model="form.muscle_mass_kg" type="number" step="0.1" placeholder="35"
              style="width:100%;background:#0D0D0D;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 14px;color:#fff;font-size:15px;outline:none;" />
          </div>
          <div class="md:col-span-3">
            <label style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">Notas (opcional)</label>
            <input v-model="form.notes" type="text" placeholder="¿Cómo te sientes?"
              style="width:100%;background:#0D0D0D;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 14px;color:#fff;font-size:15px;outline:none;" />
          </div>
          <div class="md:col-span-3 flex gap-3">
            <button type="submit" :disabled="form.processing"
              class="font-bold" style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:12px 24px;font-size:14px;cursor:pointer;"
              :style="form.processing ? 'opacity:0.6;' : ''">
              {{ form.processing ? 'Guardando...' : 'Guardar' }}
            </button>
            <button type="button" @click="showForm = false"
              style="background:transparent;color:#9CA3AF;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 24px;font-size:14px;cursor:pointer;">
              Cancelar
            </button>
          </div>
        </form>
      </div>

      <!-- Stats cards -->
      <div class="grid grid-cols-3 gap-3 md:gap-4" style="margin-bottom:24px;">
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px md:20px;">
          <div style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Peso actual</div>
          <div class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;">{{ latest?.weight_kg ?? '—' }}<span style="font-size:14px;color:#9CA3AF;font-weight:500;"> kg</span></div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px;">
          <div style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Variación</div>
          <div class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;" :style="{ color: weightDelta < 0 ? '#1DF412' : weightDelta > 0 ? '#F59E0B' : '#fff' }">
            {{ weightDelta > 0 ? '+' : '' }}{{ weightDelta.toFixed(1) }}<span style="font-size:14px;color:#9CA3AF;font-weight:500;"> kg</span>
          </div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px;">
          <div style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Registros</div>
          <div class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;">{{ weightEntries.length }}</div>
        </div>
      </div>

      <!-- Weight chart -->
      <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:18px;padding:24px;margin-bottom:24px;">
        <h3 class="font-display font-bold" style="font-size:18px;margin-bottom:20px;">Evolución del peso</h3>

        <div v-if="weights.length >= 2">
          <svg :viewBox="`0 0 100 50`" preserveAspectRatio="none" style="width:100%;height:180px;display:block;">
            <defs>
              <linearGradient id="wgrad" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="#1DF412" stop-opacity="0.25"/>
                <stop offset="100%" stop-color="#1DF412" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <line v-for="p in [0.25, 0.5, 0.75]" :key="p" x1="4" x2="96" :y1="4 + p * 42" :y2="4 + p * 42" stroke="rgba(255,255,255,0.04)" stroke-width="0.2"/>
            <path :d="chartPath.area" fill="url(#wgrad)"/>
            <path :d="chartPath.line" fill="none" stroke="#1DF412" stroke-width="0.8" stroke-linecap="round" stroke-linejoin="round" style="filter:drop-shadow(0 0 4px #1DF412);"/>
            <circle v-for="(p, i) in chartPath.points" :key="i" :cx="p.x" :cy="p.y" :r="i === chartPath.points.length - 1 ? 1.2 : 0.6" :fill="i === chartPath.points.length - 1 ? '#1DF412' : 'rgba(255,255,255,0.4)'"/>
          </svg>
          <div class="flex justify-between" style="margin-top:8px;font-size:11px;color:#9CA3AF;">
            <span>{{ formatDate(first!.recorded_at) }}</span>
            <span>{{ formatDate(latest!.recorded_at) }}</span>
          </div>
        </div>

        <div v-else class="flex flex-col items-center text-center py-12">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          <p style="font-size:14px;color:#9CA3AF;">Registra al menos 2 medidas para ver tu evolución.</p>
        </div>
      </div>

      <!-- History list -->
      <div v-if="weightEntries.length > 0">
        <h3 class="font-display font-bold" style="font-size:18px;margin-bottom:12px;">Historial</h3>
        <div class="space-y-2">
          <div v-for="entry in [...weightEntries].reverse()" :key="entry.id"
            class="flex items-center justify-between" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:14px 16px;">
            <div>
              <div class="font-semibold" style="font-size:15px;">{{ entry.weight_kg }} kg</div>
              <div style="font-size:12px;color:#9CA3AF;">{{ formatDate(entry.recorded_at) }}</div>
            </div>
            <div class="flex gap-4 text-right">
              <div v-if="entry.body_fat_pct">
                <div style="font-size:11px;color:#9CA3AF;">Grasa</div>
                <div style="font-size:14px;font-weight:600;">{{ entry.body_fat_pct }}%</div>
              </div>
              <div v-if="entry.muscle_mass_kg">
                <div style="font-size:11px;color:#9CA3AF;">Músculo</div>
                <div style="font-size:14px;font-weight:600;">{{ entry.muscle_mass_kg }} kg</div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
