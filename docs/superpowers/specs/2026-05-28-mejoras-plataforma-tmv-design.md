# Mejoras Plataforma "Tu Mejor Versión" — Diseño / Spec

> **Cómo retomar:** dile a Claude *"lee `app/docs/superpowers/specs/2026-05-28-mejoras-plataforma-tmv-design.md` y continúa donde quedaste"*.
> El estado vivo está en la sección **BITÁCORA DE PROGRESO** al final. Marca `[x]` cada tarea al terminarla y deja una nota.

- **Fecha:** 2026-05-28
- **Repo raíz:** `C:/Users/Dell/OneDrive/Documentos/Claude/Projects/Tu mejor version`
- **App:** `app/` (Laravel 11 + Inertia v2 + Vue 3 `<script setup lang="ts">` + Pinia + Tailwind v4 + Vite 7, PWA)
- **Imágenes/logos del usuario:** `app/imagenes/`
- **Tokens de diseño:** fondo `#000`, surface `#161616`, accent `#1DF412`, texto `#FFFFFF`

---

## Decisiones tomadas (con el usuario)

| Tema | Decisión |
|------|----------|
| Animación micro-UI | **anime.js reemplaza @vueuse/motion** (botones, cards, modales, transiciones de página) |
| Animación landing/scroll | **GSAP + ScrollTrigger** se queda |
| Video hero | **Remotion** se queda |
| Skill de diseño principal | **/frontend-design** (/, ui-ux-pro-max como consulta) |
| Motion tipo Viktor Oddy | **Overhaul completo** de la landing, adaptado a identidad (negro/#1DF412), NO copiar colores/fuentes |
| `vercel-react-best-practices` | **Descartada** (es React; el proyecto es Vue). Usar convenciones Vue/TS del `CLAUDE.md` |
| Tipografías | **Anton (display) + Inter (cuerpo)** reemplazan Barlow Condensed + Inter |
| Contenido landing nuevo | **Grid de features destacadas** ampliado |
| Orden de ejecución | **A → B → C → E → D** |

---

## Flujo de skills obligatorio (tarea 16)

1. `/superpowers` → planear cada módulo antes de codear.
2. `/entrevistador-procesos` → una sola pregunta a la vez cuando haya dudas.
3. `/frontend-design` (principal) + `/ui-ux-pro-max` (consulta de paletas/guidelines) → diseño.
4. Construir.
5. `/debug` → al cerrar cada módulo (rutas, props Inertia, TS, N+1, 3 estados, 390px, build, consola).
6. `/tester-usabilidad` + `/buenas-practicas` → antes de cerrar prioridad/deploy.

**Conflictos advertidos:** /frontend-design y /ui-ux-pro-max se solapan (una principal, otra consulta). vercel-react-best-practices no aplica (React vs Vue). anime.js no debe convivir con @vueuse/motion en el mismo componente → migrar.

---

## FASE A — Fixes rápidos y aislados

### A1 · Icono PWA personalizado (tarea 1)
- **Origen:** `app/imagenes/icono_movil.svg` (NO el gris con la "t").
- **Acción:** regenerar set de iconos a partir de ese SVG y reemplazar:
  - `public/icons/icon-{72,96,128,144,152,192,384,512}.png`
  - `public/icon-mobile.svg`, `public/icon-main.svg` (usado en Onboarding overlay), `public/favicon.ico`
- El icono de instalación Android/iOS sale del manifest 192/512 `purpose:"any maskable"` → asegurar padding seguro (safe zone ~10%) para que no se recorte.
- **Archivos:** `app/vite.config.ts` (manifest, ya referencia los paths correctos), `public/icons/*`.
- **Verificación:** `npm run build`, instalar PWA en móvil, confirmar icono nuevo.

### A2 · Borrar tarjetas superpuestas en landing (tarea 2)
- **Qué:** badges flotantes `SESIÓN HOY` y `RACHA ACTIVA` que se superponen a las métricas.
- **Archivo:** `resources/js/Pages/Landing.vue` → eliminar bloques `Landing.vue:143-162` (los dos `<div class="absolute ... hidden md:flex / lg:flex">`).
- **Verificación:** métricas inferiores quedan limpias en 390px y 1440px.

### A13 · Menú móvil (tarea 13)
- **Problema:** "Iniciar sesión" es `hidden sm:inline-flex` → en móvil solo se ve "Registrarse" pegado al logo.
- **Acción:** rediseñar nav (`Landing.vue:42-58`) para móvil: ambos accesos visibles y espaciados (o "Entrar" texto + "Registrarse" botón), sin pegarse al logo. Evaluar gap, padding y tamaños.
- **Verificación:** 390px sin solape ni overflow.

### A8 · Quitar movilidad del onboarding (tarea 8)
- **Qué:** eliminar el bloque "Movilidad articular general" (buena/regular/limitada) del **paso 1 (Datos personales)**. Conservar nombre, sexo, edad/peso/altura. Conservar el **paso 2 (Actividad)**.
- **Archivo:** `resources/js/Pages/Onboarding.vue`:
  - Quitar bloque `Onboarding.vue:496-508`.
  - Limpiar `mobility` de: `form` (122-140), `watch`/localStorage (200-225), `MOBILITY_LABELS` (105), persistencia onMounted (200-208).
  - Revisar summary (no muestra movilidad, ok).
- **Verificación:** TS sin referencias a `mobility`, draft localStorage no rompe.

---

## FASE B — Auth + Onboarding

### B5 · Login y Registro (tarea 5)
- **Logo sobre la imagen → quitar.** En desktop hay `LogoSVG variant="auth"` encima de la imagen (`Login.vue:189-192`). Quitarlo de ahí.
- **Logo en encabezado del formulario (derecha):** usar `app/imagenes/Logo (3).png` → copiar a `public/` (p.ej. `public/logo-form.png`). Colocar a la derecha del título "Bienvenido de vuelta" / "Crea tu cuenta".
- **Mover link de cuenta a debajo de los inputs:** el "¿No tienes cuenta? Regístrate" (desktop top-right `Login.vue:226-229`) va **debajo del formulario**. Igual en Register el "¿Ya tienes cuenta? Iniciar sesión".
- **Logo clickeable → landing:** envolver el logo del formulario en `<Link :href="route('landing')">` SOLO en Login y Register.
- **Bug a corregir:** `Login.vue:220` usa `router.visit()` pero `router` no está importado → o importar `router` de `@inertiajs/vue3`, o cambiar el botón "Volver" por `<Link :href="route('landing')">`.
- **Archivos:** `resources/js/Pages/Auth/Login.vue`, `resources/js/Pages/Auth/Register.vue`.
- **Verificación:** login y registro en 390px y 1440px; clic en logo va a landing; links debajo de inputs.

### B6 · Restaurar imágenes en onboarding (tarea 6)
- **Estado actual:** el panel lateral usa gradientes CSS (`STEP_PANELS`, `Onboarding.vue:14-21`).
- **Acción:** reemplazar por **fotos fitness** con overlay oscuro para legibilidad. Opción: una imagen por fase (array de URLs/locales) o una fija de alta calidad. Mantener el overlay y el patrón de grid sutil si aporta.
- **Verificación:** texto legible sobre la imagen en todas las fases; móvil (h-260px) y desktop (40% fijo).

### B7 · Logo + texto en onboarding (tarea 7)
- **Logo del login a la derecha:** usar `Logo (3).png` (mismo que B5) en el panel lateral, **a la derecha** (en vez de `LogoSVG variant="navbar"` arriba-izquierda en `Onboarding.vue:343-345`).
- **Texto bajo el logo:** mover/ajustar el bloque de meta (eyebrow/título/sub) para que quede **debajo del logo, a la derecha**.
- **Verificación:** composición coherente con el login.

---

## FASE C — Identidad visual transversal

### C10 · Tipografías → Anton + Inter (tarea 10)
- **Display:** Anton (Google Fonts) reemplaza Barlow Condensed en `font-display` / `font-family:'Barlow Condensed'`.
- **Cuerpo:** Inter (ya presente).
- **Archivos:** `tailwind.config.ts` (familias), CSS global de fuentes, y reemplazar referencias inline `font-family:'Barlow Condensed'` (Onboarding, etc.).
- Anton es uppercase-friendly y muy bold → revisar tamaños/line-height para que no rompa layouts.
- **Verificación:** sin FOUT grave (font-display:swap), títulos consistentes en toda la app.

### C11 · Iconografía unificada (tarea 11)
- Unificar a **lucide** (set consistente) reemplazando SVG sueltos repetidos y, donde aplique, los emojis del onboarding (sexo, objetivos, splits, actividad) por iconos coherentes monocromo + accent.
- Crear, si conviene, un componente `Icon.vue` o usar `lucide-vue-next`.
- **Verificación:** estilo de iconos homogéneo (grosor de trazo, tamaño, color).

### C14 · Logos por vista (tarea 14)
- **Catálogo:** `app/imagenes/` (Logo.png/.svg, Logo (1..5), LOGO LARGO.svg, Icono principal*, icono_movil.svg) + `public/` (logo-full, logo-horizontal, logo-icon, logo.svg, icon-main.svg, icon-mobile.svg).
- **Asignación propuesta:**
  - Navbar landing → `logo-horizontal` / LogoSVG navbar.
  - Auth + Onboarding header → `Logo (3).png`.
  - PWA/favicon → `icono_movil.svg`.
  - Footer → `logo-icon`.
- Ajustar `LogoSVG.vue` (variantes) si hace falta.

### C12 · Responsive 100% (tarea 12)
- QA a 390px y 1440px de **todas las vistas tocadas** (regla `CLAUDE.md`): Landing, Login, Register, Onboarding, y revisar Dashboard/Workout/Nutrition/Chat de paso.
- Sin overflow horizontal, botones accesibles, texto legible.
- **Verificación:** `/tester-usabilidad` + revisión manual responsive.

---

## FASE E — Contenido landing (tarea 3)

### E3 · Features destacadas + info relevante
- Ampliar el grid de features (`Landing.vue:8-13`, 4 actuales) con: **Coach IA 24/7**, **Análisis de postura con cámara (MediaPipe)**, **Nutrición colombiana (Mifflin-St Jeor, 82 alimentos)**, **Rutinas adaptadas por nivel/objetivo/lesiones**, **Modo offline PWA**, **Base científica NSCA/ACSM**.
- Considerar sección "Cómo funciona" (onboarding → rutina IA → entrenar con coach/postura → progreso) si hay espacio. (No prioritaria; el usuario eligió solo "features destacadas".)
- **Verificación:** copys en español, datos reales del proyecto, sin inventar métricas falsas nuevas.

---

## FASE D — Animación

### D9 · anime.js reemplaza @vueuse/motion (tarea 9)
- **Instalar** `animejs` (+ tipos). Referencia spring: https://animejs.com/easing-editor/spring/default
- Crear composable `resources/js/Composables/useAnime.ts` con **cleanup en `onUnmounted`** (regla CLAUDE.md). Tipar APIs externas como `unknown` si hace falta.
- Migrar micro-interacciones que hoy usan @vueuse/motion: cards, modales (ExerciseModal, CoachModal), botones, transiciones de página, skeleton/feedback.
- **No** mezclar anime.js y @vueuse/motion en el mismo componente.
- **Verificación:** sin memory leaks en navegación SPA; animaciones spring suaves.

### D4 · Motion tipo Viktor Oddy con Remotion (tarea 4)
- **Referencia:** landing de "Viktor Oddy" (marquee infinito, reveals escalonados fade-in-up, parallax, carrusel auto-scroll, trail de GIFs en hover, bottom nav flotante). **Adaptar a identidad TMV** (negro `#000`, accent `#1DF412`, Anton/Inter), NO copiar PP Mondwest ni la paleta clara.
- **Remotion (`/remotion`, ya previsto en CLAUDE.md):** composición 6–8s con logo cinético + split de letras "Tu Mejor Versión" + partículas verdes sobre negro. Export `public/animations/hero.webm`. Consumir como `<video autoplay loop muted playsinline>` en el hero.
- **GSAP en landing:** marquee infinito de GIFs/clips, reveals escalonados (equivalente a `useInViewAnimation`), parallax en imágenes, trail de cursor en sección CTA, bottom nav flotante. Cleanup con `gsap.context().revert()` en `onUnmounted`.
- **Verificación:** 60fps en desktop, degradación elegante en móvil, sin bloquear scroll.

---

## Riesgos / Notas

- **Tailwind v4** (vite plugin) — config de fuentes va en CSS `@theme` o `tailwind.config`. Verificar cómo está hoy antes de tocar familias.
- **anime.js v3 vs v4** — la API cambió (v4 es modular `import { animate }`). Confirmar versión instalada y usar la API correcta.
- **Remotion** requiere su propio `package.json` en `/remotion`; el render se hace aparte del build de Vite.
- **Logos PNG** deben copiarse a `public/` para servirse (los de `app/imagenes/` no son públicos).
- Cada cambio de módulo: correr `/debug` y `npm run build` antes de marcar `[x]`.

---

## BITÁCORA DE PROGRESO (estado vivo)

> Actualizar al cerrar cada tarea: marcar `[x]` y dejar nota corta (qué se hizo / archivos / pendiente).

### Fase A
- [ ] **A1** Icono PWA desde `icono_movil.svg`
- [ ] **A2** Borrar badges SESIÓN HOY / RACHA ACTIVA en Landing
- [ ] **A13** Menú móvil landing (ambos accesos visibles)
- [ ] **A8** Quitar movilidad del paso 1 del onboarding

### Fase B
- [ ] **B5** Login/Registro: logo derecha, links bajo inputs, logo→landing, fix `router`
- [ ] **B6** Restaurar imágenes en onboarding (panel lateral)
- [ ] **B7** Logo + texto a la derecha en onboarding

### Fase C
- [ ] **C10** Tipografías Anton + Inter
- [ ] **C11** Iconografía unificada (lucide)
- [ ] **C14** Logos por vista
- [ ] **C12** Responsive 100% (QA 390/1440)

### Fase E
- [ ] **E3** Features destacadas + info relevante en landing

### Fase D
- [ ] **D9** anime.js reemplaza @vueuse/motion (composable useAnime)
- [ ] **D4** Motion Viktor Oddy con Remotion + GSAP (overhaul landing)

### Notas de sesión
- 2026-05-28: spec creado tras brainstorming. Pendiente: revisión del usuario → writing-plans → ejecución Fase A.
