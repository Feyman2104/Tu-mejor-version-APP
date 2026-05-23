<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineOptions({ layout: GuestLayout })

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)

const passwordStrength = computed(() => {
  const len = form.password.length
  if (len >= 12) return { label: 'Fuerte', color: '#1DF412', width: '100%' }
  if (len >= 8)  return { label: 'Buena',  color: '#1DF412', width: `${(len / 12) * 100}%` }
  if (len > 0)   return { label: 'Débil',  color: '#F59E0B', width: `${(len / 12) * 100}%` }
  return null
})

function submit() {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
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
          src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&h=1200&fit=crop&q=85"
          alt=""
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(0,0,0,0.4) 0%,rgba(0,0,0,0.2) 40%,rgba(0,0,0,0.95) 100%);"></div>
        <div class="absolute bottom-5 left-5">
          <div class="inline-flex items-center gap-2" style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.12em;">
            <span class="rounded-full" style="width:5px;height:5px;background:#1DF412;display:inline-block;"></span>
            Crear cuenta
          </div>
        </div>
      </div>

      <!-- Form content -->
      <div class="flex-1 px-5 pt-6 pb-4 relative z-10">
        <h2 class="font-display font-bold" style="font-size:24px;line-height:1.15;letter-spacing:-0.02em;margin-bottom:8px;">
          Crea tu<br><span style="color:#1DF412;">cuenta.</span>
        </h2>
        <p style="font-size:13px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">
          30 segundos para empezar. Después configuraremos tu plan con IA.
        </p>

        <form @submit.prevent="submit" class="flex flex-col gap-3">
          <!-- Name -->
          <div>
            <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Nombre</div>
            <div class="flex items-center" style="background:#161616;border:1.5px solid rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.name ? 'border-color:#EF4444;' : ''">
              <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
              <input v-model="form.name" type="text" placeholder="Carlos Mendoza" autocomplete="name" class="flex-1 bg-transparent border-none outline-none" style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;" />
            </div>
            <p v-if="form.errors.name" style="font-size:12px;color:#EF4444;margin-top:6px;">{{ form.errors.name }}</p>
          </div>

          <!-- Email -->
          <div>
            <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Email</div>
            <div class="flex items-center" style="background:#161616;border:1.5px solid rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.email ? 'border-color:#EF4444;' : ''">
              <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              </div>
              <input v-model="form.email" type="email" placeholder="tu@correo.edu.co" autocomplete="email" class="flex-1 bg-transparent border-none outline-none" style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;" />
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
              <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Mínimo 8 caracteres" autocomplete="new-password" class="flex-1 bg-transparent border-none outline-none" style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;" />
              <button type="button" @click="showPassword = !showPassword" style="background:transparent;border:none;cursor:pointer;padding:8px;display:flex;color:#9CA3AF;">
                <svg v-if="showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <!-- Password strength bar -->
            <div v-if="passwordStrength" class="flex items-center gap-3" style="margin-top:8px;">
              <div class="flex-1 overflow-hidden" style="height:3px;background:#161616;border-radius:2px;">
                <div style="height:100%;transition:all 0.2s;" :style="{ width: passwordStrength.width, background: passwordStrength.color }"></div>
              </div>
              <span style="font-size:11px;font-weight:600;" :style="{ color: passwordStrength.color }">{{ passwordStrength.label }}</span>
            </div>
            <p v-if="form.errors.password" style="font-size:12px;color:#EF4444;margin-top:6px;">{{ form.errors.password }}</p>
          </div>

          <!-- Terms -->
          <p style="font-size:12px;color:#9CA3AF;line-height:1.5;margin-top:4px;">
            Al crear cuenta aceptas nuestros
            <a href="#" style="color:#1DF412;text-decoration:none;">Términos</a>
            y
            <a href="#" style="color:#1DF412;text-decoration:none;">Política de Privacidad</a>.
          </p>
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
          {{ form.processing ? 'Creando cuenta...' : 'Crear cuenta' }}
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
        <div class="text-center mt-4" style="font-size:13px;color:#9CA3AF;">
          ¿Ya tienes cuenta?
          <Link :href="route('login')" style="color:#1DF412;font-weight:700;text-decoration:none;margin-left:4px;">Inicia sesión</Link>
        </div>
      </div>
    </div>

    <!-- ========== DESKTOP LAYOUT ========== -->
    <div class="hidden md:flex min-h-screen relative z-10">

      <!-- Left panel (image) -->
      <div class="relative overflow-hidden flex-shrink-0" style="width:42%;">
        <img
          src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=900&h=1200&fit=crop&q=85"
          alt=""
          class="w-full h-full object-cover"
          style="opacity:0.7;"
        />
        <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(0,0,0,0.6) 0%,rgba(0,0,0,0.4) 40%,rgba(0,0,0,0.85) 100%);"></div>

        <!-- Logo -->
        <div class="absolute" style="top:48px;left:48px;">
          <span class="font-display font-black text-2xl uppercase text-white tracking-tight">
            Tu Mejor<br><span style="color:#1DF412;">Versión</span>
          </span>
        </div>

        <!-- Microcopy center -->
        <div class="absolute" style="top:50%;left:48px;right:48px;transform:translateY(-50%);">
          <div class="flex items-center gap-2" style="font-size:12px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.15em;margin-bottom:20px;">
            <span class="rounded-full" style="width:6px;height:6px;background:#1DF412;display:inline-block;"></span>
            Crear cuenta
          </div>
          <h3 class="font-display font-bold" style="font-size:36px;line-height:1.15;letter-spacing:-0.025em;color:#fff;margin-bottom:20px;text-shadow:0 2px 12px rgba(0,0,0,0.5);">
            Empieza tu mejor versión.
          </h3>
          <p style="font-size:15px;color:rgba(255,255,255,0.7);line-height:1.6;max-width:380px;text-shadow:0 1px 4px rgba(0,0,0,0.5);">
            Crea tu cuenta en 30 segundos. Después configuraremos tu plan personalizado con IA.
          </p>
        </div>

        <div class="absolute flex justify-between" style="bottom:48px;left:48px;right:48px;font-size:12px;color:rgba(255,255,255,0.5);">
          <span>Tu Mejor Versión · 2026</span>
          <span>v1.0.0</span>
        </div>
      </div>

      <!-- Right panel (form) -->
      <div class="flex-1 flex flex-col relative">

        <!-- Top bar -->
        <div class="flex justify-end items-center" style="padding:32px 64px;">
          <div style="font-size:13px;color:#9CA3AF;">
            ¿Ya tienes cuenta?
            <Link :href="route('login')" style="color:#1DF412;font-weight:700;text-decoration:none;margin-left:4px;">Inicia sesión</Link>
          </div>
        </div>

        <!-- Form centered -->
        <div class="flex-1 flex items-center justify-center" style="padding:20px 64px;">
          <div style="width:100%;max-width:440px;">

            <h2 class="font-display font-bold" style="font-size:36px;line-height:1.15;letter-spacing:-0.02em;margin-bottom:8px;">
              Crea tu<br><span style="color:#1DF412;">cuenta.</span>
            </h2>
            <p style="font-size:15px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">
              30 segundos para empezar. Después configuraremos tu plan con IA.
            </p>

            <form @submit.prevent="submit" class="flex flex-col gap-3">
              <!-- Name -->
              <div>
                <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Nombre</div>
                <div class="flex items-center" style="background:#161616;border:1.5px solid rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.name ? 'border-color:#EF4444;' : ''">
                  <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  </div>
                  <input v-model="form.name" type="text" placeholder="Carlos Mendoza" autocomplete="name" class="flex-1 bg-transparent border-none outline-none" style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;" />
                </div>
                <p v-if="form.errors.name" style="font-size:12px;color:#EF4444;margin-top:6px;">{{ form.errors.name }}</p>
              </div>

              <!-- Email -->
              <div>
                <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Email</div>
                <div class="flex items-center" style="background:#161616;border:1.5px solid rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.email ? 'border-color:#EF4444;' : ''">
                  <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                  </div>
                  <input v-model="form.email" type="email" placeholder="tu@correo.edu.co" autocomplete="email" class="flex-1 bg-transparent border-none outline-none" style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;" />
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
                  <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Mínimo 8 caracteres" autocomplete="new-password" class="flex-1 bg-transparent border-none outline-none" style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;" />
                  <button type="button" @click="showPassword = !showPassword" style="background:transparent;border:none;cursor:pointer;padding:8px;display:flex;color:#9CA3AF;">
                    <svg v-if="showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
                <!-- Strength bar -->
                <div v-if="passwordStrength" class="flex items-center gap-3" style="margin-top:8px;">
                  <div class="flex-1 overflow-hidden" style="height:3px;background:#161616;border-radius:2px;">
                    <div style="height:100%;transition:all 0.2s;" :style="{ width: passwordStrength.width, background: passwordStrength.color }"></div>
                  </div>
                  <span style="font-size:11px;font-weight:600;" :style="{ color: passwordStrength.color }">{{ passwordStrength.label }}</span>
                </div>
                <p v-if="form.errors.password" style="font-size:12px;color:#EF4444;margin-top:6px;">{{ form.errors.password }}</p>
              </div>

              <!-- Terms -->
              <p style="font-size:12px;color:#9CA3AF;line-height:1.5;margin-top:4px;margin-bottom:4px;">
                Al crear cuenta aceptas nuestros
                <a href="#" style="color:#1DF412;text-decoration:none;">Términos</a>
                y
                <a href="#" style="color:#1DF412;text-decoration:none;">Política de Privacidad</a>.
              </p>

              <!-- Submit -->
              <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 font-bold transition-all"
                style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:16px 24px;font-size:16px;cursor:pointer;letter-spacing:-0.01em;margin-top:8px;"
                :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
              >
                {{ form.processing ? 'Creando cuenta...' : 'Crear cuenta' }}
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
