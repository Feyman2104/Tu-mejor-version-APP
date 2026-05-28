<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { ProgressEntry } from '@/types'

defineOptions({ layout: AppLayout })

interface PR { exercise_name: string; max_weight_kg: number }

const props = defineProps<{
  entries: ProgressEntry[]
  autoBodyFat: number | null
  streak: number
  workoutsThisWeek: number
  workoutsThisMonth: number
  weeklyVolume: number
  prs: PR[]
}>()

// ─── Formulario de medida ─────────────────────────────────────────────────────
const showForm = ref(false)

const form = useForm({
  weight_kg:      '',
  body_fat_pct:   '',
  muscle_mass_kg: '',
  notes:          '',
})

function submit(): void {
  form.post(route('progress.store'), {
    onSuccess: () => { form.reset(); showForm.value = false },
  })
}

// ─── Calculadora Navy ─────────────────────────────────────────────────────────
const showNavy    = ref(false)
const navySex     = ref<'male' | 'female'>('male')
const navyHeight  = ref<number | ''>('')
const navyNeck    = ref<number | ''>('')
const navyWaist   = ref<number | ''>('')
const navyHip     = ref<number | ''>('')
const navyResult  = ref<number | null>(null)

function calcNavy(): void {
  const h = Number(navyHeight.value)
  const n = Number(navyNeck.value)
  const w = Number(navyWaist.value)
  const hip = navySex.value === 'female' ? Number(navyHip.value) : undefined

  if (!h || !n || !w || (navySex.value === 'female' && !hip)) return

  // U.S. Navy (Hodgdon & Beckett 1984)
  let pct: number
  if (navySex.value === 'male') {
    const denom = 1.0324 - 0.19077 * Math.log10(Math.max(0.01, w - n)) + 0.15456 * Math.log10(h)
    pct = 495 / denom - 450
  } else {
    const denom = 1.29579 - 0.35004 * Math.log10(Math.max(0.01, w + hip! - n)) + 0.22100 * Math.log10(h)
    pct = 495 / denom - 450
  }
  navyResult.value = Math.round(Math.max(2, Math.min(65, pct)) * 10) / 10
}

function applyNavy(): void {
  if (navyResult.value !== null) {
    form.body_fat_pct = String(navyResult.value)
    showNavy.value = false
    showForm.value = true
  }
}

// ─── Cálculos de peso ─────────────────────────────────────────────────────────
const weightEntries = computed(() =>
  [...props.entries]
    .filter(e => e.weight_kg != null)
    .sort((a, b) => new Date(a.recorded_at).getTime() - new Date(b.recorded_at).getTime())
)

const weights   = computed(() => weightEntries.value.map(e => Number(e.weight_kg)))
const latest    = computed(() => weightEntries.value.at(-1) ?? null)
const first     = computed(() => weightEntries.value.at(0) ?? null)

const weightDelta = computed(() => {
  if (!latest.value || !first.value) return 0
  return Number(latest.value.weight_kg) - Number(first.value.weight_kg)
})

// ─── Gráfica ──────────────────────────────────────────────────────────────────
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

function formatDate(d: string): string {
  return new Date(d).toLocaleDateString('es', { day: 'numeric', month: 'short' })
}

function fmtVol(kg: number): string {
  if (kg >= 1000) return `${(kg / 1000).toFixed(1)} t`
  return `${Math.round(kg)} kg`
}
</script>

<template>
  <div class="min-h-screen relative" style="background:#000;color:#fff;">
    <div class="px-4 md:px-8 py-6 max-w-4xl mx-auto pb-28">

      <!-- ── Header ───────────────────────────────────────────────────────── -->
      <div class="flex justify-between items-center" style="margin-bottom:24px;">
        <div>
          <h1 class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;">Mi Progreso</h1>
          <p style="font-size:14px;color:#9CA3AF;">Registra y visualiza tu evolución</p>
        </div>
        <button @click="showForm = !showForm"
          class="flex items-center gap-2 font-bold flex-shrink-0"
          style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:12px 18px;font-size:14px;cursor:pointer;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          <span class="hidden md:inline">Registrar medida</span>
        </button>
      </div>

      <!-- ── Calculadora Navy (modal) ─────────────────────────────────────── -->
      <Teleport to="body">
        <div v-if="showNavy" style="position:fixed;inset:0;z-index:999;background:rgba(0,0,0,0.85);display:flex;align-items:flex-end;" @click.self="showNavy = false">
          <div style="width:100%;background:#111;border-radius:20px 20px 0 0;padding:24px;max-height:90vh;overflow-y:auto;">
            <div style="width:40px;height:4px;background:rgba(255,255,255,0.15);border-radius:2px;margin:0 auto 16px;"></div>
            <h3 style="font-size:17px;font-weight:700;margin-bottom:4px;">Calculadora U.S. Navy</h3>
            <p style="font-size:12px;color:#6B7280;margin-bottom:16px;">Mide cuello, cintura (y cadera si eres mujer) con una cinta métrica. Precisión ±3.5% (Hodgdon &amp; Beckett, 1984).</p>

            <!-- Sexo -->
            <div style="margin-bottom:14px;">
              <label style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">Sexo</label>
              <div class="flex gap-2">
                <button @click="navySex = 'male'"
                  :style="navySex === 'male' ? 'background:#1DF412;color:#000;' : 'background:#1a1a1a;color:#9CA3AF;'"
                  style="flex:1;padding:10px;border-radius:10px;font-size:13px;font-weight:700;">Hombre</button>
                <button @click="navySex = 'female'"
                  :style="navySex === 'female' ? 'background:#1DF412;color:#000;' : 'background:#1a1a1a;color:#9CA3AF;'"
                  style="flex:1;padding:10px;border-radius:10px;font-size:13px;font-weight:700;">Mujer</button>
              </div>
            </div>

            <!-- Medidas -->
            <div class="grid grid-cols-2 gap-3 mb-4">
              <div>
                <label style="font-size:11px;color:#9CA3AF;font-weight:600;display:block;margin-bottom:4px;">Altura (cm)</label>
                <input v-model.number="navyHeight" type="number" placeholder="175"
                  style="width:100%;background:#1a1a1a;border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:10px;color:#fff;font-size:15px;" />
              </div>
              <div>
                <label style="font-size:11px;color:#9CA3AF;font-weight:600;display:block;margin-bottom:4px;">Cuello (cm)</label>
                <input v-model.number="navyNeck" type="number" placeholder="38"
                  style="width:100%;background:#1a1a1a;border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:10px;color:#fff;font-size:15px;" />
              </div>
              <div>
                <label style="font-size:11px;color:#9CA3AF;font-weight:600;display:block;margin-bottom:4px;">Cintura (cm)</label>
                <input v-model.number="navyWaist" type="number" placeholder="82"
                  style="width:100%;background:#1a1a1a;border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:10px;color:#fff;font-size:15px;" />
              </div>
              <div v-if="navySex === 'female'">
                <label style="font-size:11px;color:#9CA3AF;font-weight:600;display:block;margin-bottom:4px;">Cadera (cm)</label>
                <input v-model.number="navyHip" type="number" placeholder="96"
                  style="width:100%;background:#1a1a1a;border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:10px;color:#fff;font-size:15px;" />
              </div>
            </div>

            <button @click="calcNavy" style="width:100%;padding:12px;border-radius:12px;background:#3B82F6;color:#fff;font-size:14px;font-weight:700;margin-bottom:12px;">
              Calcular % grasa
            </button>

            <div v-if="navyResult !== null" style="background:rgba(29,244,18,0.06);border:1px solid rgba(29,244,18,0.2);border-radius:14px;padding:16px;margin-bottom:12px;text-align:center;">
              <div style="font-size:11px;color:#6B7280;margin-bottom:4px;">Resultado estimado</div>
              <div style="font-size:36px;font-weight:900;color:#1DF412;letter-spacing:-0.02em;">{{ navyResult }}%</div>
              <div style="font-size:11px;color:#9CA3AF;margin-top:4px;">Precisión ±3.5% vs DEXA · Método U.S. Navy</div>
              <button @click="applyNavy" style="margin-top:12px;padding:10px 24px;border-radius:10px;background:#1DF412;color:#000;font-size:13px;font-weight:700;">
                Usar este valor
              </button>
            </div>

            <button @click="showNavy = false" style="width:100%;padding:11px;border-radius:12px;background:rgba(255,255,255,0.06);color:#9CA3AF;font-size:13px;">
              Cerrar
            </button>
          </div>
        </div>
      </Teleport>

      <!-- ── Formulario nueva medida ───────────────────────────────────────── -->
      <div v-if="showForm" style="background:#161616;border:1px solid rgba(29,244,18,0.2);border-radius:18px;padding:24px;margin-bottom:24px;">
        <h3 class="font-display font-bold" style="font-size:18px;margin-bottom:16px;">Nueva medida</h3>

        <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">Peso (kg)</label>
            <input v-model="form.weight_kg" type="number" step="0.1" placeholder="72.5"
              style="width:100%;background:#0D0D0D;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 14px;color:#fff;font-size:15px;" />
          </div>

          <!-- % Grasa con estimación automática + Navy -->
          <div>
            <div class="flex items-center justify-between" style="margin-bottom:6px;">
              <label style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">% Grasa</label>
              <button type="button" @click="showNavy = true"
                style="font-size:10px;color:#3B82F6;font-weight:600;background:rgba(59,130,246,0.1);padding:3px 8px;border-radius:6px;">
                Calcular con cinta
              </button>
            </div>
            <input v-model="form.body_fat_pct" type="number" step="0.1"
              :placeholder="autoBodyFat ? `~${autoBodyFat} (estimado)` : '18'"
              style="width:100%;background:#0D0D0D;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 14px;color:#fff;font-size:15px;" />
            <div v-if="autoBodyFat && !form.body_fat_pct" style="font-size:11px;color:#6B7280;margin-top:4px;">
              Estimado con CUN-BAE: ~{{ autoBodyFat }}% (±3.5%). Ajusta o usa la calculadora.
            </div>
          </div>

          <div>
            <label style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">Masa muscular (kg)</label>
            <input v-model="form.muscle_mass_kg" type="number" step="0.1" placeholder="35"
              style="width:100%;background:#0D0D0D;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 14px;color:#fff;font-size:15px;" />
          </div>
          <div class="md:col-span-3">
            <label style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;display:block;margin-bottom:6px;">Notas (opcional)</label>
            <input v-model="form.notes" type="text" placeholder="¿Cómo te sientes?"
              style="width:100%;background:#0D0D0D;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 14px;color:#fff;font-size:15px;" />
          </div>
          <div class="md:col-span-3 flex gap-3">
            <button type="submit" :disabled="form.processing"
              style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:12px 24px;font-size:14px;font-weight:700;cursor:pointer;"
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

      <!-- ── Estimación automática (sin form) ─────────────────────────────── -->
      <div v-if="autoBodyFat && !showForm"
        style="background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.2);border-radius:14px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div class="flex items-center gap-3">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z"/><path d="M12 8v4"/><circle cx="12" cy="16" r="0.5" fill="#3B82F6"/></svg>
          <div>
            <div style="font-size:13px;font-weight:600;">% Grasa estimado: <span style="color:#60A5FA;">~{{ autoBodyFat }}%</span></div>
            <div style="font-size:11px;color:#6B7280;">Ecuación CUN-BAE (Gómez-Ambrosi 2012) · ±3.5%. Sin cinta métrica.</div>
          </div>
        </div>
        <button @click="showNavy = true"
          style="flex-shrink:0;font-size:11px;color:#3B82F6;font-weight:600;background:rgba(59,130,246,0.1);padding:6px 12px;border-radius:8px;white-space:nowrap;">
          Precisar con cinta
        </button>
      </div>

      <!-- ── Stats de peso ─────────────────────────────────────────────────── -->
      <div class="grid grid-cols-3 gap-3 md:gap-4" style="margin-bottom:20px;">
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px;">
          <div style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Peso actual</div>
          <div class="font-display font-bold" style="font-size:26px;letter-spacing:-0.02em;">{{ latest?.weight_kg ?? '—' }}<span style="font-size:13px;color:#9CA3AF;font-weight:500;"> kg</span></div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px;">
          <div style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Variación</div>
          <div class="font-display font-bold" style="font-size:26px;letter-spacing:-0.02em;"
            :style="{ color: weightDelta < 0 ? '#1DF412' : weightDelta > 0 ? '#F59E0B' : '#fff' }">
            {{ weightDelta > 0 ? '+' : '' }}{{ weightDelta.toFixed(1) }}<span style="font-size:13px;color:#9CA3AF;font-weight:500;"> kg</span>
          </div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:16px;">
          <div style="font-size:11px;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Registros</div>
          <div class="font-display font-bold" style="font-size:26px;letter-spacing:-0.02em;">{{ weightEntries.length }}</div>
        </div>
      </div>

      <!-- ── Métricas de entrenamiento ──────────────────────────────────────── -->
      <h3 style="font-size:12px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">Entrenamiento</h3>
      <div class="grid grid-cols-2 gap-3 md:grid-cols-4" style="margin-bottom:20px;">
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:14px;text-align:center;">
          <div style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:6px;">Racha</div>
          <div style="font-size:26px;font-weight:900;color:#F59E0B;">{{ streak }}</div>
          <div style="font-size:10px;color:#6B7280;">días</div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:14px;text-align:center;">
          <div style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:6px;">Esta semana</div>
          <div style="font-size:26px;font-weight:900;color:#1DF412;">{{ workoutsThisWeek }}</div>
          <div style="font-size:10px;color:#6B7280;">sesiones</div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:14px;text-align:center;">
          <div style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:6px;">Este mes</div>
          <div style="font-size:26px;font-weight:900;color:#fff;">{{ workoutsThisMonth }}</div>
          <div style="font-size:10px;color:#6B7280;">sesiones</div>
        </div>
        <div style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:14px;text-align:center;">
          <div style="font-size:10px;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:6px;">Volumen sem.</div>
          <div style="font-size:22px;font-weight:900;color:#A78BFA;">{{ fmtVol(weeklyVolume) }}</div>
          <div style="font-size:10px;color:#6B7280;">reps × peso</div>
        </div>
      </div>

      <!-- PRs -->
      <div v-if="prs.length" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:20px;margin-bottom:24px;">
        <h3 style="font-size:14px;font-weight:700;margin-bottom:14px;">🏆 Récords personales</h3>
        <div class="flex flex-col gap-2">
          <div v-for="(pr, i) in prs" :key="i" class="flex items-center justify-between"
            style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
            <div class="flex items-center gap-3">
              <span style="font-size:12px;font-weight:700;color:#F59E0B;width:18px;">{{ i + 1 }}</span>
              <span style="font-size:14px;color:#E5E7EB;">{{ pr.exercise_name }}</span>
            </div>
            <span style="font-size:14px;font-weight:700;color:#fff;">{{ pr.max_weight_kg }} kg</span>
          </div>
        </div>
      </div>
      <div v-else style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:14px;padding:16px;margin-bottom:24px;text-align:center;">
        <p style="font-size:13px;color:#6B7280;">Completa sesiones con peso para ver tus récords personales.</p>
      </div>

      <!-- ── Gráfica de peso ─────────────────────────────────────────────── -->
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
            <circle v-for="(pt, i) in chartPath.points" :key="i"
              :cx="pt.x" :cy="pt.y"
              :r="i === chartPath.points.length - 1 ? 1.2 : 0.6"
              :fill="i === chartPath.points.length - 1 ? '#1DF412' : 'rgba(255,255,255,0.4)'"/>
          </svg>
          <div class="flex justify-between" style="margin-top:8px;font-size:11px;color:#9CA3AF;">
            <span>{{ formatDate(first!.recorded_at) }}</span>
            <span>{{ formatDate(latest!.recorded_at) }}</span>
          </div>
        </div>

        <div v-else class="flex flex-col items-center text-center py-12">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          <p style="font-size:14px;color:#9CA3AF;margin-top:12px;">Registra al menos 2 medidas para ver tu evolución.</p>
        </div>
      </div>

      <!-- ── Historial ──────────────────────────────────────────────────────── -->
      <div v-if="weightEntries.length > 0">
        <h3 class="font-display font-bold" style="font-size:18px;margin-bottom:12px;">Historial</h3>
        <div class="space-y-2">
          <div v-for="entry in [...weightEntries].reverse()" :key="entry.id"
            class="flex items-center justify-between"
            style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:14px 16px;">
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
