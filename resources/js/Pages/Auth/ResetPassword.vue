<script setup lang="ts">
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import LogoSVG from '@/Components/LogoSVG.vue'

defineOptions({ layout: GuestLayout })

const props = defineProps<{
  token: string
  email: string
}>()

const showPassword = ref(false)

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post(route('password.update'), {
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
          src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=900&h=600&fit=crop&q=85"
          alt=""
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(0,0,0,0.4) 0%,rgba(0,0,0,0.2) 40%,rgba(0,0,0,0.95) 100%);"></div>
        <div class="absolute bottom-5 left-5">
          <div class="inline-flex items-center gap-2" style="font-size:11px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.12em;">
            <span class="rounded-full" style="width:5px;height:5px;background:#1DF412;display:inline-block;"></span>
            Nueva contraseña
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 px-5 pt-6 pb-4 relative z-10">
        <h2 class="font-display font-bold" style="font-size:24px;line-height:1.15;letter-spacing:-0.02em;margin-bottom:8px;">
          Nueva<br><span style="color:#1DF412;">contraseña.</span>
        </h2>
        <p style="font-size:13px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">
          Elige una contraseña nueva y segura para tu cuenta.
        </p>

        <form @submit.prevent="submit" class="flex flex-col gap-3">
          <!-- Email (readonly) -->
          <div>
            <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Email</div>
            <div class="flex items-center" style="background:#0D0D0D;border:1.5px solid rgba(255,255,255,0.04);border-radius:14px;padding:4px;">
              <div style="padding-left:14px;color:#4B5563;display:flex;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              </div>
              <input
                v-model="form.email"
                type="email"
                readonly
                class="flex-1 bg-transparent border-none outline-none"
                style="padding:14px 16px;color:#4B5563;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;"
              />
            </div>
          </div>

          <!-- New password -->
          <div>
            <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Nueva contraseña</div>
            <div class="flex items-center" style="background:#161616;border:1.5px solid rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.password ? 'border-color:#EF4444;' : ''">
              <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </div>
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Mínimo 8 caracteres"
                autocomplete="new-password"
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

          <!-- Confirm password -->
          <div>
            <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Confirmar contraseña</div>
            <div class="flex items-center" style="background:#161616;border:1.5px solid rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.password_confirmation ? 'border-color:#EF4444;' : ''">
              <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </div>
              <input
                v-model="form.password_confirmation"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Repite tu contraseña"
                autocomplete="new-password"
                class="flex-1 bg-transparent border-none outline-none"
                style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;"
              />
            </div>
            <p v-if="form.errors.password_confirmation" style="font-size:12px;color:#EF4444;margin-top:6px;">{{ form.errors.password_confirmation }}</p>
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
          {{ form.processing ? 'Guardando...' : 'Guardar contraseña' }}
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
        <div class="text-center mt-4" style="font-size:13px;color:#9CA3AF;">
          <Link :href="route('login')" style="color:#1DF412;font-weight:700;text-decoration:none;">Volver al inicio de sesión</Link>
        </div>
      </div>
    </div>

    <!-- ========== DESKTOP LAYOUT ========== -->
    <div class="hidden md:flex min-h-screen relative z-10">

      <!-- Left panel (image) -->
      <div class="relative overflow-hidden flex-shrink-0" style="width:42%;">
        <img
          src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=900&h=1200&fit=crop&q=85"
          alt=""
          class="w-full h-full object-cover"
          style="opacity:0.7;"
        />
        <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(0,0,0,0.6) 0%,rgba(0,0,0,0.4) 40%,rgba(0,0,0,0.85) 100%);"></div>

        <div class="absolute" style="top:48px;left:48px;">
          <LogoSVG variant="auth" size="medium" />
        </div>

        <div class="absolute" style="top:50%;left:48px;right:48px;transform:translateY(-50%);">
          <div class="flex items-center gap-2" style="font-size:12px;color:#1DF412;font-weight:600;text-transform:uppercase;letter-spacing:0.15em;margin-bottom:20px;">
            <span class="rounded-full" style="width:6px;height:6px;background:#1DF412;display:inline-block;"></span>
            Nueva contraseña
          </div>
          <h3 class="font-display font-bold" style="font-size:36px;line-height:1.15;letter-spacing:-0.025em;color:#fff;margin-bottom:20px;text-shadow:0 2px 12px rgba(0,0,0,0.5);">
            Crea una contraseña<br>segura.
          </h3>
          <p style="font-size:15px;color:rgba(255,255,255,0.7);line-height:1.6;max-width:380px;text-shadow:0 1px 4px rgba(0,0,0,0.5);">
            Elige una contraseña nueva. Usa al menos 8 caracteres con letras y números.
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
          <Link :href="route('login')" class="flex items-center gap-2" style="font-size:13px;color:#9CA3AF;text-decoration:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Volver al inicio de sesión
          </Link>
        </div>

        <!-- Form centered -->
        <div class="flex-1 flex items-center justify-center" style="padding:20px 64px;">
          <div style="width:100%;max-width:440px;">

            <h2 class="font-display font-bold" style="font-size:36px;line-height:1.15;letter-spacing:-0.02em;margin-bottom:8px;">
              Nueva<br><span style="color:#1DF412;">contraseña.</span>
            </h2>
            <p style="font-size:15px;color:#9CA3AF;line-height:1.5;margin-bottom:24px;">
              Elige una contraseña nueva y segura para tu cuenta.
            </p>

            <form @submit.prevent="submit" class="flex flex-col gap-4">
              <!-- Email (readonly) -->
              <div>
                <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Email</div>
                <div class="flex items-center" style="background:#0D0D0D;border:1.5px solid rgba(255,255,255,0.04);border-radius:14px;padding:4px;">
                  <div style="padding-left:14px;color:#4B5563;display:flex;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                  </div>
                  <input
                    v-model="form.email"
                    type="email"
                    readonly
                    class="flex-1 bg-transparent border-none outline-none"
                    style="padding:14px 16px;color:#4B5563;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;"
                  />
                </div>
              </div>

              <!-- New password -->
              <div>
                <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Nueva contraseña</div>
                <div class="flex items-center" style="background:#161616;border:1.5px solid rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.password ? 'border-color:#EF4444;' : ''">
                  <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  </div>
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Mínimo 8 caracteres"
                    autocomplete="new-password"
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

              <!-- Confirm password -->
              <div>
                <div style="font-size:11px;color:#9CA3AF;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;">Confirmar contraseña</div>
                <div class="flex items-center" style="background:#161616;border:1.5px solid rgba(255,255,255,0.06);border-radius:14px;padding:4px;" :style="form.errors.password_confirmation ? 'border-color:#EF4444;' : ''">
                  <div style="padding-left:14px;color:#9CA3AF;display:flex;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  </div>
                  <input
                    v-model="form.password_confirmation"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Repite tu contraseña"
                    autocomplete="new-password"
                    class="flex-1 bg-transparent border-none outline-none"
                    style="padding:14px 16px;color:#fff;font-size:15px;font-weight:500;letter-spacing:-0.01em;min-width:0;"
                  />
                </div>
                <p v-if="form.errors.password_confirmation" style="font-size:12px;color:#EF4444;margin-top:6px;">{{ form.errors.password_confirmation }}</p>
              </div>

              <button
                type="submit"
                :disabled="form.processing"
                class="w-full flex items-center justify-center gap-2 font-bold transition-all"
                style="background:#1DF412;color:#000;border:none;border-radius:12px;padding:16px 24px;font-size:16px;cursor:pointer;letter-spacing:-0.01em;"
                :style="form.processing ? 'opacity:0.6;cursor:not-allowed;' : ''"
              >
                {{ form.processing ? 'Guardando...' : 'Guardar nueva contraseña' }}
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
