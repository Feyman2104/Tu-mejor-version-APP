<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { User } from '@/types'

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
    name: 'Hoy',
    routeName: 'workout.today',
    urlPrefix: '/workout',
    icon: `<rect x="4" y="10" width="4" height="4" rx="1"/><rect x="16" y="10" width="4" height="4" rx="1"/><line x1="8" y1="12" x2="16" y2="12"/>`,
  },
  {
    name: 'Coach IA',
    routeName: 'chat.index',
    urlPrefix: '/chat',
    icon: `<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>`,
  },
  {
    name: 'Postura',
    routeName: 'posture.index',
    urlPrefix: '/posture',
    icon: `<circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 10-16 0"/>`,
  },
  {
    name: 'Progreso',
    routeName: 'progress.index',
    urlPrefix: '/progress',
    icon: `<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>`,
  },
]

// Determina si el ítem de navegación es el activo según la URL actual
function isActive(urlPrefix: string): boolean {
  const url = page.url
  return url === urlPrefix || url.startsWith(urlPrefix + '/')
}
</script>

<template>
  <div class="min-h-screen font-sans" style="background:#000;color:#fff;">

    <!-- Barra lateral fija en escritorio -->
    <aside class="hidden md:flex flex-col fixed left-0 top-0 h-full w-64 z-40"
           style="background:#0D0D0D;border-right:1px solid rgba(255,255,255,0.06);">

      <!-- Logo de la aplicación -->
      <div class="p-6 border-b" style="border-color:rgba(255,255,255,0.06);">
        <Link :href="route('dashboard')" class="block">
          <span class="font-display font-black text-2xl uppercase text-white tracking-tight">
            Tu Mejor<br>
            <span style="color:#1DF412">Versión</span>
          </span>
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
            <div class="text-xs capitalize" style="color:#9CA3AF">{{ user.role }}</div>
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

  </div>
</template>
