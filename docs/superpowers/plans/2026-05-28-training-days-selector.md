# Training Days Selector — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir que el usuario elija qué días de la semana entrena durante el onboarding; usar esos días como `day_number` en las rutinas generadas para que el dashboard muestre la rutina correcta cada día.

**Architecture:** Se añade columna JSON `training_days` en `users`, un nuevo paso 10 en el wizard de onboarding, y se actualiza `RoutineGeneratorService` para usar los días reales en vez de días consecutivos. El dashboard y el WorkoutController ya leen `day_number` — no necesitan cambios.

**Tech Stack:** Laravel 11 · PHP 8.3 · Inertia.js v2 · Vue 3 `<script setup lang="ts">` · Tailwind v4 · MySQL

---

## Mapa de archivos

| Archivo | Acción | Responsabilidad |
|---|---|---|
| `database/migrations/2026_05_28_000001_add_training_days_to_users_table.php` | Crear | Columna `training_days JSON NULL` en `users` |
| `app/Models/User.php` | Modificar | `$fillable` + `casts` para `training_days` |
| `app/Http/Controllers/OnboardingController.php` | Modificar | Validación y almacenamiento de `training_days` |
| `app/Services/RoutineGeneratorService.php` | Modificar | `$trainingDays` property + uso en `generate()` |
| `resources/js/Pages/Onboarding.vue` | Modificar | Nuevo paso 10, constantes, toggleDay, canContinue, summary |

---

## Task 1: Migración — columna `training_days`

**Files:**
- Create: `database/migrations/2026_05_28_000001_add_training_days_to_users_table.php`

- [ ] **Step 1.1: Crear la migración**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('training_days')->nullable()->after('days_per_week');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('training_days');
        });
    }
};
```

- [ ] **Step 1.2: Ejecutar la migración**

Desde `C:/Users/Dell/OneDrive/Documentos/Claude/Projects/Tu mejor version/app`:
```bash
php artisan migrate
```

Salida esperada:
```
  INFO  Running migrations.
  2026_05_28_000001_add_training_days_to_users_table .... 14ms DONE
```

- [ ] **Step 1.3: Verificar columna en BD**

```bash
php artisan tinker --execute="echo Schema::hasColumn('users', 'training_days') ? 'OK' : 'FAIL';"
```

Salida esperada: `OK`

- [ ] **Step 1.4: Commit**

```bash
git add database/migrations/2026_05_28_000001_add_training_days_to_users_table.php
git commit -m "feat: add training_days column to users table"
```

---

## Task 2: User model

**Files:**
- Modify: `app/Models/User.php`

- [ ] **Step 2.1: Añadir `training_days` a `$fillable`**

En `app/Models/User.php`, reemplaza el bloque `$fillable` actual (líneas 15-23) por:

```php
protected $fillable = [
    'name', 'email', 'password',
    'role', 'level', 'goal',
    'equipment', 'injuries', 'avatar',
    'onboarding_completed_at',
    'age', 'sex', 'weight_kg', 'height_cm', 'mobility', 'activity_level',
    'place', 'days_per_week', 'training_days', 'session_duration_minutes',
    'preferred_muscles', 'split_type', 'has_trained_before', 'last_trained',
];
```

- [ ] **Step 2.2: Añadir cast para `training_days`**

En el método `casts()`, añade la línea tras `'preferred_muscles' => 'array',`:

```php
'training_days'   => 'array',
```

El método completo queda:

```php
protected function casts(): array
{
    return [
        'email_verified_at'       => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'password'                => 'hashed',
        'equipment'               => 'array',
        'injuries'                => 'array',
        'preferred_muscles'       => 'array',
        'training_days'           => 'array',
        'has_trained_before'      => 'boolean',
    ];
}
```

- [ ] **Step 2.3: Verificar con Tinker**

```bash
php artisan tinker --execute="
\$u = App\Models\User::first();
\$u->training_days = [1,3,5];
\$u->save();
echo json_encode(App\Models\User::find(\$u->id)->training_days);
\$u->training_days = null;
\$u->save();
"
```

Salida esperada: `[1,3,5]`

- [ ] **Step 2.4: Commit**

```bash
git add app/Models/User.php
git commit -m "feat: add training_days to User fillable and casts"
```

---

## Task 3: OnboardingController — validación y almacenamiento

**Files:**
- Modify: `app/Http/Controllers/OnboardingController.php`

- [ ] **Step 3.1: Añadir reglas de validación**

Dentro del array de `$request->validate([...])` (después de la regla `'last_trained'`), añade:

```php
'training_days'   => ['required', 'array', 'min:1'],
'training_days.*' => ['integer', 'between:1,7'],
```

El bloque de validación completo (líneas 20-43) queda:

```php
$validated = $request->validate([
    'name'                     => ['required', 'string', 'max:255'],
    'age'                      => ['nullable', 'integer', 'min:10', 'max:100'],
    'sex'                      => ['nullable', 'in:male,female,other'],
    'weight_kg'                => ['nullable', 'numeric', 'min:20', 'max:300'],
    'height_cm'                => ['nullable', 'integer', 'min:100', 'max:250'],
    'mobility'                 => ['nullable', 'in:good,average,limited'],
    'activity_level'           => ['nullable', 'in:sedentary,lightly_active,active,very_active'],
    'level'                    => ['required', 'in:beginner,intermediate,advanced'],
    'goal'                     => ['required', 'in:fat_loss,muscle_gain,strength,maintain,flexibility,cardio,body_recomposition'],
    'place'                    => ['required', 'in:home,gym,both'],
    'equipment'                => ['required', 'array', 'min:1'],
    'equipment.*'              => ['in:none,dumbbells,barbell,pull_up_bar,cables,machines,kettlebell,bands'],
    'injuries'                 => ['nullable', 'array'],
    'injuries.*.zone'          => ['required', 'string', 'in:knee,back,shoulder,wrist,ankle,neck,hip'],
    'injuries.*.notes'         => ['nullable', 'string', 'max:300'],
    'days_per_week'            => ['required', 'integer', 'min:1', 'max:7'],
    'session_duration_minutes' => ['required', 'integer', 'min:15', 'max:180'],
    'preferred_muscles'        => ['nullable', 'array'],
    'preferred_muscles.*'      => ['string'],
    'split_type'               => ['nullable', 'in:auto,full_body,upper_lower,ppl,weider'],
    'has_trained_before'       => ['nullable', 'boolean'],
    'last_trained'             => ['nullable', 'in:never,currently,lt_1m,1_3m,3_6m,gt_6m'],
    'training_days'            => ['required', 'array', 'min:1'],
    'training_days.*'          => ['integer', 'between:1,7'],
]);
```

- [ ] **Step 3.2: Añadir `training_days` al `$user->update()`**

Dentro del bloque `$user->update([...])` (líneas 46-66), añade dos líneas después de `'days_per_week'`:

```php
'training_days'            => $validated['training_days'],
'days_per_week'            => count($validated['training_days']),  // derivado server-side
```

El bloque completo queda:

```php
$user->update([
    'name'                     => $validated['name'],
    'age'                      => $validated['age'] ?? null,
    'sex'                      => $validated['sex'] ?? null,
    'weight_kg'                => $validated['weight_kg'] ?? null,
    'height_cm'                => $validated['height_cm'] ?? null,
    'mobility'                 => $validated['mobility'] ?? null,
    'activity_level'           => $validated['activity_level'] ?? null,
    'level'                    => $validated['level'],
    'goal'                     => $validated['goal'],
    'place'                    => $validated['place'],
    'equipment'                => $validated['equipment'],
    'injuries'                 => $validated['injuries'] ?? [],
    'days_per_week'            => count($validated['training_days']),
    'training_days'            => $validated['training_days'],
    'session_duration_minutes' => $validated['session_duration_minutes'],
    'preferred_muscles'        => $validated['preferred_muscles'] ?? [],
    'split_type'               => $validated['split_type'] ?? 'auto',
    'has_trained_before'       => $validated['has_trained_before'] ?? null,
    'last_trained'             => $validated['last_trained'] ?? null,
    'onboarding_completed_at'  => now(),
]);
```

- [ ] **Step 3.3: Commit**

```bash
git add app/Http/Controllers/OnboardingController.php
git commit -m "feat: validate and store training_days in OnboardingController"
```

---

## Task 4: RoutineGeneratorService — usar días reales

**Files:**
- Modify: `app/Services/RoutineGeneratorService.php`

- [ ] **Step 4.1: Añadir propiedad `$trainingDays`**

En el bloque de propiedades de clase (líneas 15-25), añade después de `private array $injuries;`:

```php
private array $trainingDays = [];
```

- [ ] **Step 4.2: Cargar `training_days` en `loadUserProfile()`**

Al final de `loadUserProfile()`, antes de la llamada a `$this->buildExercisePool()` (línea 53), añade:

```php
$rawDays = $this->user->training_days ?? [];
$this->trainingDays = array_values(array_filter(
    array_map('intval', $rawDays),
    fn ($d) => $d >= 1 && $d <= 7
));
```

El final de `loadUserProfile()` queda así:

```php
        $this->userProfile = [
            'goal'        => $this->user->goal,
            'level'       => $this->user->level ?? 'beginner',
            'equipment'   => $this->user->equipment ?? [],
            'place'       => $this->user->place ?? 'gym',
            'mobility'    => $this->user->mobility ?? 'good',
            'weight_kg'   => $this->user->weight_kg,
            'session_min' => $this->user->session_duration_minutes ?? 60,
        ];

        $rawDays = $this->user->training_days ?? [];
        $this->trainingDays = array_values(array_filter(
            array_map('intval', $rawDays),
            fn ($d) => $d >= 1 && $d <= 7
        ));

        $this->buildExercisePool();
    }
```

- [ ] **Step 4.3: Usar `$trainingDays` en `generate()`**

En el método `generate()`, localiza el `foreach` que crea los días (línea ~146):

```php
foreach ($days as $dayIndex => $dayData) {
    $day = $routine->days()->create([
        'day_number' => $dayIndex + 1,   // ← esta línea
```

Reemplaza `'day_number' => $dayIndex + 1,` por:

```php
'day_number' => $this->trainingDays[$dayIndex] ?? ($dayIndex + 1),
```

- [ ] **Step 4.4: Verificar lógica con Tinker**

```bash
php artisan tinker --execute="
\$u = App\Models\User::first();
\$u->training_days = [1, 3, 5];
\$u->days_per_week = 3;
\$u->save();
\$svc = new App\Services\RoutineGeneratorService(\$u);
\$routine = \$svc->generate();
echo 'day_numbers: ' . \$routine->days->pluck('day_number')->join(', ');
"
```

Salida esperada: `day_numbers: 1, 3, 5`

- [ ] **Step 4.5: Commit**

```bash
git add app/Services/RoutineGeneratorService.php
git commit -m "feat: use training_days as day_number in RoutineGeneratorService"
```

---

## Task 5: Onboarding.vue — nuevo paso 10 y ajustes

**Files:**
- Modify: `resources/js/Pages/Onboarding.vue`

Este task modifica el único archivo Vue. Se agrupa en sub-steps para facilitar el seguimiento.

### 5a — Constantes y `form`

- [ ] **Step 5a.1: Añadir constante `DAYS` y `DAYS_LABELS`**

Después del bloque de constantes existentes (después de la línea `const ACTIVITY_LABELS`), añade:

```ts
const DAYS = [
  { iso: 1, initial: 'L', name: 'Lunes',      type: 'Entre semana'  },
  { iso: 2, initial: 'M', name: 'Martes',     type: 'Entre semana'  },
  { iso: 3, initial: 'X', name: 'Miércoles',  type: 'Entre semana'  },
  { iso: 4, initial: 'J', name: 'Jueves',     type: 'Entre semana'  },
  { iso: 5, initial: 'V', name: 'Viernes',    type: 'Entre semana'  },
  { iso: 6, initial: 'S', name: 'Sábado',     type: 'Fin de semana' },
  { iso: 7, initial: 'D', name: 'Domingo',    type: 'Fin de semana' },
] as const

const DAYS_LABELS: Record<number, string> = {
  1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves',
  5: 'Viernes', 6: 'Sábado', 7: 'Domingo',
}

const DEFAULT_DAYS_MAP: Record<number, number[]> = {
  1: [3],
  2: [1, 4],
  3: [1, 3, 5],
  4: [1, 2, 4, 5],
  5: [1, 2, 3, 4, 5],
  6: [1, 2, 3, 4, 5, 6],
  7: [1, 2, 3, 4, 5, 6, 7],
}
```

- [ ] **Step 5a.2: Añadir `training_days` al `form`**

En `useForm({...})` (línea ~121), añade después de `split_type`:

```ts
training_days: [] as number[],
```

- [ ] **Step 5a.3: Cambiar `TOTAL_STEPS` de 13 a 14**

```ts
const TOTAL_STEPS = 14
```

- [ ] **Step 5a.4: Insertar nuevo entry en `STEP_META` en la posición 10**

El array `STEP_META` tiene actualmente 13 entradas (índices 0–12). Inserta el nuevo objeto **en la posición 10**, desplazando los actuales 10, 11, 12 a los índices 11, 12, 13.

El array resultante debe ser:

```ts
const STEP_META = [
  { eyebrow: 'Bienvenida',        title: 'Construye tu mejor versión.',        sub: 'Un plan científico y personalizado con IA en menos de 3 minutos.' },
  { eyebrow: 'Datos personales',  title: 'Cuéntanos sobre ti.',                sub: 'Estos datos permiten a la IA calibrar la intensidad correcta para ti.' },
  { eyebrow: 'Actividad',         title: '¿Cómo es tu día a día?',             sub: 'No hablamos de flexibilidad — sino de cuánto te mueves en tu rutina habitual.' },
  { eyebrow: 'Experiencia',       title: '¿Tienes experiencia?',               sub: 'El sistema adapta la fase de entrada a tu punto de partida real.' },
  { eyebrow: 'Nivel',             title: '¿Cuál es tu nivel?',                 sub: 'El sistema escala la dificultad automáticamente cada semana.' },
  { eyebrow: 'Objetivo',          title: 'Tu meta define el plan.',            sub: 'Define el norte de tu entrenamiento. Puedes cambiarlo cuando quieras.' },
  { eyebrow: 'Lugar',             title: '¿Dónde entrenas?',                   sub: 'Tu plan se adapta al espacio y equipamiento disponible.' },
  { eyebrow: 'Equipamiento',      title: 'Entrenamos con lo que tienes.',      sub: 'Tu plan usa solo el equipamiento que tengas disponible.' },
  { eyebrow: 'Salud',             title: 'Adaptamos sin riesgos.',             sub: 'Conocer tus limitaciones nos permite evitar molestias en cada rutina.' },
  { eyebrow: 'Disponibilidad',    title: '¿Cuánto tiempo tienes?',             sub: 'Ajustamos el volumen a los días y minutos que puedas dedicar.' },
  { eyebrow: 'Tus días de entreno', title: '¿Qué días\nentrenas?',             sub: 'Los días elegidos definen cuándo aparece tu rutina en el dashboard.' },
  { eyebrow: 'Tipo de split',     title: '¿Cómo distribuyes tus días?',        sub: 'Cada organización tiene pros y contras. Elige la que mejor encaje en tu semana.' },
  { eyebrow: 'Preferencias',      title: '¿Qué quieres trabajar más?',         sub: 'El plan da prioridad a los grupos musculares que elijas.' },
  { eyebrow: 'Resumen',           title: 'Todo listo.',                        sub: 'Revisa tu perfil y genera tu rutina personalizada con IA.' },
]
```

### 5b — Computed y funciones

- [ ] **Step 5b.1: Añadir `defaultDays` y `summaryDays` computed**

Después de `const progressPct = computed(...)`, añade:

```ts
const defaultDays = computed<number[]>(() =>
  DEFAULT_DAYS_MAP[form.days_per_week ?? 3] ?? [1, 3, 5]
)

const summaryDays = computed(() =>
  form.training_days.map(d => DAYS_LABELS[d]).join(' · ') || '—'
)
```

- [ ] **Step 5b.2: Actualizar `canContinue`**

Reemplaza el `switch` completo de `canContinue` por:

```ts
const canContinue = computed(() => {
  switch (step.value) {
    case 0:  return true
    case 1:  return true
    case 2:  return !!form.activity_level
    case 3:  return form.has_trained_before !== null
    case 4:  return !!form.level
    case 5:  return !!form.goal
    case 6:  return !!form.place
    case 7:  return form.equipment.length > 0
    case 8:  return true
    case 9:  return !!form.days_per_week && !!form.session_duration_minutes
    case 10: return form.training_days.length >= 1
    case 11: return true
    case 12: return true
    case 13: return true
    default: return false
  }
})
```

- [ ] **Step 5b.3: Añadir `toggleDay()` función**

Después de `toggleMuscle()` (línea ~257), añade:

```ts
function toggleDay(iso: number): void {
  const idx = form.training_days.indexOf(iso)
  if (idx === -1) {
    form.training_days.push(iso)
    form.training_days.sort((a, b) => a - b)
  } else {
    form.training_days.splice(idx, 1)
  }
  form.days_per_week = form.training_days.length
}
```

- [ ] **Step 5b.4: Añadir watch para pre-poblar al llegar al paso 10**

Después del watch de localStorage existente (después de la línea del `try { localStorage.setItem... }`), añade un nuevo watch:

```ts
watch(() => step.value, (newStep) => {
  if (newStep === 10 && form.training_days.length === 0) {
    form.training_days = [...defaultDays.value]
  }
})
```

- [ ] **Step 5b.5: Añadir `training_days` al localStorage**

En `onMounted`, en el array `fields` (línea ~200-203), añade `'training_days'`:

```ts
const fields: (keyof typeof form)[] = [
  'name','age','sex','weight_kg','height_cm','mobility','level','goal','place',
  'equipment','injuries','days_per_week','session_duration_minutes','preferred_muscles',
  'training_days',
]
```

En el watch de localStorage, añade `training_days: form.training_days` al objeto watcheado:

```ts
watch(
  () => ({
    step: step.value,
    name: form.name, age: form.age, sex: form.sex, weight_kg: form.weight_kg, height_cm: form.height_cm,
    mobility: form.mobility,
    activity_level: form.activity_level, level: form.level, goal: form.goal, place: form.place,
    equipment: form.equipment, injuries: form.injuries,
    days_per_week: form.days_per_week, session_duration_minutes: form.session_duration_minutes,
    preferred_muscles: form.preferred_muscles, split_type: form.split_type,
    has_trained_before: form.has_trained_before, last_trained: form.last_trained,
    training_days: form.training_days,
  }),
  (val) => {
    try { localStorage.setItem(LS_KEY, JSON.stringify(val)) } catch { /* quota */ }
  },
  { deep: true },
)
```

### 5c — Template

- [ ] **Step 5c.1: Insertar template del nuevo PASO 10**

Inmediatamente **antes** del comentario `<!-- ═══ PASO 10: Tipo de split ═══ -->` (línea 765), inserta el nuevo bloque:

```html
            <!-- ═══ PASO 10: Días de entreno ═══ -->
            <template v-if="step === 10">
              <div class="flex flex-col gap-2">
                <button
                  v-for="day in DAYS"
                  :key="day.iso"
                  type="button"
                  class="w-full flex items-center justify-between"
                  style="border-radius:14px;padding:14px 16px;cursor:pointer;transition:all 0.15s;border:1.5px solid;"
                  :style="form.training_days.includes(day.iso)
                    ? 'background:rgba(29,244,18,0.08);border-color:#1DF412;'
                    : 'background:#161616;border-color:rgba(255,255,255,0.06);'"
                  @click="toggleDay(day.iso)"
                >
                  <div class="flex items-center gap-3">
                    <div
                      class="flex items-center justify-center flex-shrink-0"
                      style="width:36px;height:36px;border-radius:10px;font-size:15px;font-weight:800;font-family:'Barlow Condensed',sans-serif;transition:all 0.15s;border:1.5px solid;"
                      :style="form.training_days.includes(day.iso)
                        ? 'background:rgba(29,244,18,0.15);border-color:#1DF412;color:#1DF412;'
                        : 'background:#1a1a1a;border-color:#2a2a2a;color:#444;'"
                    >
                      {{ day.initial }}
                    </div>
                    <div>
                      <div
                        class="font-semibold"
                        style="font-size:15px;"
                        :style="form.training_days.includes(day.iso) ? 'color:#fff;' : 'color:#888;'"
                      >{{ day.name }}</div>
                      <div
                        style="font-size:11px;"
                        :style="form.training_days.includes(day.iso) ? 'color:rgba(29,244,18,0.6);' : 'color:#444;'"
                      >{{ day.type }}</div>
                    </div>
                  </div>
                  <div
                    class="flex items-center justify-center flex-shrink-0"
                    style="width:22px;height:22px;border-radius:50%;transition:all 0.15s;"
                    :style="form.training_days.includes(day.iso)
                      ? 'background:#1DF412;border:2px solid #1DF412;'
                      : 'background:#1a1a1a;border:2px solid #2a2a2a;'"
                  >
                    <svg v-if="form.training_days.includes(day.iso)" width="12" height="10" viewBox="0 0 12 10" fill="none">
                      <path d="M1 5L4.5 8.5L11 1.5" stroke="#000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </div>
                </button>
              </div>

              <!-- Resumen reactivo -->
              <div
                v-if="form.training_days.length"
                style="margin-top:16px;background:#0d0d0d;border:1px solid #1a1a1a;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:10px;"
              >
                <div style="width:8px;height:8px;border-radius:50%;background:#1DF412;flex-shrink:0;"></div>
                <div style="font-size:13px;color:#aaa;">
                  <strong style="color:#1DF412;">{{ summaryDays }}</strong>
                  <span> — {{ form.training_days.length }} {{ form.training_days.length === 1 ? 'día' : 'días' }}/semana</span>
                </div>
              </div>
              <p v-else style="font-size:12px;color:#6B7280;margin-top:12px;">
                Selecciona al menos un día para continuar
              </p>
            </template>
```

- [ ] **Step 5c.2: Cambiar `step === 10` → `step === 11` en el bloque de split**

Busca `<!-- ═══ PASO 10: Tipo de split ═══ -->` y cambia el `v-if`:

```html
<!-- ═══ PASO 11: Tipo de split ═══ -->
<template v-if="step === 11">
```

- [ ] **Step 5c.3: Cambiar `step === 11` → `step === 12` en preferencias musculares**

```html
<!-- ═══ PASO 12: Preferencias musculares ═══ -->
<template v-if="step === 12">
```

- [ ] **Step 5c.4: Cambiar `step === 12` → `step === 13` en el resumen**

```html
<!-- ═══ PASO 13: Resumen ═══ -->
<template v-if="step === 13">
```

- [ ] **Step 5c.5: Añadir fila de días de entreno en el resumen (paso 13)**

En el bloque del resumen (ahora `v-if="step === 13"`), después de la fila de "Disponibilidad" (línea ~824-829), añade una nueva fila:

```html
<div style="display:flex;justify-content:space-between;align-items:flex-start;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
  <span style="font-size:13px;color:#6B7280;">Días de entreno</span>
  <span style="font-size:14px;font-weight:600;color:#1DF412;text-align:right;max-width:240px;">
    {{ summaryDays }}
  </span>
</div>
```

- [ ] **Step 5c.6: Actualizar el texto informativo del resumen**

Al final del resumen, el párrafo que dice "La IA diseñará tus X sesiones semanales..." actualmente referencia el paso 12. Como ahora es el paso 13, solo asegúrate de que el texto siga siendo correcto. Actualiza la frase para que mencione los días elegidos:

Reemplaza el texto de la nota informativa (bloque con `style="background:rgba(29,244,18,0.06)..."`) por:

```html
<div style="background:rgba(29,244,18,0.06);border:1px solid rgba(29,244,18,0.2);border-radius:14px;padding:14px 16px;margin-top:4px;display:flex;gap:10px;align-items:flex-start;">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1DF412" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <p style="font-size:13px;color:#D1FAE5;line-height:1.5;margin:0;">
    La IA diseñará tus <strong>{{ form.training_days.length }}</strong> sesiones semanales para {{ summaryDays }}. El dashboard mostrará tu rutina exactamente esos días.
  </p>
</div>
```

- [ ] **Step 5c.7: Commit**

```bash
git add resources/js/Pages/Onboarding.vue
git commit -m "feat: add training days selector as step 10 in onboarding wizard"
```

---

## Task 6: Build y verificación end-to-end

- [ ] **Step 6.1: Build de assets**

```bash
npm run build
```

Salida esperada: sin errores TypeScript. Debe mostrar algo como:
```
✓ built in Xs
```

Si hay errores TypeScript sobre tipos, revisa que `DAYS` esté tipado con `as const` y que `form.training_days` sea `number[]`.

- [ ] **Step 6.2: Levantar servidor**

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

- [ ] **Step 6.3: Verificación manual — usuario nuevo**

1. Abrir http://localhost:8000 e ir a registro de nuevo usuario
2. Completar el onboarding hasta el paso 10 — verificar que:
   - El título dice "¿Qué días entrenas?"
   - Aparecen los 7 días con nombres completos (Lunes–Domingo)
   - Al llegar, se pre-seleccionan días según `days_per_week` elegido en paso 9
   - Hacer clic en días adicionales los activa (borde verde, checkmark)
   - El resumen inferior se actualiza reactivamente ("Lunes · Miércoles · Viernes — 3 días/semana")
   - El botón "Continuar" está habilitado si hay ≥1 día seleccionado
3. Completar el onboarding hasta el paso 13 (Resumen) — verificar que aparece la fila "Días de entreno"
4. Enviar y verificar en BD:

```bash
php artisan tinker --execute="
\$u = App\Models\User::latest()->first();
echo 'training_days: ' . json_encode(\$u->training_days) . PHP_EOL;
echo 'days_per_week: ' . \$u->days_per_week . PHP_EOL;
"
```

Salida esperada (ejemplo con L/X/V):
```
training_days: [1,3,5]
days_per_week: 3
```

- [ ] **Step 6.4: Verificar que la rutina generada usa días correctos**

```bash
php artisan tinker --execute="
\$u = App\Models\User::latest()->first();
\$r = \$u->routines()->where('is_active', true)->with('days')->first();
echo 'day_numbers: ' . \$r->days->pluck('day_number')->join(', ') . PHP_EOL;
"
```

Salida esperada (si eligió L/X/V): `day_numbers: 1, 3, 5`

- [ ] **Step 6.5: Verificar dashboard**

Abrir http://localhost:8000/dashboard:
- Si hoy es uno de los días elegidos → debe mostrar la rutina del día (no "Día de descanso")
- Si hoy NO es uno de los días elegidos → debe mostrar "Día de descanso" ✓

- [ ] **Step 6.6: Verificar compatibilidad hacia atrás**

```bash
php artisan tinker --execute="
\$u = App\Models\User::where('training_days', null)->first();
if (\$u) {
    \$svc = new App\Services\RoutineGeneratorService(\$u);
    \$r = \$svc->generate();
    echo 'fallback day_numbers: ' . \$r->days->pluck('day_number')->join(', ') . PHP_EOL;
} else {
    echo 'No hay usuarios sin training_days para probar' . PHP_EOL;
}
"
```

Salida esperada: `fallback day_numbers: 1, 2, 3` (consecutivos — comportamiento anterior)

- [ ] **Step 6.7: Commit final**

```bash
git add -A
git commit -m "feat: training days selector — onboarding step 10 complete and verified"
```
