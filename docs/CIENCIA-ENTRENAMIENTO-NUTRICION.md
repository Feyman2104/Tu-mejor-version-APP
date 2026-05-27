# Base de conocimiento — Ciencia del entrenamiento y la nutrición

> Fuente de verdad científica para el generador de rutinas, el módulo de nutrición y el chatbot IA.
> Basado en literatura revisada por pares (Schoenfeld, Morton, ACSM) y la metodología divulgativa de
> Jeremy Ethier (Built With Science) y Trainologym. Última revisión: 2026-05.

---

## 1. CIENCIA DEL ENTRENAMIENTO DE FUERZA / HIPERTROFIA

### 1.1 Volumen (series efectivas por grupo muscular / semana)
Relación dosis-respuesta (Schoenfeld et al. 2017) + landmarks de Renaissance Periodization (Israetel):

| Landmark | Sigla | Series/semana (referencia) | Uso |
|----------|-------|----------------------------|-----|
| Volumen de mantenimiento | MV | 4–6 | Mantener masa (descargas, fases ocupadas) |
| Volumen mínimo efectivo | MEV | 8–10 | Punto de partida de un mesociclo |
| Volumen máximo adaptativo | MAV | 12–20 | Zona óptima de crecimiento |
| Volumen máximo recuperable | MRV | 20–25 | Techo antes de sobreentrenar |

**Regla práctica del generador:**
- Principiante: 8–10 series/semana por grupo.
- Intermedio: 12–16 series/semana por grupo.
- Avanzado: 16–20 series/semana por grupo (subir en grupos priorizados, no en todos).
- Core/abdomen, gemelos, antebrazos: mínimo 4–6 series/semana aunque no sean prioridad (frecuencia 2).

### 1.2 Frecuencia
- **Mínimo 2 sesiones/semana por grupo muscular** (frecuencia 2). Es superior a frecuencia 1 cuando el
  volumen NO está igualado, y nunca inferior cuando sí lo está (Schoenfeld 2016/2019). → El motor debe
  garantizar que cada grupo principal se entrene ≥2 veces por semana.
- A más volumen objetivo, repartirlo en más sesiones mejora la calidad de cada serie.

### 1.3 Intensidad y proximidad al fallo (RIR)
- Hipertrofia: 6–20 reps con RIR 0–3 (la mayoría del trabajo a RIR 1–3).
- Fuerza: 3–6 reps, ≥80% 1RM, RIR 0–2, descansos 3–5 min.
- Compuestos pesados: parar 1–2 reps antes del fallo. Aislamientos: se puede llegar al fallo.

### 1.4 Selección y orden de ejercicios
- **Priorizar compuestos** (multiarticulares) al inicio de la sesión; aislamientos al final.
- Compuestos e aislamientos producen hipertrofia similar en el músculo diana si volumen está igualado,
  pero los compuestos dan más estímulo total por unidad de tiempo (eficiencia).
- Favorecer ejercicios que cargan el músculo en **posición alargada** (long-muscle-length), p.ej.
  press inclinado, sentadilla profunda controlada, curl predicador, femoral tumbado.
- Cubrir patrones de movimiento: empuje horizontal/vertical, tracción horizontal/vertical, dominante de
  rodilla (sentadilla), dominante de cadera (bisagra), core anti-extensión/anti-rotación.

### 1.5 Sobrecarga progresiva y periodización
- Progresar semana a semana: +reps, +carga, o +1 serie en grupos rezagados.
- Mesociclos de 4–6 semanas MEV→MAV→(MRV) seguidos de **descarga** (deload) 1 semana al ~50% volumen.
- Escalar volumen por nivel: el avanzado necesita más volumen para el mismo estímulo relativo.

### 1.6 Tipos de split (elegibles por el usuario) y asignación por días
El onboarding pregunta el **tipo de organización** (`split_type`) y el motor lo combina con `days_per_week`:

| `split_type` | Agrupación | Días recomendados | Frecuencia/grupo | ¿Recomendado? |
|--------------|-----------|-------------------|------------------|---------------|
| `full_body` | Todos los grupos cada sesión | 2–3 | 2–3 | ✅ Ideal principiantes / poco tiempo |
| `upper_lower` (Torso-Pierna) | Torso: pecho, espalda, hombro, brazos · Pierna: cuádriceps, isquios, glúteo, gemelo, abdomen | 2–4 | 2 (a 4 días) | ✅ |
| `ppl` (Push-Pull-Legs) | Empuje: pecho/hombro/tríceps · Tirón: espalda/bíceps · Pierna: tren inferior | 3 ó 6 | 1 (a 3 días) / 2 (a 6 días) | ✅ a 6 días · ⚠️ a 3 días (freq 1) |
| `weider` (dividida) | 1–2 grupos por día (lun pecho, mar espalda…) | 4–6 | 1 | ⚠️ freq 1 (subóptimo vs. evidencia) |

**Reglas del motor:**
- **Frecuencia 2 mínima** es el ideal. `full_body`, `upper_lower` y `ppl`-6d la cumplen; `weider` y
  `ppl`-3d quedan en frecuencia 1 → permitidos por elección del usuario pero con aviso suave.
- Si el usuario no elige split, por defecto: 2–3 días → `full_body`; 4 días → `upper_lower`;
  5 días → `ppl`+torso/pierna; 6 días → `ppl` x2.
- **Énfasis en piernas** (preferencia del usuario): 3 días de pierna especializados
  (cuádriceps / glúteo-femoral / pierna completa) + 2 de torso, manteniendo torso a frecuencia 2 con
  volumen de mantenimiento.
- Si el `split_type` elegido no cuadra con los días (p.ej. Weider con 3 días), proponer el más cercano
  viable y avisar.

### 1.7 Adaptación por EDAD
- **< 18:** prioridad técnica, evitar cargas máximas/compresión axial alta. Rango 8–15 reps.
- **18–34:** sin restricciones especiales; tolera volumen e intensidad altos.
- **35–49:** +1 día de recuperación si se puede; descanso ≥60 s; calentamiento articular obligatorio.
- **50+:** carga moderada 10–15 reps; descanso ≥90 s; minimizar compresión espinal pesada (sentadilla
  con barra alta, peso muerto convencional pesado) → preferir prensa, hack, goblet, peso muerto rumano
  ligero, máquinas; incluir trabajo de **potencia** (concéntrico rápido) por declive de potencia con la
  edad (ACSM). Movilidad y equilibrio como parte del plan.

### 1.8 Adaptación por NIVEL DE ACTIVIDAD DIARIA (NEAT) — reemplaza "movilidad articular"
Mide cuán activa es la persona en su día a día (no su rango articular). Ajusta volumen inicial y cardio:
- **Sedentario** (oficina, <5k pasos): empezar conservador, +trabajo de base aeróbica, progresión lenta.
- **Poco activo** (5k–8k pasos): volumen estándar para su nivel.
- **Activo** (8k–12k pasos, trabajo de pie): tolera más volumen; menos cardio adicional necesario.
- **Muy activo** (trabajo físico, >12k pasos): vigilar recuperación; el gasto extra cuenta para el TDEE.

### 1.9 Restricciones / lesiones → RECOMENDAR, no excluir
El sistema NO debe limitarse a "cuida tu rodilla". Debe dar una **recomendación accionable**: ajuste de
rango, tempo, postura o variante. Ejemplos basados en guías de fisioterapia (JOSPT/AAOS):

- **Rodilla (dolor patelofemoral):** no eliminar la sentadilla; reducir profundidad al rango sin dolor,
  tempo 3-0-3, preferir cadena cerrada controlada (sentadilla a cajón, prensa rango parcial, sentadilla
  goblet). Evitar valgo de rodilla. Variantes: wall-sit, step-ups bajos. Progresar al ceder el dolor.
- **Lumbar:** preferir bisagra con torso más vertical (peso muerto rumano ligero, hip thrust), reforzar
  core anti-extensión (plancha, dead bug). Evitar flexión lumbar cargada en fase aguda.
- **Hombro:** rango sin dolor; press neutro/landmine en vez de press tras nuca; elevaciones a 90° máx.
- **Muñeca:** agarre neutro con mancuernas; evitar extensión cargada.
- **Cadera:** evitar flexión extrema con carga en fase aguda; trabajar glúteo en rango medio.

> Implementación: cada `ExerciseThreshold`/`ContraIndication` en `exerciseKnowledge.ts` debe llevar un
> campo de **recomendación de ejecución/variante**, no solo una advertencia.

### 1.10 Fase de adaptación y readaptación (on-ramp)
Una persona nueva, o que retoma tras una pausa, NO debe empezar con la rutina completa: primero necesita
una fase de adaptación. Las primeras mejoras son **neurales** (coordinación, reclutamiento), y los tendones
y ligamentos se adaptan más lento que el músculo. Saltarse esto causa lesiones y agujetas severas (DOMS).

**Fase de adaptación anatómica (Bompa) — usuarios nuevos / principiantes:**
- Duración: **3–4 semanas** (hasta 6–8 en muy desacondicionados).
- Split: **Full Body**, 2–3 días.
- Volumen bajo (≈ MEV o menos), 1–2 series efectivas por ejercicio, RIR 3–4 (lejos del fallo).
- Reps moderadas (10–15), tempo controlado, foco en **técnica** y rango de movimiento.
- Progresión suave; al terminar → pasar a la rutina/volumen objetivo de su nivel.

**Readaptación por desentrenamiento — según tiempo sin entrenar (`last_workout_at`):**
| Tiempo parado | Acción | Carga inicial | Duración readaptación |
|---------------|--------|---------------|------------------------|
| < 2 semanas | Sin cambios | 100% | — |
| 2–4 semanas | Re-entrada suave | ~85% | 1 semana |
| 4–8 semanas | Readaptación | ~70–80% | 2 semanas |
| > 8 semanas | Como novato | ~50–70% | 3–4 semanas |

**Cómo conoce el sistema el tiempo sin entrenar (data capture):**
- **Usuario nuevo (onboarding):** se pregunta *"¿Has entrenado antes?"* y, si sí, *"¿hace cuánto fue tu
  último entrenamiento?"* (actualmente / <1 mes / 1–3 meses / 3–6 meses / +6 meses). Se guarda en
  `users.last_trained` (+ `has_trained_before`). Combinado con `level` decide adaptación vs readaptación.
- **Usuario recurrente:** se usa la fecha del último `workout_log` (`last_workout_at`). Si vuelve tras
  una pausa, el sistema lo detecta automáticamente sin volver a preguntar. El historial real manda sobre
  lo declarado en onboarding.

**Regla del sistema:**
- El motor marca la rutina con una `phase` (`adaptation` | `main`) y `phase_weeks`.
- Siempre se **explica al usuario el porqué** antes de empezar ("estás retomando tras X semanas;
  empezamos con N semanas a menor volumen para readaptar tendones y sistema nervioso y evitar lesiones").
- La fase de adaptación es **saltable por el principiante** (botón "Omitir"), pero mostrando un aviso
  claro de por qué no debería (riesgo de lesión y agujetas severas). Queda registrado si la omite.
- Al cumplir las semanas de adaptación, el sistema **progresa automáticamente** a la fase `main`.
- DOMS: agujetas normales 24–72 h y bajan en 5–7 días; el arranque suave (efecto de bout repetido)
  las reduce en las sesiones siguientes.

---

## 2. CIENCIA DE LA NUTRICIÓN

### 2.1 Gasto energético (TDEE)
**BMR — Mifflin-St Jeor** (más preciso que Harris-Benedict, dentro del 10% medido):
- Hombres: BMR = 10·peso(kg) + 6.25·altura(cm) − 5·edad + 5
- Mujeres: BMR = 10·peso(kg) + 6.25·altura(cm) − 5·edad − 161

**TDEE = BMR × factor de actividad** (usar el nivel de actividad diaria del onboarding, no solo el gym):
| Nivel | Factor |
|-------|--------|
| Sedentario | 1.2 |
| Poco activo (1–3 entrenos/sem) | 1.375 |
| Activo (3–5 entrenos/sem) | 1.55 |
| Muy activo (6–7 entrenos/sem) | 1.725 |
| Atleta / trabajo físico | 1.9 |

### 2.2 Ajuste calórico por objetivo
- **Pérdida de grasa:** déficit 10–20% del TDEE (≈ −300 a −500 kcal/día → ~0.25–0.5 kg/sem). No bajar de
  ~1200 kcal (mujer) / ~1500 kcal (hombre) sin supervisión.
- **Mantenimiento / recomposición:** ≈ TDEE (recomp funciona mejor en principiantes y con proteína alta).
- **Ganancia muscular:** superávit 5–15% (≈ +200 a +400 kcal/día → ~0.25–0.5 kg/sem para minimizar grasa).

### 2.3 Macronutrientes
- **Proteína:** 1.6–2.2 g/kg/día (meseta de beneficio ~1.6 g/kg, Morton 2018). En déficit usar el extremo
  alto (~2.0–2.4 g/kg) para preservar masa magra. 1 g proteína = 4 kcal.
- **Grasa:** 0.6–1.0 g/kg/día (mínimo ~0.5 g/kg para función hormonal). 1 g grasa = 9 kcal.
- **Carbohidratos:** el resto de las calorías. 1 g carbohidrato = 4 kcal. Priorizar alrededor del entreno.
- **Fibra:** ~14 g por cada 1000 kcal.

**Orden de cálculo del módulo:** TDEE → ajuste por objetivo → fijar proteína (g/kg) → fijar grasa (g/kg)
→ carbohidratos = (kcal restantes / 4). Reparte en 3–5 comidas.

### 2.4 Base de datos de alimentos (contexto colombiano)
- **Fuente primaria:** Tabla de Composición de Alimentos Colombianos (TCAC) del ICBF — energía y
  macros/micros por 100 g de alimentos consumidos en Colombia. (icbf.gov.co)
- **Respaldo LatAm / productos de marca:** Open Food Facts (subset Latinoamérica).
- Estructura mínima por alimento: nombre, categoría, kcal, proteína, grasa, carbohidrato, fibra,
  porción típica (g) y, si aplica, código TCAC. Permitir equivalencias/intercambios por grupo.

### 2.5 Principios para dietas NO genéricas
- Personalizar por peso, altura, edad, sexo, actividad y objetivo (no plantillas fijas).
- Respetar preferencias/alergias y presupuesto cuando se conozcan.
- Adherencia > perfección: ofrecer intercambios de alimentos del mismo grupo y porciones realistas
  colombianas (arepa, frijol, plátano, arroz, huevo, pollo, etc.).

---

## 3. CÓMO USA ESTO EL SISTEMA
- **Generador de rutinas:** §1.1–1.8 definen volumen, frecuencia, split, sets/reps/descanso y orden.
- **Adaptaciones:** §1.7 (edad), §1.8 (actividad), §1.9 (lesiones → recomendación, no exclusión).
- **Módulo nutrición:** §2.1–2.5 definen el cálculo y la base de alimentos.
- **Chatbot:** este documento se inyecta (resumido) como contexto/RAG para que las respuestas sean
  consistentes con la rutina y la dieta del usuario.

## 4. REFERENCIAS
- Schoenfeld BJ et al. (2017) *Dose-response: weekly volume and hypertrophy*. J Sports Sci.
- Schoenfeld BJ et al. (2016/2019) *Training frequency meta-analyses*. J Sports Sci.
- Morton RW et al. (2018) *Protein supplementation meta-analysis*. Br J Sports Med (~1.6 g/kg meseta).
- ACSM (2009 / 2026) *Position stands: progression & resistance training prescription*.
- Renaissance Periodization (Israetel) — volume landmarks MV/MEV/MAV/MRV.
- JOSPT (2019) *Patellofemoral Pain CPG* · AAOS *Knee Conditioning Program*.
- ICBF — Tabla de Composición de Alimentos Colombianos (TCAC).
- Mifflin MD, St Jeor ST et al. (1990) — ecuación BMR.
- Divulgación aplicada: Jeremy Ethier (Built With Science), Trainologym.
