<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#1DF412">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title inertia>{{ config('app.name', 'Tu Mejor Versión') }}</title>

  <!-- MediaPipe Pose — cargado desde CDN en TODAS las rutas autenticadas
       porque CoachModal (FAB) puede abrirse desde cualquier página.
       Sin crossorigin para evitar bloqueo CORS silencioso en redes locales.
       Versiones fijadas a la última API estable que exporta window.Pose y window.Camera. -->
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils@0.3.1675466862/camera_utils.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils@0.3.1675466124/drawing_utils.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose@0.5.1675469404/pose.js"></script>

  <!-- PWA — manifest apunta al build generado por vite-plugin-pwa -->
  <link rel="manifest" href="/build/manifest.webmanifest">
  <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

  @routes
  @vite(['resources/js/app.ts'])
  @inertiaHead
</head>
<body class="antialiased">
  @inertia
</body>
</html>
