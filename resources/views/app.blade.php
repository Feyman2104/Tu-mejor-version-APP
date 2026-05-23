<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#1DF412">

  <title inertia>{{ config('app.name', 'Tu Mejor Versión') }}</title>

  <!-- MediaPipe Pose — cargado desde CDN, NO instalar por npm (bundle WASM demasiado grande) -->
  @if(request()->is('posture*') || request()->routeIs('posture.*'))
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils/drawing_utils.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose/pose.js" crossorigin="anonymous"></script>
  @endif

  <!-- PWA -->
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

  @vite(['resources/js/app.ts', 'resources/css/app.css'])
  @inertiaHead
</head>
<body class="antialiased">
  @inertia
</body>
</html>
