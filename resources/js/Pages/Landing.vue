<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineOptions({ layout: GuestLayout })

const features = [
  { title: 'Rutinas IA', desc: 'Planes adaptativos según tu nivel, objetivos y equipo disponible. Cambian cada semana.', icon: 'dumbbell' },
  { title: 'Análisis de postura', desc: 'Corrección en tiempo real con tu cámara. Reduce el riesgo de lesiones.', icon: 'camera' },
  { title: 'Progreso medible', desc: 'Gráficas claras de fuerza, volumen, consistencia y cumplimiento.', icon: 'chart' },
  { title: 'Coach virtual 24/7', desc: 'Respuestas instantáneas a cualquier duda. Disponible siempre.', icon: 'message' },
]

const stats = [
  { value: '+15K', label: 'Estudiantes activos' },
  { value: '20+', label: 'Universidades' },
  { value: '4.9', label: 'Valoración media' },
  { value: '80+', label: 'Ejercicios guiados' },
]
</script>

<template>
  <div class="min-h-screen relative overflow-hidden" style="background:#000;color:#fff;">

    <!-- Glow orbs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
      <div class="absolute rounded-full" style="top:5%;right:5%;width:500px;height:500px;background:radial-gradient(circle,rgba(29,244,18,0.15) 0%,transparent 65%);filter:blur(40px);"></div>
      <div class="absolute rounded-full" style="bottom:15%;left:15%;width:450px;height:450px;background:radial-gradient(circle,rgba(29,244,18,0.13) 0%,transparent 65%);filter:blur(40px);"></div>
    </div>

    <div class="relative z-10">

      <!-- TOP NAV -->
      <nav class="flex justify-between items-center px-6 md:px-16 py-5">
        <Link :href="route('landing')" class="font-display font-black uppercase tracking-tight" style="font-size:22px;">
          Tu Mejor<span style="color:#1DF412;">Versión</span>
        </Link>
        <div class="flex items-center gap-2 md:gap-3">
          <Link :href="route('login')"
            class="font-semibold transition-colors hidden sm:inline-flex"
            style="color:#fff;border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:10px 20px;font-size:14px;text-decoration:none;">
            Iniciar sesión
          </Link>
          <Link :href="route('register')"
            class="font-bold"
            style="background:#1DF412;color:#000;border-radius:12px;padding:10px 20px;font-size:14px;text-decoration:none;">
            Comenzar gratis
          </Link>
        </div>
      </nav>

      <!-- HERO -->
      <section class="grid items-center gap-12 px-6 md:px-16 py-12 md:py-20" style="grid-template-columns:1fr;" :class="'lg:grid-cols-2'">
        <div>
          <div class="inline-flex items-center gap-2 mb-6" style="font-size:12px;font-weight:600;color:#1DF412;text-transform:uppercase;letter-spacing:0.15em;">
            <span class="rounded-full" style="width:6px;height:6px;background:#1DF412;display:inline-block;"></span>
            Fitness inteligente
          </div>
          <h1 class="font-display font-black uppercase" style="font-size:48px;line-height:0.98;letter-spacing:0.01em;margin-bottom:24px;">
            Transforma tu cuerpo,<br>
            <span style="color:#1DF412;">tu rutina</span>, tu vida.
          </h1>
          <p style="font-size:18px;color:#9CA3AF;line-height:1.6;margin-bottom:32px;max-width:480px;">
            Rutinas personalizadas con IA, análisis de postura en tiempo real y seguimiento de progreso. Hecho para estudiantes universitarios.
          </p>
          <div class="flex flex-wrap gap-3 mb-10">
            <Link :href="route('register')"
              class="inline-flex items-center gap-2 font-bold"
              style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:18px 28px;font-size:16px;text-decoration:none;">
              Comenzar gratis
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </Link>
            <Link :href="route('login')"
              class="inline-flex items-center gap-2 font-semibold"
              style="background:#1C1C1C;color:#fff;border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:18px 28px;font-size:16px;text-decoration:none;">
              Ya tengo cuenta
            </Link>
          </div>
          <!-- Trust strip -->
          <div class="flex items-center gap-2">
            <div class="flex items-center gap-1">
              <svg v-for="i in 5" :key="i" width="14" height="14" viewBox="0 0 24 24" fill="#1DF412" stroke="#1DF412"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <span style="font-size:13px;font-weight:600;margin-left:8px;">4.9</span>
            </div>
            <span style="font-size:13px;color:#9CA3AF;margin-left:8px;"><strong style="color:#fff;">+15,000 estudiantes</strong> en 20+ universidades</span>
          </div>
        </div>

        <!-- Hero image -->
        <div class="relative hidden lg:block">
          <div class="relative overflow-hidden" style="border-radius:24px;aspect-ratio:4/5;">
            <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&h=1000&fit=crop&q=85" alt="Estudiante entrenando" class="w-full h-full object-cover" />
            <div class="absolute inset-0" style="background:linear-gradient(180deg,transparent 40%,rgba(0,0,0,0.4) 100%);"></div>
          </div>
          <!-- Floating stat 1 -->
          <div class="absolute flex items-center gap-3" style="top:32px;left:-32px;background:rgba(13,13,13,0.95);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:14px 18px;box-shadow:0 12px 32px rgba(0,0,0,0.5);">
            <div class="flex items-center justify-center" style="width:40px;height:40px;border-radius:10px;background:rgba(29,244,18,0.08);color:#1DF412;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </div>
            <div>
              <div style="font-size:11px;color:#9CA3AF;margin-bottom:2px;">SESIÓN HOY</div>
              <div style="font-size:16px;font-weight:700;">42 min · 387 kcal</div>
            </div>
          </div>
          <!-- Floating stat 2 -->
          <div class="absolute flex items-center gap-3" style="bottom:60px;right:-32px;background:rgba(13,13,13,0.95);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:14px 18px;box-shadow:0 12px 32px rgba(0,0,0,0.5);">
            <div class="flex items-center justify-center" style="width:40px;height:40px;border-radius:10px;background:rgba(29,244,18,0.08);color:#1DF412;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
            </div>
            <div>
              <div style="font-size:11px;color:#9CA3AF;margin-bottom:2px;">RACHA ACTIVA</div>
              <div style="font-size:16px;font-weight:700;">14 días seguidos</div>
            </div>
          </div>
        </div>
      </section>

      <!-- STATS BAR -->
      <section class="px-6 md:px-16 pb-16 md:pb-24">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="stat in stats" :key="stat.label"
            class="text-center" style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:18px;padding:28px 20px;">
            <div class="font-display font-black" style="font-size:40px;letter-spacing:-0.02em;color:#1DF412;line-height:1;">{{ stat.value }}</div>
            <div style="font-size:13px;color:#9CA3AF;margin-top:8px;">{{ stat.label }}</div>
          </div>
        </div>
      </section>

      <!-- FEATURES -->
      <section class="px-6 md:px-16 pb-16 md:pb-24">
        <div class="text-center mb-14">
          <div class="inline-flex items-center gap-2 mb-4" style="font-size:12px;font-weight:600;color:#1DF412;text-transform:uppercase;letter-spacing:0.15em;">
            <span class="rounded-full" style="width:6px;height:6px;background:#1DF412;display:inline-block;"></span>
            Características
          </div>
          <h2 class="font-display font-black uppercase" style="font-size:40px;line-height:1;letter-spacing:0.01em;max-width:640px;margin:0 auto;">
            Todo lo que necesitas para <span style="color:#1DF412;">entrenar mejor.</span>
          </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
          <div v-for="f in features" :key="f.title"
            style="background:#161616;border:1px solid rgba(255,255,255,0.06);border-radius:18px;padding:28px;">
            <div class="flex items-center justify-center mb-5" style="width:52px;height:52px;border-radius:14px;background:rgba(29,244,18,0.08);border:1px solid rgba(29,244,18,0.2);color:#1DF412;">
              <svg v-if="f.icon === 'dumbbell'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
              <svg v-if="f.icon === 'camera'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              <svg v-if="f.icon === 'chart'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
              <svg v-if="f.icon === 'message'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            </div>
            <h3 class="font-display font-bold" style="font-size:20px;letter-spacing:-0.01em;margin-bottom:8px;">{{ f.title }}</h3>
            <p style="font-size:14px;color:#9CA3AF;line-height:1.6;">{{ f.desc }}</p>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="px-6 md:px-16 pb-20">
        <div class="relative overflow-hidden text-center" style="background:linear-gradient(135deg,#161616 0%,#0D0D0D 100%);border:1px solid rgba(29,244,18,0.2);border-radius:24px;padding:60px 24px;">
          <div class="absolute rounded-full" style="top:-40px;right:10%;width:300px;height:300px;background:radial-gradient(circle,rgba(29,244,18,0.15) 0%,transparent 70%);filter:blur(30px);"></div>
          <div class="relative">
            <h2 class="font-display font-black uppercase" style="font-size:36px;line-height:1.05;letter-spacing:0.01em;margin-bottom:16px;">
              Empieza <span style="color:#1DF412;">tu mejor versión</span> hoy.
            </h2>
            <p style="font-size:16px;color:#9CA3AF;max-width:480px;margin:0 auto 28px;line-height:1.6;">
              Crea tu cuenta gratis y obtén tu primer plan personalizado con IA en menos de 2 minutos.
            </p>
            <Link :href="route('register')"
              class="inline-flex items-center gap-2 font-bold"
              style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:18px 32px;font-size:16px;text-decoration:none;">
              Crear cuenta gratis
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </Link>
          </div>
        </div>
      </section>

      <!-- FOOTER -->
      <footer class="px-6 md:px-16 py-8 flex flex-col md:flex-row justify-between items-center gap-4" style="border-top:1px solid rgba(255,255,255,0.06);">
        <span class="font-display font-black uppercase tracking-tight" style="font-size:16px;">Tu Mejor<span style="color:#1DF412;">Versión</span></span>
        <span style="font-size:13px;color:#6B7280;">© 2026 Tu Mejor Versión · Hecho para estudiantes</span>
      </footer>

    </div>
  </div>
</template>
