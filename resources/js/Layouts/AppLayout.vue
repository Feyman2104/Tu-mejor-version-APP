<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import type { User } from '@/types'

const page = usePage()
const user = computed(() => page.props.auth.user as User | null)

const navItems = [
  { name: 'Dashboard',    icon: 'home',      route: 'dashboard' },
  { name: 'Hoy',          icon: 'dumbbell',  route: 'workout.today' },
  { name: 'Chat',         icon: 'message',   route: 'chat.index' },
  { name: 'Postura',      icon: 'camera',    route: 'posture.index' },
  { name: 'Progreso',     icon: 'bar-chart', route: 'progress.index' },
]
</script>

<template>
  <div class="min-h-screen font-sans" style="background:#000;color:#fff;">

    <!-- Desktop sidebar -->
    <aside class="hidden md:flex flex-col fixed left-0 top-0 h-full w-64 z-40"
           style="background:#0D0D0D;border-right:1px solid rgba(255,255,255,0.06);">

      <!-- Logo -->
      <div class="p-6 border-b" style="border-color:rgba(255,255,255,0.06);">
        <Link :href="route('dashboard')" class="block">
          <span class="font-display font-black text-2xl uppercase text-white tracking-tight">
            Tu Mejor<br>
            <span style="color:#1DF412">Versión</span>
          </span>
        </Link>
      </div>

      <!-- Nav -->
      <nav class="flex-1 p-4 space-y-1">
        <Link
          v-for="item in navItems"
          :key="item.route"
          :href="route(item.route)"
          class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-150 group"
          :class="$page.url.startsWith('/' + item.route.replace('.', '/'))
            ? 'text-black font-bold'
            : 'text-muted hover:text-white hover:bg-surface'"
          :style="$page.url.startsWith('/' + item.route.replace('.', '/'))
            ? 'background:#1DF412;'
            : ''"
        >
          <span class="text-sm font-semibold">{{ item.name }}</span>
        </Link>
      </nav>

      <!-- User -->
      <div class="p-4 border-t" style="border-color:rgba(255,255,255,0.06);">
        <div v-if="user" class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm text-black"
               style="background:#1DF412;">
            {{ user.name.charAt(0).toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-white truncate">{{ user.name }}</div>
            <div class="text-xs capitalize" style="color:#9CA3AF">{{ user.role }}</div>
          </div>
          <Link :href="route('logout')" method="post" as="button"
                class="text-xs px-3 py-1.5 rounded-lg transition-colors"
                style="color:#9CA3AF;border:1px solid rgba(255,255,255,0.06);"
                onmouseover="this.style.color='#fff'"
                onmouseout="this.style.color='#9CA3AF'">
            Salir
          </Link>
        </div>
      </div>
    </aside>

    <!-- Main content desktop -->
    <main class="md:ml-64 min-h-screen">
      <!-- Flash messages -->
      <div v-if="$page.props.flash?.success"
           class="fixed top-4 right-4 z-50 px-4 py-3 rounded-xl text-black text-sm font-bold"
           style="background:#1DF412;box-shadow:0 0 20px rgba(29,244,18,0.4)">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.flash?.error"
           class="fixed top-4 right-4 z-50 px-4 py-3 rounded-xl text-white text-sm font-bold"
           style="background:#EF4444;">
        {{ $page.props.flash.error }}
      </div>

      <slot />
    </main>

    <!-- Mobile bottom nav -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 flex items-center justify-around px-2 pb-safe"
         style="background:#0D0D0D;border-top:1px solid rgba(255,255,255,0.06);height:64px;">
      <Link
        v-for="item in navItems"
        :key="item.route"
        :href="route(item.route)"
        class="flex flex-col items-center gap-1 px-3 py-2 rounded-xl transition-all"
        :style="$page.url.startsWith('/' + item.route.replace('.', '/'))
          ? 'color:#1DF412'
          : 'color:#6B7280'"
      >
        <span class="text-xs font-medium">{{ item.name }}</span>
      </Link>
    </nav>

  </div>
</template>
