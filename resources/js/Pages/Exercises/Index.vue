<script setup lang="ts">
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import type { PaginatedResponse, Exercise } from '@/types'

defineOptions({ layout: AppLayout })

const props = defineProps<{
  exercises: PaginatedResponse<Exercise>
  filters: { search?: string; equipment?: string; level?: string }
}>()

const search = ref(props.filters.search ?? '')

let timeout: ReturnType<typeof setTimeout>
watch(search, (val) => {
  clearTimeout(timeout)
  timeout = setTimeout(() => {
    router.get(route('exercises.index'), { search: val }, { preserveState: true, replace: true })
  }, 350)
})
</script>

<template>
  <div class="min-h-screen relative" style="background:#000;">
    <div class="px-4 md:px-8 py-6 max-w-5xl mx-auto pb-24">

      <div style="margin-bottom:20px;">
        <h1 class="font-display font-bold" style="font-size:28px;letter-spacing:-0.02em;">Ejercicios</h1>
        <p style="font-size:14px;color:#9CA3AF;">Biblioteca de {{ exercises.total }} ejercicios guiados</p>
      </div>

      <!-- Search -->
      <div class="flex items-center mb-6" style="background:#161616;border:1.5px solid rgba(255,255,255,0.08);border-radius:14px;padding:4px 4px 4px 16px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input v-model="search" type="text" placeholder="Buscar ejercicio o grupo muscular..."
          class="flex-1 bg-transparent border-none outline-none"
          style="padding:12px 14px;color:#fff;font-size:15px;" />
      </div>

      <!-- Grid -->
      <div v-if="exercises.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Link v-for="ex in exercises.data" :key="ex.id" :href="route('exercises.show', { exercise: ex.slug })"
          style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:18px;text-decoration:none;display:block;transition:all 0.15s;">
          <div class="flex justify-between items-start" style="margin-bottom:10px;">
            <div class="flex items-center justify-center" style="width:44px;height:44px;border-radius:12px;background:rgba(29,244,18,0.08);color:#1DF412;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
            </div>
            <span style="font-size:10px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;background:rgba(29,244,18,0.08);border-radius:6px;padding:4px 8px;text-transform:capitalize;">
              {{ ex.level }}
            </span>
          </div>
          <h3 class="font-display font-bold" style="font-size:17px;letter-spacing:-0.01em;color:#fff;margin-bottom:4px;">{{ ex.name }}</h3>
          <p style="font-size:13px;color:#9CA3AF;text-transform:capitalize;">{{ ex.muscle_group }}</p>
        </Link>
      </div>

      <div v-else class="text-center py-20">
        <p style="font-size:14px;color:#9CA3AF;">No se encontraron ejercicios con "{{ search }}".</p>
      </div>

      <!-- Pagination -->
      <div v-if="exercises.last_page > 1" class="flex justify-center gap-2 mt-8">
        <Link v-for="link in exercises.links" :key="link.label"
          :href="link.url ?? '#'"
          v-html="link.label"
          class="flex items-center justify-center"
          style="min-width:40px;height:40px;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;padding:0 12px;"
          :style="link.active ? 'background:#1DF412;color:#000;' : link.url ? 'background:#161616;color:#fff;border:1px solid rgba(255,255,255,0.06);' : 'background:transparent;color:#6B7280;cursor:not-allowed;'"
        />
      </div>

    </div>
  </div>
</template>
