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
- [~] **1.1.b** `movement_pattern` correcto en los ejercicios NUEVOS y verificada la convención
      existente (gemelos→squat, isquios→hinge, bíceps/espalda→pull). La auditoría completa de los 77
      originales + la coherencia por día se refuerza en **1.3.d** (validación post-generación).
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
- [ ] **1.2.d** Usar `age` para bucket de edad y `activity_level` en el generador.
- [x] **1.2.e** Historial de entrenamiento: backend listo (`has_trained_before`, `last_trained` en
      migración + controlador). **UI PENDIENTE** en `Onboarding.vue`: "¿Has entrenado antes?" (sí/no)
      y "¿hace cuánto fue tu último entrenamiento?" (actualmente / <1m / 1–3m / 3–6m / +6m). Alimenta §1.10.

> **Estado 1.2:** backend completo (migración + modelo + controlador, migración aplicada). Falta solo
> la UI multi-paso de `Onboarding.vue` (pasos b, c, c-bis, e). El generador (1.3) ya puede leer los
> campos nuevos aunque la UI no los rellene (usa defaults sensatos).

### 1.3 Reescribir el generador `app/Jobs/GenerateRoutineJob.php`
- [ ] **1.3.a** Extraer la lógica a un `App\Services\RoutineGeneratorService` (Controllers/Jobs finos).
- [ ] **1.3.b** Implementar splits reales según `split_type` + `days_per_week` (ver §1.6 del doc):
      FB(2-3d), UL(4d), PPL+UL o P/P/L/Torso/Pierna(5d), PPLx2(6d).
- [ ] **1.3.c** Garantizar **frecuencia 2 mínima** por grupo muscular en el reparto semanal.
- [ ] **1.3.d** **Coherencia por día**: cada día solo incluye patrones que le corresponden
      (validación post-generación que descarta ejercicios fuera de patrón del día).
- [ ] **1.3.e** **Volumen por nivel** (sets/semana §1.1) y **priorización**: los grupos en
      `preferred_muscles` reciben más series/ejercicios (no número fijo `$perDay=5`).
- [ ] **1.3.f** Incluir **core** y grupos pequeños (gemelos, antebrazos) a frecuencia 2 / 3 series
      aunque no sean prioridad.
- [ ] **1.3.g** **Lesiones → recomendación**: en vez de excluir, generar `notes` con ajuste de
      rango/tempo/variante (tomado de `exerciseKnowledge.ts`). Solo excluir si contraindicación absoluta.
- [ ] **1.3.h** Adaptación por **edad** y **actividad** (descansos, reps, compresión axial, cardio).
- [ ] **1.3.i** Priorizar **compuestos** primero, aislamientos al final del día.
- [ ] **1.3.j** Fallback determinista actualizado con la misma lógica (no quedar en versión vieja).
- [ ] **1.3.k** **Fase de adaptación / readaptación** (ver §1.10 del doc):
      - Añadir `phase` (`adaptation`|`main`) y `phase_weeks` a `routines` (migración).
      - Usuario nuevo/principiante → generar fase de adaptación (Full Body, volumen bajo, RIR 3–4,
        técnica) de 3–4 semanas antes de la rutina objetivo.
      - Detectar retomada por `last_workout_at` (o última `workout_log`): aplicar carga/volumen
        reducidos según la tabla de §1.10 (<2 / 2–4 / 4–8 / >8 semanas).
      - **Explicar el porqué** al usuario (texto en la rutina/onboarding) antes de empezar.
      - Fase de adaptación **saltable para principiantes** (botón "Omitir") con aviso del riesgo;
        registrar si la omite.
      - Progresar automáticamente a fase `main` al cumplir las semanas.

### 1.4 Base científica compartida
- [ ] **1.4.a** Ampliar `resources/js/data/exerciseKnowledge.ts`: añadir campo de recomendación de
      variante/ejecución por contraindicación; cubrir nuevos ejercicios.
- [ ] **1.4.b** Conectar `knowledge_key` de los ejercicios nuevos.

### 1.5 Cierre P1
- [ ] **1.5.a** `/debug` en cada subtarea, `/buenas-practicas` al cerrar P1.
- [ ] **1.5.b** Probar con `tester-usabilidad`: generar rutinas de 3/4/5/6 días y verificar coherencia,
      frecuencia 2, volumen por nivel, y manejo de lesión (rodilla → recomendación, no exclusión).

---

## PRIORIDAD 2 — Módulo Alimentación

### 2.1 Datos
- [ ] **2.1.a** Migraciones: `foods` (nombre, categoría, kcal, prot, grasa, carb, fibra, porción g,
      tcac_code), `diet_plans`, `diet_meals`, `diet_meal_items`. Soft deletes donde aplique.
- [ ] **2.1.b** Seeder/Importador de la TCAC del ICBF (comando `php artisan foods:import-tcac`).
      Respaldo Open Food Facts LatAm para productos de marca.
- [ ] **2.1.c** Campos de nutrición en users si faltan (sexo, objetivo ya existe).

### 2.2 Lógica
- [ ] **2.2.a** `App\Services\NutritionService`: Mifflin-St Jeor → TDEE (factor por actividad) →
      ajuste por objetivo → macros (proteína g/kg, grasa g/kg, carbo resto). Ver §2 del doc.
- [ ] **2.2.b** Generador de dieta: reparto en comidas con alimentos reales de `foods`, porciones e
      intercambios por grupo. Evitar dietas genéricas (personalizar por perfil).
- [ ] **2.2.c** Controlador + rutas + Form Requests + Policies.

### 2.3 UI
- [ ] **2.3.a** Páginas Vue: resumen calórico/macros, plan de dieta del día, lista de intercambios,
      registro/seguimiento de calorías. Tokens de diseño dark/neón existentes.
- [ ] **2.3.b** Entrada en navegación (nuevo módulo "Alimentación").

### 2.4 Cierre P2
- [ ] **2.4.a** `/debug` + `/buenas-practicas`.
- [ ] **2.4.b** Probar con `tester-usabilidad`: distintos objetivos/perfiles → dietas coherentes y
      personalizadas con alimentos colombianos.

---

## PRIORIDAD 3 — Integración MiniMax + chatbot

- [ ] **3.1** `config/services.php`: bloque `minimax` (key, model `MiniMax-Text-01`, base_url). `.env`.
- [ ] **3.2** Cliente MiniMax en `App\Services\AICoachService` (chat) y en el generador de rutinas/dieta.
      Manejar formato de mensajes y JSON de salida; failover claro si la API falla.
- [ ] **3.3** **Arreglar chatbot que no responde** (revisar `ChatController` + `useChat.ts` + SSE).
- [ ] **3.4** Inyectar la base de conocimiento (`docs/CIENCIA-...md` resumida) + rutina activa +
      historial reciente en el system prompt (cubre la P4 vieja del CLAUDE.md).
- [ ] **3.5** Cierre: `/debug`, `/buenas-practicas`, `tester-usabilidad` (chat responde y es coherente).

---

## PRIORIDAD 4 — Bugs y UI/UX

- [ ] **4.1** Botón **"Descartar entrenamiento"** en sesión activa (`Workout/Today.vue`), distinto de
      "Finalizar". Confirmación y limpieza de estado/`useWorkoutSession`.
- [ ] **4.2** Temporizador de descanso: botones **+5s / −5s** (componente de descanso en Today/Modal).
- [ ] **4.3** Fix z-index: **botones de eliminar quedan detrás de las tarjetas** al agregar sesión.
- [ ] **4.4** **Botón volver** desde Login/Register al dashboard sin recargar (Inertia visit/back).
- [ ] **4.5** Onboarding desktop: mover **logo** fuera de la imagen (a zona de datos izq/centro) y
      reubicar **textos** que se ven mal sobre la imagen (incl. "¿Qué quieres trabajar?").
- [ ] **4.6** **Agrandar textos pequeños** del onboarding ("Conocer más sobre mí" y otros).
- [ ] **4.7** Cambiar **animación de carga** post-onboarding por **ícono del logo con zoom-out**.
- [ ] **4.8** Cierre: `/debug`, `/buenas-practicas`, `tester-usabilidad` (mobile 390px + desktop).

---

## Bitácora de avance
> (Anota aquí cada cierre: fecha · tarea · resultado · pendientes)
- 2026-05-26 · Fase 0 completada · base científica y plan creados · pendiente validación del usuario.
- 2026-05-26 · Plan validado con el usuario (taxonomía de splits + fase de adaptación/readaptación).
- 2026-05-26 · **Tarea 1.1 COMPLETADA** · catálogo 77→94 (trapecios/antebrazos/gemelos/isquios) +
  fix de categorías vacías en el buscador (endpoint dinámico). `npm run build` OK.
- 2026-05-26 · **Tarea 1.2 backend COMPLETADO** · migración aplicada (activity_level, split_type,
  has_trained_before, last_trained) + User + OnboardingController. Falta UI de Onboarding.vue.
  Siguiente: UI de onboarding (1.2 b/c/c-bis/e) o saltar a 1.3 (generador) según prioridad del usuario.
