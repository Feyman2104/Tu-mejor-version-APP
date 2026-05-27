<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { User } from '@/types'
import CoachModal from '@/Components/Coach/CoachModal.vue'
import LogoSVG from '@/Components/LogoSVG.vue'

const page = usePage()
const user = computed(() => page.props.auth.user as User | null)

// Ítems de navegación principal con la ruta del segmento URL para detección activa
const navItems = [
  {
    name: 'Dashboard',
    routeName: 'dashboard',
    urlPrefix: '/dashboard',
    icon: `<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>`,
  },
  {
    name: 'Entrenamiento',
    routeName: 'workout.index',
    urlPrefix: '/workout',
    icon: `<path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/>`,
  },
  {
    name: 'Progreso',
    routeName: 'progress.index',
    urlPrefix: '/progress',
    icon: `<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>`,
  },
  {
    name: 'Nutrición',
    routeName: 'nutrition.index',
    urlPrefix: '/nutrition',
    icon: `<path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>`,
  },
]

// Determina si el ítem de navegación es el activo según la URL actual
function isActive(urlPrefix: string): boolean {
  const url = page.url
  return url === urlPrefix || url.startsWith(urlPrefix + '/')
}

// Etiqueta de rol en español
const roleLabel = computed(() => {
  const map: Record<string, string> = { student: 'Estudiante', trainer: 'Entrenador', admin: 'Administrador' }
  return map[user.value?.role ?? ''] ?? (user.value?.role ?? '')
})

// FAB — Coach modal
const coachOpen = ref(false)

// Ocultar FAB solo en /chat y /posture (páginas completas que ya incluyen esa funcionalidad)
const hideFab = computed(() => {
  const url = page.url
  return url.startsWith('/chat') || url.startsWith('/posture')
})
</script>

<template>
  <div class="min-h-screen font-sans" style="background:#000;color:#fff;">

    <!-- Barra lateral fija en escritorio -->
    <aside class="hidden md:flex flex-col fixed left-0 top-0 h-full w-64 z-40"
           style="background:#0D0D0D;border-right:1px solid rgba(255,255,255,0.06);">

      <!-- Logo de la aplicación -->
      <div class="p-6 border-b flex items-center" style="border-color:rgba(255,255,255,0.06);">
        <Link :href="route('dashboard')" class="block">
          <LogoSVG variant="sidebar" />
        </Link>
      </div>

      <!-- Menú de navegación principal -->
      <nav class="flex-1 p-4 space-y-1">
        <Link
          v-for="item in navItems"
          :key="item.routeName"
          :href="route(item.routeName)"
          class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-150"
          :class="isActive(item.urlPrefix)
            ? 'text-black font-bold'
            : 'hover:text-white'"
          :style="isActive(item.urlPrefix)
            ? 'background:#1DF412;color:#000;'
            : 'color:#9CA3AF;'"
          onmouseover="if(!this.style.background.includes('1DF412'))this.style.color='#fff'"
          onmouseout="if(!this.style.background.includes('1DF412'))this.style.color='#9CA3AF'"
        >
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
               class="flex-shrink-0" aria-hidden="true" v-html="item.icon" />
          <span class="text-sm font-semibold">{{ item.name }}</span>
        </Link>
      </nav>

      <!-- Sección de usuario y botón de cerrar sesión -->
      <div class="p-4 border-t" style="border-color:rgba(255,255,255,0.06);">
        <div v-if="user" class="flex items-center gap-3">
          <!-- Avatar con inicial del nombre -->
          <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm text-black flex-shrink-0"
               style="background:#1DF412;">
            {{ user.name.charAt(0).toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-white truncate">{{ user.name }}</div>
            <div class="text-xs" style="color:#9CA3AF">{{ roleLabel }}</div>
          </div>
          <Link :href="route('logout')" method="post" as="button"
                class="text-xs px-3 py-1.5 rounded-lg transition-colors flex-shrink-0"
                style="color:#9CA3AF;border:1px solid rgba(255,255,255,0.06);"
                onmouseover="this.style.color='#fff';this.style.borderColor='rgba(255,255,255,0.2)'"
                onmouseout="this.style.color='#9CA3AF';this.style.borderColor='rgba(255,255,255,0.06)'">
            Salir
          </Link>
        </div>
      </div>
    </aside>

    <!-- Contenido principal con margen para la barra lateral en escritorio -->
    <main class="md:ml-64 min-h-screen pb-16 md:pb-0">

      <!-- Notificaciones flash de éxito -->
      <div v-if="$page.props.flash?.success"
           class="fixed top-4 right-4 z-50 px-4 py-3 rounded-xl text-black text-sm font-bold"
           style="background:#1DF412;box-shadow:0 0 20px rgba(29,244,18,0.4);max-width:320px;">
        {{ $page.props.flash.success }}
      </div>

      <!-- Notificaciones flash de error -->
      <div v-if="$page.props.flash?.error"
           class="fixed top-4 right-4 z-50 px-4 py-3 rounded-xl text-white text-sm font-bold"
           style="background:#EF4444;max-width:320px;">
        {{ $page.props.flash.error }}
      </div>

      <slot />
    </main>

    <!-- Barra de navegación inferior en móvil -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 flex items-center justify-around"
         style="background:#0D0D0D;border-top:1px solid rgba(255,255,255,0.06);height:64px;padding-bottom:env(safe-area-inset-bottom,0);">
      <Link
        v-for="item in navItems"
        :key="item.routeName"
        :href="route(item.routeName)"
        class="flex flex-col items-center gap-0.5 px-2 py-2 rounded-xl transition-all flex-1"
        :style="isActive(item.urlPrefix) ? 'color:#1DF412' : 'color:#6B7280'"
      >
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true" v-html="item.icon" />
        <span class="text-xs font-semibold leading-tight text-center">{{ item.name }}</span>
      </Link>
    </nav>

    <!-- ── Coach FAB ── -->
    <Transition name="fab">
      <button v-if="!hideFab"
        @click="coachOpen = true"
        style="position:fixed;bottom:80px;right:20px;z-index:50;width:56px;height:56px;border-radius:50%;background:#1DF412;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 24px rgba(29,244,18,0.45);transition:transform 0.15s,box-shadow 0.15s;"
        class="md:bottom-8 md:right-8"
        onmouseover="this.style.transform='scale(1.08)';this.style.boxShadow='0 6px 32px rgba(29,244,18,0.6)'"
        onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 4px 24px rgba(29,244,18,0.45)'"
        aria-label="Abrir Coach IA"
      >
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
        </svg>
      </button>
    </Transition>

    <!-- ── Coach Modal ── -->
    <CoachModal :open="coachOpen" @close="coachOpen = false" />

  </div>
</template>

<style scoped>
/* FAB entry/exit */
.fab-enter-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fab-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.fab-enter-from, .fab-leave-to { opacity: 0; transform: scale(0.7); }

/* Desktop: FAB queda a la derecha del sidebar (ml-64) */
@media (min-width: 768px) {
  button[aria-label="Abrir Coach IA"] {
    bottom: 32px !important;
    right: 32px !important;
  }
}
</style>
