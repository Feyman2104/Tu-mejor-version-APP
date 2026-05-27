<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import LogoSVG from '@/Components/LogoSVG.vue'

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

const progressMetrics = [
  { value: '1,240', label: 'Ejercicios completados' },
  { value: '+18%',  label: 'Progreso semanal' },
  { value: '12.4k', label: 'Calorías quemadas' },
  { value: '8',     label: 'Logros desbloqueados' },
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
        <Link :href="route('landing')" class="flex items-center">
          <LogoSVG variant="navbar" />
        </Link>
        <div class="flex items-center gap-2 md:gap-3">
          <Link :href="route('login')"
            class="font-semibold transition-colors hidden sm:inline-flex"
            style="color:#fff;border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:10px 20px;font-size:14px;text-decoration:none;">
            Iniciar sesión
          </Link>
          <Link :href="route('register')"
            class="font-bold"
            style="color:#1DF412;border:1.5px solid rgba(29,244,18,0.45);border-radius:12px;padding:10px 20px;font-size:14px;text-decoration:none;">
            Registrarse
          </Link>
        </div>
      </nav>

      <!-- HERO — sección 1: video de fondo + texto CTA -->
      <section class="relative overflow-hidden px-6 md:px-16 py-24 md:py-36 flex items-center">

        <!-- Video de fondo -->
        <video
          src="/video-gym.mp4"
          autoplay
          muted
          loop
          playsinline
          class="absolute inset-0 w-full h-full object-cover"
          style="z-index:0;" />
        <!-- Overlay oscuro para legibilidad -->
        <div class="absolute inset-0" style="z-index:1;background:linear-gradient(90deg,rgba(0,0,0,0.88) 0%,rgba(0,0,0,0.65) 55%,rgba(0,0,0,0.3) 100%);"></div>

        <!-- Contenido del hero -->
        <div class="relative w-full max-w-2xl" style="z-index:2;">
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
              style="background:transparent;color:#1DF412;border:1.5px solid rgba(29,244,18,0.45);border-radius:12px;padding:18px 28px;font-size:16px;text-decoration:none;">
              Comenzar gratis
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
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
      </section>

      <!-- HERO — sección 2: banner a todo el ancho con info de progreso -->
      <section class="px-6 md:px-16 py-16">
        <div class="relative w-full overflow-hidden" style="border-radius:24px;min-height:520px;">
          <!-- Imagen de fondo (landscape) -->
          <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=1600&h=900&fit=crop&q=85" alt="Estudiante entrenando" class="absolute inset-0 w-full h-full object-cover" />
          <!-- Overlay para legibilidad -->
          <div class="absolute inset-0" style="background:linear-gradient(90deg,rgba(0,0,0,0.9) 0%,rgba(0,0,0,0.65) 45%,rgba(0,0,0,0.35) 100%);"></div>
          <div class="absolute inset-0" style="background:linear-gradient(180deg,transparent 45%,rgba(0,0,0,0.6) 100%);"></div>

          <!-- Contenido sobre la imagen -->
          <div class="relative flex flex-col justify-between h-full" style="z-index:10;padding:40px 32px;min-height:520px;">

            <!-- Texto superior -->
            <div style="max-width:560px;">
              <div class="inline-flex items-center gap-2 mb-5" style="font-size:12px;font-weight:600;color:#1DF412;text-transform:uppercase;letter-spacing:0.15em;">
                <span class="rounded-full" style="width:6px;height:6px;background:#1DF412;display:inline-block;"></span>
                Tu progreso, en tiempo real
              </div>
              <h2 class="font-display font-black uppercase" style="font-size:42px;line-height:1;letter-spacing:0.01em;margin-bottom:18px;">
                Cada sesión cuenta.<br><span style="color:#1DF412;">Cada día suma.</span>
              </h2>
              <p style="font-size:17px;color:#D1D5DB;line-height:1.6;max-width:480px;">
                Registra tus entrenamientos, sigue tu racha y mira cómo mejoras semana a semana. Todo tu avance, en un solo lugar.
              </p>
            </div>

            <!-- Métricas inferiores -->
            <div class="flex flex-wrap gap-3" style="margin-top:32px;">
              <div v-for="m in progressMetrics" :key="m.label"
                style="flex:1 1 160px;min-width:140px;background:rgba(13,13,13,0.7);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:18px 20px;">
                <div class="font-display font-black" style="font-size:30px;letter-spacing:-0.02em;color:#1DF412;line-height:1;">{{ m.value }}</div>
                <div style="font-size:13px;color:#9CA3AF;margin-top:6px;">{{ m.label }}</div>
              </div>
            </div>
          </div>

          <!-- Badge flotante: SESIÓN HOY -->
          <div class="absolute items-center gap-3 hidden md:flex" style="top:32px;right:32px;z-index:10;background:rgba(13,13,13,0.95);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:14px 18px;box-shadow:0 12px 32px rgba(0,0,0,0.5);">
            <div class="flex items-center justify-center" style="width:40px;height:40px;border-radius:10px;background:rgba(29,244,18,0.08);color:#1DF412;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </div>
            <div>
              <div style="font-size:11px;color:#9CA3AF;margin-bottom:2px;">SESIÓN HOY</div>
              <div style="font-size:16px;font-weight:700;">42 min · 387 kcal</div>
            </div>
          </div>
          <!-- Badge flotante: RACHA ACTIVA -->
          <div class="absolute items-center gap-3 hidden lg:flex" style="bottom:32px;right:32px;z-index:10;background:rgba(13,13,13,0.95);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.06);border-radius:16px;padding:14px 18px;box-shadow:0 12px 32px rgba(0,0,0,0.5);">
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
      <footer style="border-top:1px solid rgba(255,255,255,0.06);">
        <div class="px-6 md:px-16 py-12 grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-12">

          <!-- Brand -->
          <div class="col-span-2 md:col-span-1">
            <Link :href="route('landing')" class="flex items-center mb-3">
              <LogoSVG variant="navbar" size="small" />
            </Link>
            <p style="font-size:13px;color:#6B7280;line-height:1.6;margin-top:12px;max-width:220px;">
              Fitness inteligente para estudiantes universitarios. Tu mejor versión, con IA.
            </p>
            <!-- Social links -->
            <div class="flex items-center gap-3" style="margin-top:16px;">
              <a href="#" aria-label="Instagram"
                style="width:34px;height:34px;border-radius:8px;background:#161616;border:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;color:#9CA3AF;text-decoration:none;transition:border-color 0.15s;"
                onmouseover="this.style.borderColor='rgba(29,244,18,0.4)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.06)'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
              </a>
              <a href="#" aria-label="Twitter / X"
                style="width:34px;height:34px;border-radius:8px;background:#161616;border:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;color:#9CA3AF;text-decoration:none;"
                onmouseover="this.style.borderColor='rgba(29,244,18,0.4)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.06)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.259 5.631 5.905-5.631Zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
              </a>
              <a href="#" aria-label="TikTok"
                style="width:34px;height:34px;border-radius:8px;background:#161616;border:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;color:#9CA3AF;text-decoration:none;"
                onmouseover="this.style.borderColor='rgba(29,244,18,0.4)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.06)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.77a8.16 8.16 0 0 0 4.77 1.52V6.85a4.85 4.85 0 0 1-1-.16z"/></svg>
              </a>
            </div>
          </div>

          <!-- Producto -->
          <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:#4B5563;margin-bottom:16px;">Producto</div>
            <div class="flex flex-col gap-3">
              <Link :href="route('register')" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Comenzar gratis</Link>
              <Link :href="route('login')" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Iniciar sesión</Link>
              <a href="#" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Rutinas IA</a>
              <a href="#" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Análisis de postura</a>
              <a href="#" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Coach virtual</a>
            </div>
          </div>

          <!-- Empresa -->
          <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:#4B5563;margin-bottom:16px;">Empresa</div>
            <div class="flex flex-col gap-3">
              <a href="#" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Acerca de</a>
              <a href="#" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Blog</a>
              <a href="#" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Universidades</a>
              <a href="#" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Contacto</a>
            </div>
          </div>

          <!-- Legal -->
          <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:#4B5563;margin-bottom:16px;">Legal</div>
            <div class="flex flex-col gap-3">
              <a href="#" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Términos de uso</a>
              <a href="#" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Política de privacidad</a>
              <a href="#" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Cookies</a>
              <a href="#" style="font-size:14px;color:#9CA3AF;text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">Accesibilidad</a>
            </div>
          </div>
        </div>

        <!-- Copyright bar -->
        <div class="px-6 md:px-16 py-5 flex flex-col md:flex-row justify-between items-center gap-3"
          style="border-top:1px solid rgba(255,255,255,0.04);">
          <span style="font-size:12px;color:#4B5563;">© 2026 Tu Mejor Versión. Todos los derechos reservados.</span>
          <span style="font-size:12px;color:#4B5563;">Hecho con ❤️ para estudiantes universitarios</span>
        </div>
      </footer>

    </div>
  </div>
</template>
