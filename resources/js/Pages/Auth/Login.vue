<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import LogoSVG from '@/Components/LogoSVG.vue'

defineOptions({ layout: GuestLayout })

const page = usePage()
const flashError = computed(() => page.props.flash?.error ?? null)

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)

function submit() {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <div class="min-h-screen font-sans relative overflow-hidden" style="background:#000;color:#fff;">

    <!-- Glow orbs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
      <div class="absolute rounded-full" style="top:-10%;left:-20%;width:380px;height:380px;background:radial-gradient(circle,rgba(29,244,18,0.2) 0%,transparent 65%);filter:blur(40px);"></div>
      <div class="absolute rounded-full" style="bottom:-5%;right:-25%;width:340px;height:340px;background:radial-gradient(circle,rgba(29,244,18,0.18) 0%,transparent 65%);filter:blur(40px);"></div>
    </div>

    <!-- ========== MOBILE LAYOUT ========== -->
    <div class="md:hidden flex flex-col min-h-screen relative z-10">

      <!-- Image header -->
      <div class="relative overflow-hidden flex-shrink-0" style="height:220px;">
        <img
          src="https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=900&h=1200&fit=crop&q=85"
          alt=""
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(0,0,0,0.4) 0%,rgba(0,0,0,0.2) 40%,rgba(0,0,0,0.95) 100%);"></div>
        <div class="absolute bottom-5 left-5">
          <div class="inline-flex items-center gap-2" style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.12em;">
            <span class="rounded-full" style="width:5px;height:5px;background:#1DF412;display:inline-block;"></span>
            Iniciar sesión
          </div>
        </div>
      </div>

      <!-- Form content -->
      <div class="flex-1 px-5 pt-6 pb-4 relative z-10">
        <h2 class="font-display font-bold" style="font-size:24px;line-height:1.15;letter-spacing:-0.02em;margin-bottom:8px;">
          Bienvenido<br><span style="color:#1DF412;">de vuelta.</span>
        </h2>
        <p style="font-size:13px;color:#9CA3AF;line-height:1.5;margin-bottom:20px;">
          Inicia sesión y continúa tu plan donde lo dejaste.
        </p>

        <!-- Flash error (social login, credenciales incorrectas, etc.) -->
        <div v-if="flashError"
          style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:12px 14px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span style="font-size:13px;color:#FCA5A5;line-height:1.4;">{{ flashError }}</span>
        </div>

        <!-- Social login -->
        <div style="margin-bottom:4px;">
          <div class="flex gap-2">
            <a href="/auth/google"
              class="flex-1 flex items-center justify-center gap-2 font-semibold"
              style="background:#161616;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 10px;font-size:13px;color:#fff;text-decoration:none;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
              Google
            </a>
            <a href="/auth/facebook"
              class="flex-1 flex items-center justify-center gap-2 font-semibold"
              style="background:#161616;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:12px 10px;font-size:13px;color:#fff;text-decoration:none;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              Facebook
            </a>
          </div>
          <div class="flex items-center gap-3" style="margin-top:14px;margin-bottom:18px;">
            <div class="flex-1" style="height:1px;background:rgba(255,255,255,0.06);"></div>
            <span style="font-size:11px;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">o con email</span>
            <div class="flex-1" style="height:1px;background:rgba(255,255,255,0.06);"></div>
          </div>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-3">
          <!-- Email -->
          <div>
            <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Email</div>
            <div class="flex items-center transition-all" style="background:#161616;border:1.5px solid;border-color:rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.email ? 'border-color:#EF4444' : ''">
              <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              </div>
              <input
                v-model="form.email"
                type="email"
                placeholder="tu@correo.edu.co"
                autocomplete="email"
                class="flex-1 bg-transparent border-none outline-none"
                style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;"
              />
            </div>
            <p v-if="form.errors.email" style="font-size:12px;color:#EF4444;margin-top:6px;">{{ form.errors.email }}</p>
          </div>

          <!-- Password -->
          <div>
            <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Contraseña</div>
            <div class="flex items-center transition-all" style="background:#161616;border:1.5px solid;border-color:rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.password ? 'border-color:#EF4444' : ''">
              <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </div>
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                autocomplete="current-password"
                class="flex-1 bg-transparent border-none outline-none"
                style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;"
              />
              <button type="button" @click="showPassword = !showPassword" style="background:transparent;border:none;cursor:pointer;padding:8px;display:flex;color:#9CA3AF;">
                <svg v-if="showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <p v-if="form.errors.password" style="font-size:12px;color:#EF4444;margin-top:6px;">{{ form.errors.password }}</p>
          </div>

          <!-- Remember + forgot -->
          <div class="flex justify-between items-center" style="margin-top:2px;">
            <label class="flex items-center gap-2 cursor-pointer" style="font-size:13px;color:#9CA3AF;">
              <input type="checkbox" v-model="form.remember" class="hidden" />
              <span
                class="flex items-center justify-center flex-shrink-0"
                style="width:16px;height:16px;border-radius:4px;cursor:pointer;transition:all 0.15s;"
                :style="form.remember ? 'background:#1DF412;border:1.5px solid #1DF412;' : 'background:transparent;border:1.5px solid rgba(255,255,255,0.2);'"
              >
                <svg v-if="form.remember" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              </span>
              Recordarme
            </label>
            <Link :href="route('password.request')" style="font-size:12px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#1DF412'" onmouseout="this.style.color='#9CA3AF'">
              ¿Olvidaste tu contraseña?
            </Link>
          </div>
        </form>
      </div>

      <!-- CTA sticky bottom -->
      <div class="flex-shrink-0 px-5 pb-7 pt-4" style="background:linear-gradient(to top,rgba(0,0,0,0.98) 50%,rgba(0,0,0,0.85) 90%,transparent 100%);">
        <button
          @click="submit"
          :disabled="form.processing"
          class="w-full flex items-center justify-center gap-2 font-bold transition-all"
          style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:16px 24px;font-size:16px;cursor:pointer;letter-spacing:-0.01em;"
          :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
        >
          {{ form.processing ? 'Iniciando sesión...' : 'Iniciar sesión' }}
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
        <div class="text-center mt-4" style="font-size:13px;color:#9CA3AF;">
          ¿No tienes cuenta?
          <Link :href="route('register')" style="color:#1DF412;font-weight:700;text-decoration:none;margin-left:4px;">Regístrate</Link>
        </div>
      </div>
    </div>

    <!-- ========== DESKTOP LAYOUT ========== -->
    <div class="hidden md:flex min-h-screen relative z-10">

      <!-- Left panel (image) -->
      <div class="relative overflow-hidden flex-shrink-0" style="width:42%;">
        <img
          src="https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=900&h=1200&fit=crop&q=85"
          alt=""
          class="w-full h-full object-cover"
          style="opacity:0.7;"
        />
        <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(0,0,0,0.6) 0%,rgba(0,0,0,0.4) 40%,rgba(0,0,0,0.85) 100%);"></div>

        <!-- Logo SVG -->
        <div class="absolute" style="top:48px;left:48px;">
          <LogoSVG variant="auth" size="medium" />
        </div>

        <!-- Microcopy center -->
        <div class="absolute" style="top:50%;left:48px;right:48px;transform:translateY(-50%);">
          <div class="flex items-center gap-2" style="font-size:12px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.15em;margin-bottom:20px;">
            <span class="rounded-full" style="width:6px;height:6px;background:#1DF412;display:inline-block;"></span>
            Iniciar sesión
          </div>
          <h3 class="font-display font-bold" style="font-size:36px;line-height:1.15;letter-spacing:-0.025em;color:#fff;margin-bottom:20px;text-shadow:0 2px 12px rgba(0,0,0,0.5);">
            Bienvenido de vuelta.
          </h3>
          <p style="font-size:15px;color:rgba(255,255,255,0.7);line-height:1.6;max-width:380px;text-shadow:0 1px 4px rgba(0,0,0,0.5);">
            Continúa donde lo dejaste. Tu rutina, tu progreso y tu coach te están esperando.
          </p>
        </div>

        <!-- Bottom decoration -->
        <div class="absolute flex justify-between" style="bottom:48px;left:48px;right:48px;font-size:12px;color:rgba(255,255,255,0.5);">
          <span>Tu Mejor Versión · 2026</span>
          <span>v1.0.0</span>
        </div>
      </div>

      <!-- Right panel (form) -->
      <div class="flex-1 flex flex-col relative">

        <!-- Top bar -->
        <div class="flex justify-between items-center" style="padding:32px 64px;">
          <button @click="router.visit(route('dashboard'))"
            class="flex items-center gap-2"
            style="background:transparent;border:none;font-size:14px;font-weight:500;padding:8px;cursor:pointer;color:#9CA3AF;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Volver
          </button>
          <div style="font-size:13px;color:#9CA3AF;">
            ¿No tienes cuenta?
            <Link :href="route('register')" style="color:#1DF412;font-weight:700;text-decoration:none;margin-left:4px;">Regístrate</Link>
          </div>
        </div>

        <!-- Form centered -->
        <div class="flex-1 flex items-center justify-center" style="padding:20px 64px;">
          <div style="width:100%;max-width:440px;">

            <h2 class="font-display font-bold" style="font-size:36px;line-height:1.15;letter-spacing:-0.02em;margin-bottom:8px;">
              Bienvenido<br><span style="color:#1DF412;">de vuelta.</span>
            </h2>
            <p style="font-size:15px;color:#9CA3AF;line-height:1.5;margin-bottom:20px;">
              Inicia sesión y continúa tu plan donde lo dejaste.
            </p>

            <!-- Flash error (social login, credenciales incorrectas, etc.) -->
            <div v-if="flashError"
              style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:12px 14px;margin-bottom:16px;display:flex;gap:10px;align-items:flex-start;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <span style="font-size:15px;color:#FCA5A5;line-height:1.4;">{{ flashError }}</span>
            </div>

            <!-- Social login -->
            <div style="margin-bottom:4px;">
              <div class="flex gap-3">
                <a href="/auth/google"
                  class="flex-1 flex items-center justify-center gap-2 font-semibold"
                  style="background:#161616;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:13px 16px;font-size:14px;color:#fff;text-decoration:none;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                  Continuar con Google
                </a>
                <a href="/auth/facebook"
                  class="flex-1 flex items-center justify-center gap-2 font-semibold"
                  style="background:#161616;border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:13px 16px;font-size:14px;color:#fff;text-decoration:none;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                  Continuar con Facebook
                </a>
              </div>
              <div class="flex items-center gap-3" style="margin-top:16px;margin-bottom:20px;">
                <div class="flex-1" style="height:1px;background:rgba(255,255,255,0.06);"></div>
                <span style="font-size:11px;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">o continúa con email</span>
                <div class="flex-1" style="height:1px;background:rgba(255,255,255,0.06);"></div>
              </div>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-3">
              <!-- Email -->
              <div>
                <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Email</div>
                <div class="flex items-center" style="background:#161616;border:1.5px solid rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.email ? 'border-color:#EF4444;' : ''">
                  <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                  </div>
                  <input
                    v-model="form.email"
                    type="email"
                    placeholder="tu@correo.edu.co"
                    autocomplete="email"
                    class="flex-1 bg-transparent border-none outline-none"
                    style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;"
                  />
                </div>
                <p v-if="form.errors.email" style="font-size:12px;color:#EF4444;margin-top:6px;">{{ form.errors.email }}</p>
              </div>

              <!-- Password -->
              <div>
                <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Contraseña</div>
                <div class="flex items-center" style="background:#161616;border:1.5px solid rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.password ? 'border-color:#EF4444;' : ''">
                  <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  </div>
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    class="flex-1 bg-transparent border-none outline-none"
                    style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;"
                  />
                  <button type="button" @click="showPassword = !showPassword" style="background:transparent;border:none;cursor:pointer;padding:8px;display:flex;color:#9CA3AF;">
                    <svg v-if="showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
                <p v-if="form.errors.password" style="font-size:12px;color:#EF4444;margin-top:6px;">{{ form.errors.password }}</p>
              </div>

              <!-- Remember -->
              <div class="flex justify-between items-center" style="margin-top:2px;margin-bottom:8px;">
                <label class="flex items-center gap-2 cursor-pointer" style="font-size:13px;color:#9CA3AF;">
                  <input type="checkbox" v-model="form.remember" class="hidden" />
                  <span
                    class="flex items-center justify-center flex-shrink-0"
                    style="width:16px;height:16px;border-radius:4px;cursor:pointer;transition:all 0.15s;"
                    :style="form.remember ? 'background:#1DF412;border:1.5px solid #1DF412;' : 'background:transparent;border:1.5px solid rgba(255,255,255,0.2);'"
                  >
                    <svg v-if="form.remember" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </span>
                  Recordarme
                </label>
                <Link :href="route('password.request')" style="font-size:13px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#1DF412'" onmouseout="this.style.color='#9CA3AF'">
                  ¿Olvidaste tu contraseña?
                </Link>
              </div>

              <!-- Submit -->
              <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 font-bold transition-all"
                style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:16px 24px;font-size:16px;cursor:pointer;letter-spacing:-0.01em;"
                :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
              >
                {{ form.processing ? 'Iniciando sesión...' : 'Iniciar sesión' }}
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
              </button>
            </form>
          </div>
        </div>

        <!-- Footer -->
        <div class="text-center" style="padding:24px 64px 32px;font-size:12px;color:#6B7280;">
          © 2026 Tu Mejor Versión ·
          <a href="#" style="color:inherit;text-decoration:none;">Términos</a> ·
          <a href="#" style="color:inherit;text-decoration:none;">Privacidad</a>
        </div>
      </div>
    </div>

  </div>
</template>
