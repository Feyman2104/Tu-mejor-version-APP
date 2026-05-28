---
timestamp: 2026-05-28T03-09-32Z
slug: toda-la-interfaz
---
## Audit Health Score: 9/20 — Poor

| Dimensión | Score |
|-----------|-------|
| Accesibilidad | 2/4 |
| Rendimiento | 2/4 |
| Theming | 1/4 |
| Responsive | 3/4 |
| Anti-Patterns | 1/4 |

## Design Health Score: 21/40 — Acceptable

| Heurística | Score |
|-----------|-------|
| Visibilidad del estado | 2/4 |
| Mundo real | 3/4 |
| Control y libertad | 2/4 |
| Consistencia | 2/4 |
| Prevención de errores | 2/4 |
| Reconocimiento | 2/4 |
| Flexibilidad | 3/4 |
| Diseño minimalista | 2/4 |
| Recuperación de errores | 1/4 |
| Ayuda y documentación | 2/4 |

## Priority Issues

- [P1] Sistema de color sin semántica (Dashboard.vue) — 13 colores sin token system
- [P1] Chat y Postura fuera del nav principal (AppLayout.vue)
- [P1] setInterval sin cleanup → memory leak (Dashboard.vue:98-115)
- [P1] #4B5563 sobre negro = 2.86:1 — WCAG AA fail (Dashboard + Today)
- [P2] Flash notifications sin role="alert" (AppLayout.vue:125-134)
- [P2] Onboarding con Unsplash stock photos (Onboarding.vue:14-20)
- [P2] Hover con 3 implementaciones distintas, sin keyboard focus (AppLayout.vue)
