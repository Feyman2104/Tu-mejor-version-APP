# Product

## Register

product

## Users

Estudiantes universitarios (18-25 años), nivel principiante a intermedio. Entrenan en gimnasio o en casa. Buscan estructura y ciencia detrás de sus rutinas: no quieren apps genéricas, quieren entender el porqué de cada ejercicio. Muchos no han tenido acceso a un entrenador personal real. Contexto de uso: móvil en el gym, sentados en casa revisando su progreso, onboarding en laptop.

## Product Purpose

Plataforma PWA de entrenamiento físico personalizado con IA. Genera rutinas científicas (splits reales, volumen por nivel, frecuencia 2, fase de adaptación), registra sesiones estilo Hevy, analiza postura con MediaPipe, ofrece chat con coach IA (MiniMax/Claude/Gemini). Módulo de nutrición con Mifflin-St Jeor. Éxito = el usuario entrena de forma consistente, entiende su progreso y confía en las recomendaciones.

## Brand Personality

Accesible · Científico · Confiable. La IA es un coach que enseña, no un bot que da órdenes. El rigor científico (NSCA, ACSM, Schoenfeld) es visible pero no intimidante. El tono es el de un entrenador que sabe de qué habla y te lo explica.

## Anti-references

- Apps de fitness con neon excesivo: todo brillante, exceso de color, sensación de videojuego. El acento verde (#1DF412) debe ser herramienta, no decoración. Cada uso del neon debe ganarse su lugar.
- MyFitnessPal: UI genérica, sensación de app vieja, azul/blanco sin personalidad.
- Generic AI SaaS: gradiente púrpura-azul, glassmorphism decorativo, Inter + tarjetas idénticas.
- Fitness amateur: verde lima en fondo blanco, fotos de stock, aspecto de plantilla WordPress.

## Design Principles

1. **El rigor se ve, no se declara.** Los datos científicos (MEV/MAV/RIR, Mifflin-St Jeor, fases de adaptación) deben ser visibles en la UI, no escondidos en tooltips.
2. **El neon es acento, no ambiente.** #1DF412 aparece en acciones primarias, estado activo y logros. No en decoración, fondos o bordes informativos.
3. **Mobile primero, siempre.** El usuario está en el gym con el teléfono. 390px es la pantalla real; 1440px es bonus.
4. **La carga nunca es sorpresa.** Skeleton loaders en todo; el usuario siempre sabe que algo está pasando.
5. **Consistencia es la feature.** Mismo vocabulario de componentes en todas las pantallas. El mismo botón "Completar serie" se ve igual en Today.vue y en el historial.

## Accessibility & Inclusion

WCAG AA como mínimo. Contraste especial en el neon verde (#1DF412) sobre negro (#000): verificar ratio para texto pequeño. Sin requisitos específicos adicionales declarados. Reducción de movimiento no implementada aún (GSAP/motion activos).
