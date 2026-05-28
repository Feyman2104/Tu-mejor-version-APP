# Diseño: Selector de días de entrenamiento

**Fecha:** 2026-05-28  
**Estado:** Aprobado  
**Stack:** Laravel 11 · Inertia.js v2 · Vue 3 `<script setup lang="ts">` · Tailwind v4

---

## Contexto

Actualmente el generador de rutinas asigna `day_number` de forma consecutiva (1, 2, 3…) al crear los `RoutineDay`. El `DashboardController` compara `day_number` con el día ISO de la semana actual (`now()->isoFormat('E')`, donde 1=lunes, 7=domingo) para determinar si hoy es día de entrenamiento o descanso.

El resultado: una rutina de 3 días siempre asigna lunes/martes/miércoles, sin importar cuándo entrena realmente el usuario. Si el usuario entrena lunes/jueves/sábado, el dashboard nunca mostrará su rutina correcta.

**Objetivo:** permitir que el usuario elija sus días reales de entrenamiento durante el onboarding. Esos días se almacenan, se usan como `day_number` en la generación de rutinas, y el dashboard los muestra correctamente de forma automática.

---

## Alcance

- Nuevo paso 10 en el wizard de onboarding (total: 14 pasos)
- Migración: columna `training_days` JSON en `users`
- Cambios en `OnboardingController`, `User` model, `RoutineGeneratorService`
- Sin cambios en `DashboardController` ni `WorkoutController` (ya usan `day_number` correctamente)
- Compatibilidad hacia atrás: usuarios sin `training_days` usan días consecutivos (comportamiento actual)

---

## Modelo de datos

### Nueva columna

```sql
ALTER TABLE users ADD COLUMN training_days JSON NULL AFTER days_per_week;
```

**Migración:** `2026_05_28_000001_add_training_days_to_users_table.php`

Valores: array de enteros ISO ordenado, ej. `[1, 3, 5]` = lunes, miércoles, viernes.  
Rango válido: 1–7. Mínimo 1 elemento cuando se guarda.

### User model

```php
// $fillable — añadir:
'training_days',

// casts() — añadir:
'training_days' => 'array',
```

---

## Paso 10 del onboarding — Vue

### Posición en el wizard

Insertado entre el actual paso 9 (Disponibilidad) y el actual paso 10 (Tipo de split).  
Todos los pasos desde el antiguo 10 en adelante suben a índice+1. `TOTAL_STEPS` pasa de 13 a 14.

### STEP_META — entrada nueva

```js
{ eyebrow: 'Tus días de entreno', title: '¿Qué días\nentrenas?' }
// insertar en posición 10 del array STEP_META
```

### Campo en `form`

```ts
training_days: number[] as number[]   // ISO 1–7, ordenado
```

Inicialización en `onMounted` / watch del paso: cuando se llega al paso 10, si `training_days` está vacío, se pre-rellena con la selección recomendada según `form.days_per_week`.

### Tabla de pre-selección (computed `defaultDays`)

| days_per_week | training_days |
|---|---|
| 1 | [3] |
| 2 | [1, 4] |
| 3 | [1, 3, 5] |
| 4 | [1, 2, 4, 5] |
| 5 | [1, 2, 3, 4, 5] |
| 6 | [1, 2, 3, 4, 5, 6] |
| 7 | [1, 2, 3, 4, 5, 6, 7] |

### Toggle de día

```ts
function toggleDay(iso: number) {
  const idx = form.training_days.indexOf(iso)
  if (idx === -1) {
    form.training_days.push(iso)
    form.training_days.sort((a, b) => a - b)
  } else {
    form.training_days.splice(idx, 1)
  }
  form.days_per_week = form.training_days.length  // sincroniza step 9
}
```

### canContinue para el paso 10

```ts
case 10: return form.training_days.length >= 1
```

### UI — componente de días

Lista vertical de 7 píldoras (Lunes → Domingo).  
Cada píldora muestra: inicial en tile cuadrado redondeado + nombre completo + etiqueta "Entre semana" / "Fin de semana".  
Estado activo: borde `#1DF412`, fondo `rgba(29,244,18,0.07)`, tile y nombre iluminados, checkmark verde.  
Bajo la lista: resumen reactivo — "**Lunes · Miércoles · Viernes** — 3 días/semana".

Nombres de días (array constante):

```ts
const DAYS = [
  { iso: 1, initial: 'L', name: 'Lunes',     type: 'Entre semana' },
  { iso: 2, initial: 'M', name: 'Martes',    type: 'Entre semana' },
  { iso: 3, initial: 'X', name: 'Miércoles', type: 'Entre semana' },
  { iso: 4, initial: 'J', name: 'Jueves',    type: 'Entre semana' },
  { iso: 5, initial: 'V', name: 'Viernes',   type: 'Entre semana' },
  { iso: 6, initial: 'S', name: 'Sábado',    type: 'Fin de semana' },
  { iso: 7, initial: 'D', name: 'Domingo',   type: 'Fin de semana' },
]
```

---

## OnboardingController

### Validación (añadir a las reglas existentes)

```php
'training_days'   => ['required', 'array', 'min:1'],
'training_days.*' => ['integer', 'between:1,7'],
```

### Almacenamiento (añadir al `$user->update([...])`)

```php
'training_days' => $validated['training_days'],
'days_per_week' => count($validated['training_days']),  // derivado server-side, no se confía en el frontend
```

---

## RoutineGeneratorService

### loadUserProfile — nueva variable de instancia

```php
private array $trainingDays = [];

// en loadUserProfile():
$rawDays = $this->user->training_days ?? [];
$this->trainingDays = array_values(array_filter(
    array_map('intval', $rawDays),
    fn ($d) => $d >= 1 && $d <= 7
));
```

### generate — asignación de day_number

```php
// Antes (consecutivo):
'day_number' => $dayIndex + 1,

// Después (día real si existe, fallback a consecutivo):
'day_number' => $this->trainingDays[$dayIndex] ?? ($dayIndex + 1),
```

Si el usuario tiene `training_days = [1,3,5]` y la rutina genera 3 días:
- Día 0 → `day_number = 1` (lunes)
- Día 1 → `day_number = 3` (miércoles)
- Día 2 → `day_number = 5` (viernes)

Si `training_days` está vacío (usuarios anteriores) → fallback a `$dayIndex + 1` (comportamiento anterior preservado).

---

## Flujo completo post-implementación

```
Onboarding paso 9:  days_per_week = 3, session_duration_minutes = 60
                          ↓  (pre-selección automática L/X/V)
Onboarding paso 10: training_days = [1,3,5]  → days_per_week = 3 (sincronizado)
                          ↓
OnboardingController: user.training_days = [1,3,5], user.days_per_week = 3
                          ↓
GenerateRoutineJob → RoutineGeneratorService.generate()
  routine_days creados: day_number=1, day_number=3, day_number=5
                          ↓
DashboardController: todayIso = now()->isoFormat('E')
  Si hoy es lunes (1)    → todayDay = routine_day con day_number=1 → muestra rutina ✅
  Si hoy es martes (2)   → todayDay = null → isRestDay = true ✅
  Si hoy es miércoles (3)→ todayDay = routine_day con day_number=3 → muestra rutina ✅
```

---

## Compatibilidad hacia atrás

- Usuarios con `training_days = null`: `RoutineGeneratorService` usa fallback a días consecutivos. Sin romper rutinas existentes.
- El comando `routines:repair` existente puede usarse para regenerar rutinas de usuarios que quieran adoptar el nuevo sistema.

---

## Archivos a modificar

| Archivo | Cambio |
|---|---|
| `database/migrations/2026_05_28_000001_add_training_days_to_users_table.php` | Nuevo |
| `app/Models/User.php` | `$fillable` + `casts` |
| `app/Http/Controllers/OnboardingController.php` | Validación + almacenamiento |
| `app/Services/RoutineGeneratorService.php` | `$trainingDays` + asignación `day_number` |
| `resources/js/Pages/Onboarding.vue` | Nuevo paso 10, `DAYS` constante, `toggleDay()`, `canContinue`, `TOTAL_STEPS=14` |

**Sin cambios:** `DashboardController`, `WorkoutController`, modelos `Routine`/`RoutineDay`.

---

## Verificación

1. Registro de usuario nuevo → completar onboarding eligiendo L/J/S → verificar en BD: `users.training_days = [1,4,6]`
2. Dashboard en lunes → debe mostrar el día de rutina (no "día de descanso")
3. Dashboard en martes → debe mostrar "día de descanso"
4. Pantalla Entrenamiento → debe listar los días de la rutina con nombres correctos
5. Usuario antiguo (sin `training_days`) → rutina y dashboard siguen funcionando igual que antes
6. `php artisan serve` + revisar consola sin errores JS ni PHP
