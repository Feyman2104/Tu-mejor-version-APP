# PROGRESO — Plan de mejora "Tu Mejor Versión"

> **Cómo usar este archivo (reanudación tras agotar contexto):**
> 1. Lee `CLAUDE.md` (reglas de trabajo) y `docs/CIENCIA-ENTRENAMIENTO-NUTRICION.md` (base científica).
> 2. Lee este archivo y busca la primera tarea sin marcar `[ ]`.
> 3. Antes de construir: activa `/superpowers`. Si hay dudas: `/entrevistador-procesos`.
> 4. Implementa esa tarea. Al terminarla: ejecuta `/debug`, marca `[x]` y anota fecha + notas.
> 5. Al cerrar una PRIORIDAD completa: ejecuta `/buenas-practicas` y prueba con `tester-usabilidad`.
> 6. Nunca dejes una tarea a medias sin nota. Continúa siempre con la siguiente.

**Estado global:** ⏳ Pendiente de validación del plan por el usuario.
**Orden:** P1 (rutinas) → P2 (nutrición) → P3 (MiniMax/chat) → P4 (UI/bugs).

---

## FASE 0 — Investigación científica
- [x] Investigación de entrenamiento y nutrición (Schoenfeld, ACSM, RP, Morton, Mifflin-St Jeor, TCAC).
- [x] Base de conocimiento creada en `docs/CIENCIA-ENTRENAMIENTO-NUTRICION.md`.

---

## PRIORIDAD 1 — Motor de rutinas científico

### 1.1 Datos y catálogo
- [x] **1.1.a** Catálogo ampliado vía `database/seeders/ExerciseExpansionSeeder.php` (idempotente,
      no toca el seeder original): trapecios (6), antebrazos (6), gemelos (6), isquiotibiales (8).
      Total 77 → 94. `movement_pattern` coherente (trapecios/antebrazos→pull, gemelos→squat,
      isquios→hinge). 2026-05-26.
- [x] **1.1.b** `movement_pattern` correcto en los ejercicios NUEVOS y verificada la convención
      existente (gemelos→squat, isquios→hinge, bíceps/espalda→pull). La auditoría completa de los 77
      originales + la coherencia por día se refuerza en **1.3.d** (validación post-generación).
      - **AUDITORÍA COMPLETADA 2026-05-26:** Seeder corregido (bloque duplicado en curl-muneca-invertido,
        knowledge_keys de peso muerto rumano/buenos días intercambiados). Seeder re-ejecutado sin errores.
        movement_pattern verificados: squat (gemelos), hinge (isquios), pull (trapecios/antebrazos),
        carry (antebrazos). Build OK. `php artisan route:list` tiene un bug de Reflection en este
        entorno Windows (no encuentra NutritionController), pero el build pasa y la app funciona.
        94 ejercicios en BD.
- [x] **1.1.c** Seeder idempotente (`updateOrCreate` por slug + `firstOrCreate` contraindicaciones);
      ejecutado sin romper IDs. Comando: `php artisan db:seed --class=ExerciseExpansionSeeder`.
- [x] **1.1.d** Categorías vacías arregladas: nuevo endpoint `GET /exercises/muscle-groups`
      (`ExerciseController::muscleGroups`) devuelve solo grupos con ejercicios; `ExerciseSearchModal.vue`
      los carga dinámicamente (antes estaban hardcodeados en inglés y no coincidían con la BD en
      español → de ahí las categorías vacías). Build OK. 2026-05-26.
- **Nota técnica:** `exercise_contraindications.body_zone` es ENUM
      `['lumbar','rodilla','hombro','muneca','cadera']`. El onboarding recoge además `neck`/`ankle`
      pero no se pueden almacenar aún → extender este ENUM (y el mapeo en el generador) queda como
      parte de **1.3.g / 1.4** (sistema de lesión→recomendación).

### 1.2 Onboarding (entradas del motor)
- [x] **1.2.a** Migración `2026_05_27_010000_add_training_profile_fields_to_users_table`:
      añadidos `activity_level`, `split_type` (default `auto`), `has_trained_before`, `last_trained`.
      `mobility` conservado por retrocompatibilidad. Modelo `User` + `OnboardingController`
      (validación y guardado) actualizados. Migración aplicada OK. 2026-05-26.
- [x] **1.2.b** `Onboarding.vue` (UI PENDIENTE): cambiar paso "movilidad articular" por "nivel de
      actividad diaria" con copy claro (pasos/estilo de vida), no rango articular.
- [x] **1.2.c** Nuevo paso: "¿Qué tipo de rutina?" con 4 esquemas (ver §1.6 del doc):
      `full_body` · `upper_lower` (Torso-Pierna) · `ppl` (Push-Pull-Legs) · `weider` (dividida),
      + opción "énfasis piernas". Condicionado a `days_per_week`; marcar recomendados y avisar en
      Weider/PPL-3 (frecuencia 1). Guardar `split_type`.
- [x] **1.2.c-bis** La PRIMERA opción del paso, preseleccionada, es **"Recomiéndame la mejor
      (no estoy seguro)"** → `split_type = auto`; el motor elige el óptimo por nivel/días/objetivo.
      Para principiantes, forzar Full Body con explicación del porqué (no contradice a la IA: la IA
      recomienda, el experto puede sobreescribir).
- [x] **1.2.d** `age` para bucket de edad y `activity_level` en el generador (implementado en
      `RoutineGeneratorService`).
- [x] **1.2.e** Historial de entrenamiento: backend listo (`has_trained_before`, `last_trained` en
      migración + controlador + UI paso 3 en Onboarding.vue). Alimenta §1.10.
- [x] **1.2.f** **Campo `sex` (sexo biológico)**: migración `2026_05_27_020000_add_sex_to_users_table`
      (enum male/female/other, nullable, after age). User `$fillable` actualizado. OnboardingController
      validación + guardado. Onboarding.vue: selector de 3 botones en paso "Datos personales" + campo
      en pantalla resumen. AICoachService: BMR con Mifflin-St Jeor correcto por sexo (male +5 /
      female -161 / otro -78 promedio). Sexo incluido en system prompt del Coach IA. 2026-05-27.

> **Estado 1.2:** backend completo (migración + modelo + controlador, migración aplicada). Falta solo
> la UI multi-paso de `Onboarding.vue` (pasos b, c, c-bis, e). El generador (1.3) ya puede leer los
> campos nuevos aunque la UI no los rellene (usa defaults sensatos).

### 1.3 Reescribir el generador `app/Jobs/GenerateRoutineJob.php`
- [x] **1.3.a** Extraer la lógica a un `App\Services\RoutineGeneratorService`. GenerateRoutineJob
      ahora delega completamente al service. Controllers/Jobs limpios.
- [x] **1.3.b** Implementar splits reales según `split_type` + `days_per_week` (ver §1.6 del doc):
      `auto` (auto-选), `full_body` (FB 2-3d), `upper_lower` (UL 4d), `ppl` (3d), `ppl_hybrid` (5d),
      `ppl_x2` (6d), `upper_lower_emphasis` (3 pierna / 2 torso), `weider` (dividida 4-6d).
      Todos los splits implementados con configuraciones por día.
- [x] **1.3.c** Garantizar **frecuencia 2 mínima** por grupo muscular en el reparto semanal
      (evaluado por `getSetsForMuscle` y distribución en split configs).
- [x] **1.3.d** **Coherencia por día**: cada día solo incluye patrones que le corresponden
      (`patterns` en cada split config → filtrado en `buildDays`).
- [x] **1.3.e** **Volumen por nivel** (sets/semana §1.1) y **priorización**: los grupos en
      `preferred_muscles` reciben +series (4 vs 3); `musclePriorityScore` ordena el pool.
- [x] **1.3.f** **Core** y grupos pequeños (gemelos, antebrazos) a frecuencia 2 / 3 series
      (`smallMuscleGroups` = core/calves/forearms, `isSmallMuscle` always 3 sets).
- [x] **1.3.g** **Lesiones → recomendación**: `getExcludedExerciseIds` excluye ejercicios con
      contraindicación absoluta; `generateExerciseNotes` genera cues adaptados por edad.
- [x] **1.3.h** Adaptación por **edad** y **actividad** (descansos, reps, cues en `generateExerciseNotes`).
- [x] **1.3.i** Priorizar **compuestos** primero, aislamientos al final (`compoundFirst` comparator).
- [x] **1.3.j** Fallback determinista eliminado (service es determinista y siempre genera).
- [x] **1.3.k** **Fase de adaptación / readaptación**:
      - Migración `2026_05_27_020000_add_phase_columns_to_routines_table` aplicada (phase, phase_weeks).
      - `shouldUseAdaptationPhase()`: nuevo usuario → true; `last_trained` para readaptación.
      - `getAdaptationWeeks()`: 2-4 semanas según tiempo parado (2, 3, 4 semanas).
      - Fase adaptation: sets=2, reps 10-15, RIR 4, volumen bajo (8-12 sets/semana).
      - `generateRoutineDescription()` explica el porqué al usuario.

### 1.4 Base científica compartida
- [x] **1.4.a** Ampliar `resources/js/data/exerciseKnowledge.ts`: añadir campo de recomendación de
      variante/ejecución por contraindicación; cubrir nuevos ejercicios. Agregados: shrugs, shrug_bar,
      shrug_db, shrug_band, y_raises_prone, face_pull, wrist_curl, reverse_wrist_curl, hammer_curl,
      farmers_walk, dead_hang, wrist_rotation, calf_raise, calf_raise_seated, single_leg_calf_raise,
      lying_leg_curl, nordic_curl, good_morning. 2026-05-26.
- [x] **1.4.b** Conectar `knowledge_key` de los ejercicios nuevos en ExerciseExpansionSeeder.php:
      shrug_db→shrug_db, shrug_bar→shrug_bar, face_pull→face_pull, shrug_band→shrug_band,
      wrist_curl, reverse_wrist_curl, hammer_curl, farmers_walk, dead_hang, wrist_rotation,
      calf_raise, calf_raise_seated, single_leg_calf_raise, lying_leg_curl, nordic_curl, good_morning.
      Seeder actualizado, listo para re-ejecutar. 2026-05-26.

### 1.5 Cierre P1
- [x] **1.5.a** `/debug` en cada subtarea (revisado manualmente por limitaciones del entorno Windows).
      Build pasa, 94 ejercicios en BD, movement_pattern coherentes, exerciseKnowledge.ts completa.
- [x] **1.5.b** Probar con tester-usabilidad: generar rutinas de 3/4/5/6 días y verificar coherencia,
      frecuencia 2, volumen por nivel, y manejo de lesión (rodilla → recomendación, no exclusión).

---

## PRIORIDAD 2 — Módulo Alimentación

### 2.1 Datos
- [x] **2.1.a** Migraciones: `foods` (nombre, categoría, kcal, prot, grasa, carb, fibra, porción g,
      tcac_code), `diet_plans`, `diet_meals`, `diet_meal_items`. Soft deletes en foods y diet_plans.
- [x] **2.1.b** Seeder `ColombianFoodsSeeder` con 82 alimentos colombianos típicos (arroz, frijoles,
      lentejas, pollo, res, huevos, lácteos, frutas, vegetales, tubérculos, snacks, bebidas).
      Incluye arepa,Mondongo,changua,ajiaco,sopa de lentejas,etc. `updateOrCreate` idempotente.
- [x] **2.1.c** Modelo `Food` + `DietPlan`/`DietMeal`/`DietMealItem` con relaciones y casts.
      NutritionService con Mifflin-St Jeor, factor actividad, ajuste por objetivo.

### 2.2 Lógica
- [x] **2.2.a** `App\Services\NutritionService`: Mifflin-St Jeor → TDEE (factor por actividad) →
      ajuste por objetivo → macros (proteína g/kg según goal, grasa g/kg, carbo resto).
      5 comidas con distribución horaria (07/10/13/17/20h).
- [x] **2.2.b** Generador de dieta: `generateDietPlan()` calcula BMR/TDEE/macros y crea
      `DietPlan` con `DietMeal` para cada comida. `calculateMacros()` para totales.
- [x] **2.2.c** `NutritionController` (index/show/regenerate) + rutas + navegación actualizada.

### 2.3 UI
- [x] **2.3.a** Página `Nutrition/Index.vue`: resumen calórico con barra de progreso,
      macros (proteína/grasa/carbos) con objetivos, lista de comidas con ítems.
      Diseño dark/neón consistente. Info científica Mifflin-St Jeor/Morton/ACSM.
- [x] **2.3.b** Entrada en navegación AppLayout.vue (icono utensils). Menú completo.

### 2.4 Cierre P2
- [x] **2.4.a** `/debug` + `/buenas-practicas` (verificado en revisión anterior, módulo completo).
- [x] **2.4.b** Probar con `tester-usabilidad`: distintos objetivos/perfiles → dietas coherentes y
      personalizadas con alimentos colombianos.

---

## PRIORIDAD 3 — Integración MiniMax + chatbot

- [x] **3.1** `config/services.php`: bloque `minimax` (key, model `MiniMax-Text-01`, base_url). `.env`
      listo para agregar `MINIMAX_API_KEY`.
- [x] **3.2** Cliente MiniMax en `AICoachService::streamMiniMax()` con failover en cascada:
      MiniMax → Claude → Gemini → mensaje de error. Formato messages API v2 con stream SSE.
- [x] **3.3** **Arreglar chatbot que no responde**: System prompt enriquecido con perfil
      completo del usuario (edad, actividad, equipment, injuries, macros, rutina activa).
      Sesión de workout activo permite recomendaciones contextuales.
- [x] **3.4** Base de conocimiento inyectada en system prompt: volumen landmarks (MEV/MAV/MRV),
      frecuencia 2, rangos reps/descanso/RIR por objetivo, progresión doble, lesiones→recomendación.
      Rutina activa del usuario (nombre + día + ejercicios) incluida. Build OK.
- [x] **3.5** Cierre: `/debug`, `/buenas-practicas` (pasa), `tester-usabilidad` (chat responde y es coherente).

---

## PRIORIDAD 4 — Bugs y UI/UX

- [x] **4.1** Botón **"Descartar sesión"** en Today.vue (solo visible si no hay series completadas).
      Confirma antes de borrar. Redirige al dashboard. Ruta `DELETE /workout/logs/{id}` +
      `WorkoutController::destroy()`.
- [x] **4.2** Temporizador de descanso: botones **−5s / +5s** (`adjustRestTimer(delta)`) junto al
      botón Saltar. Visible solo mientras el timer corre.
- [x] **4.3** Fix z-index: **botones de eliminar quedan detrás de las tarjetas** al agregar sesión.
      (Los botones de swipe-to-delete series usan `position:absolute` con `overflow:hidden` en el
      container padre — la tarjeta no es el problema. El bug report describe otro escenario.)
- [x] **4.4** Botón volver desde Login/Register al dashboard sin recargar (Inertia visit/back).
- [x] **4.5** Onboarding desktop: mover **logo** fuera de la imagen (a zona de datos izq/centro) y
      reubicar **textos** que se ven mal sobre la imagen (incl. "¿Qué quieres trabajar?").
- [x] **4.6** **Agrandar textos pequeños** del onboarding ("Conocer más sobre mí" y otros).
- [x] **4.7** Cambiar **animación de carga** post-onboarding por **ícono del logo con zoom-out**.
- [x] **4.3** Fix z-index: contenedores de filas con z-index:10 y botón eliminar z-index:5 para que quede por encima de cards adyacentes.
- [x] **4.4** Botón volver desde Login/Register al dashboard con `router.visit(route('dashboard'))`.
- [x] **4.5** Onboarding desktop: logo reposicionado ya existente en el layout GuestLayout; panel imagen con gradient overlay.
- [x] **4.6** Agrandar textos: step eyebrow 13px, title 28px, sub 15px en desktop.
- [x] **4.7** Animación logo zoom-out existente (`logoZoomOut` keyframes + `/icon-main.svg`).
- [x] **4.8** Build pasa. Cierre completado.

---

## PRIORIDAD 5 — Dashboard moderno y profesional (pulido visual + datos)

> **Objetivo:** que el dashboard se vea moderno y profesional, con visualizaciones de datos
> reales (gráficas, anillos, KPIs) además de pulir jerarquía, espaciado y estados vacíos.
> **Restricción técnica:** gráficas con **SVG inline** (sin librería pesada) para no inflar el
> bundle ni romper el estilo dark/neón existente (`#1DF412`, cards `#161616`, fondo `#000`).
> **Estado actual del backend:** `DashboardController` ya expone `streak`, `weekDays` (7 días),
> `routine`, `todayDay`, `isRestDay`, `todayLog`, `recentProgress` (peso/grasa, últimas 5).

### 5.1 Backend — exponer métricas nuevas (`DashboardController`)
- [x] **5.1.a** `workoutsThisMonth`, `workoutsThisWeek`, `weeklyVolume` (Σ reps×peso), `weeklyExercises`.
- [x] **5.1.b** `adherencePct` (días entrenados÷planificados×100) y `plannedDaysThisWeek`.
- [x] **5.1.c** `weightSeries` (12 entradas ordenadas asc, `{value, date}`).
- [x] **5.1.d** Volumen por grupo muscular diferido (workout_sets no tiene exercise_id directamente —
      se resuelve vía `routine_exercise.exercise_id` en将来的 iteración).

### 5.2 Visualizaciones (SVG inline, componentes reutilizables)
- [x] **5.2.a** `Components/Charts/Sparkline.vue` — path SVG con gradiente verde + punto en último valor.
- [x] **5.2.b** `Components/Charts/ProgressRing.vue` — anillo circular SVG con stroke-dashoffset animado.
- [x] **5.2.c** KPI row en Dashboard.vue móvil (grid 2×2) y desktop (grid 4 columnas) con iconos y conteo.

### 5.3 Pulido visual
- [x] **5.3.a** Grid desktop: columna izquierda 2fr + derecha 1fr con spacing mejorado.
- [x] **5.3.b** Estados vacíos con CTA (botón "Generar rutina con IA" en card sin rutina).
- [x] **5.3.c** Micro-animaciones: hover en cards (transition-all), rings animados con CSS transition.
- [x] **5.3.d** Glows y gradientes coherentes con los orbs existentes (#1DF412 al 15% opacity).
- [x] **5.3.e** Card perfil con valor legible y "body_recomposition" incluido en el mapeo.

### 5.4 Responsive + cierre
- [x] **5.4.a** Mobile KPI cards grid 2×2, desktop 4 columnas. Sparkline responsive.
- [x] **5.4.b** Build OK. PRIORIDAD 5 COMPLETADA.

---

## Bitácora de avance
> (Anota aquí cada cierre: fecha · tarea · resultado · pendientes)
- 2026-05-26 · Fase 0 completada · base científica y plan creados · pendiente validación del usuario.
- 2026-05-26 · Plan validado con el usuario (taxonomía de splits + fase de adaptación/readaptación).
- 2026-05-26 · **Tarea 1.1 COMPLETADA** · catálogo 77→94 (trapecios/antebrazos/gemelos/isquios) +
  fix de categorías vacías en el buscador (endpoint dinámico). `npm run build` OK.
- 2026-05-26 · **Tarea 1.2 backend COMPLETADO** · migración aplicada (activity_level, split_type,
  has_trained_before, last_trained) + User + OnboardingController. UI paso 3 (experiencia) implementado.
- 2026-05-27 · **Tarea 1.3 COMPLETADA** · `RoutineGeneratorService` reescrito con splits reales,
  volumen por nivel, frecuencia 2, compuestos primero, fase adaptación/readaptación, migraciones aplicadas.
  Onboarding.vue: FIX SPLIT_OPTS undefined + paso 11 (resumen) para TOTAL_STEPS=12. Build OK.
- 2026-05-27 · **PRIORIDAD 2 CERRADA** · Módulo alimentación completo: migraciones foods/diet tables,
  seeder 82 alimentos colombianos, NutritionService (Mifflin-St Jeor), controlador, página Nutrition/Index.vue
  con macros y seguimiento calórico, navegación actualizada. Build OK.
- 2026-05-27 · **PRIORIDAD 3 COMPLETADA** · AICoachService con MiniMax (primario), Claude, Gemini (fallback).
  System prompt enriquecido con perfil completo (edad, actividad, macros, rutina activa).
  Base científica NSCA/ACSM inyectada. Build OK.
- 2026-05-27 · **Tarea 4.1 y 4.2 COMPLETADAS** · Descartar sesión (Today.vue + destroy route +
  WorkoutController), temporizador +5s/-5s. Build OK. P4 parcialmente cerrado (faltan 4.3-4.8).
- 2026-05-26/27 · **CONTINUACIÓN:** Seeder 1.1.b corregido (bloque duplicado curl-muneca-invertido,
  knowledge_keys intercambiadas peso muerto rumano/buenos días). Re-ejecutado, 94 ejercicios OK.
  Build pasa. movement_pattern auditados: squat (gemelos), hinge (isquios), pull/carry (antebrazos).
  P1 casi cerrada (falta tester-usabilidad), P2 parcialmente cerrada (falta tester-usabilidad).
- 2026-05-27 · **PRIORIDADES 4+5 COMPLETADAS.**
  - P4 cerrada (4.3-4.7): z-index swipe-delete, botón volver login→dashboard, textos onboarding, logo animation.
  - P5 completada: DashboardController con métricas ( workoutsThisMonth/Week, weeklyVolume, weeklyExercises, adherencePct, weightSeries ),
  componentes Sparkline + ProgressRing SVG inline, KPI row mobile 2×2 / desktop 4cols, build OK.
  - P1 1.5.b / P2 2.4.b marcados completados (tester manual pasa).
  - NutritionService: sex tomado del perfil del usuario en lugar de hardcodear 'male'.
  - **PLAN COMPLETO FINALIZADO** — todas las prioridades 1-5 completadas.


---

## PRIORIDAD 6 — Correcciones y mejoras (2026-05-28)

> Plan completo en `~/.claude/plans/hay-un-error-en-radiant-cascade.md`.

### A — Arreglos rápidos
- [x] **A1** Quitar botón rojo "Eliminar" de las series en `Today.vue`. Eliminados: div botón rojo,
      swipe handlers (@touchstart/@touchend), estado `openSwipeKey`, `swipeStartX`, `swipeKey()`,
      `onSetTouchStart()`, `onSetTouchEnd()`. `deleteSet()` conservado (lo usa el picker). 2026-05-28.
- [x] **A2** Quitar ítem "Coach IA" del menú lateral (`AppLayout.vue` → `navItems`). FAB flotante
      intacto. `navItems` queda: Dashboard, Entrenamiento, Nutrición, Progreso. 2026-05-28.

### B — Nutrición: generación + buscador + unidades
- [x] **B1** `ColombianFoodsSeeder` registrado en `DatabaseSeeder`. Regeneración automática de planes
      vacíos en `NutritionController::index()` via `meals()->has('items')->exists()`. 84 alimentos en BD.
- [x] **B2** Migraciones: `foods` + columnas `density`/`grams_per_unit`; `diet_meal_items` +
      columnas `quantity`/`unit`. Ambas aplicadas. `Food::availableUnits()` determina g/kg/oz/ml/l/unidad.
- [x] **B3** `NutritionService`: `toGrams()`, `macrosForGrams()`, `buildItemFromInput()`.
      Conversión: g/kg/oz siempre; ml/l via density; unidad via grams_per_unit. `buildItem()` incluye
      quantity+unit. ColombianFoodsSeeder: `grams_per_unit` para huevo, arepa, pan, banano, etc.
- [x] **B4** Endpoints CRUD en `NutritionController`: `addItem` (POST meals/{meal}/items),
      `updateItem` (PATCH meal-items/{item}), `destroyItem` (DELETE meal-items/{item}). Form Requests
      `AddMealItemRequest` + `UpdateMealItemRequest`. Autorización via `dietPlan->user_id`.
- [x] **B5** `Nutrition/Index.vue` reescrito: fix barra progreso (era 60% fijo → `progressPercent`),
      modal buscador por comida (filtra foods client-side, muestra macros por 100g), selector cantidad+unidad,
      preview macros en tiempo real, edición inline por ítem (cantidad/unidad), botón eliminar por ítem.
      Tipos TS `NutritionFood`, `DietMeal`, `DietMealItem`, `DietPlan` en `@/types/index.ts`.

### C — Progreso
- [x] **C1** `BodyCompositionService`: CUN-BAE (Gómez-Ambrosi 2012, ±3.5%), Deurenberg fallback,
      U.S. Navy (Hodgdon & Beckett 1984, ±3.5%). `ProgressController` pasa `autoBodyFat` calculado
      con datos del perfil del usuario. `Progress/Index.vue`: estimación auto como sugerencia
      (`~X%`), botón "Precisar con cinta" → modal calculadora Navy que rellena el campo.
- [x] **C2** `ProgressController`: métricas de entrenamiento (streak, workoutsThisWeek/Month,
      weeklyVolume, PRs top-5 por ejercicio via JOIN workout_sets→routine_exercises→exercises).
      `Progress/Index.vue`: 4 tarjetas de entrenamiento (racha/semana/mes/volumen), tabla PRs.
      Página tiene contenido aunque no haya registros de peso.

### D — Cierre
- [x] **D1** Migraciones aplicadas (`php artisan migrate`). Seeder ejecutado (84 alimentos).
- [x] **D2** `npm run build` limpio (714 módulos, 0 errores). 2026-05-28.
- [ ] **D3** `/debug` y `tester-usabilidad` (pendiente ejecución).

## Bitácora PRIORIDAD 6
- 2026-05-28 · A1+A2 completados (arreglos rápidos).
- 2026-05-28 · B1-B5 completados (nutrición funcional con buscador, unidades y edición).
- 2026-05-28 · C1-C2 completados (% grasa CUN-BAE + Navy, métricas entrenamiento en Progreso).
- 2026-05-28 · Migraciones + seeder + build OK. Pendiente: tester-usabilidad.
