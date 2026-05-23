import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'
import path from 'path'

// Configuración principal de Vite para el proyecto Tu Mejor Versión
// Incluye: Vue 3, Laravel Inertia, Tailwind CSS v4 y soporte PWA
export default defineConfig({
  plugins: [
    // Plugin de Laravel para integración con Inertia y hot reload
    laravel({
      input: ['resources/js/app.ts'],
      refresh: true,
    }),
    // Plugin Vue 3 con configuración de URLs de assets
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    // Tailwind CSS v4 vía plugin de Vite (NO como plugin de PostCSS)
    tailwindcss(),
    // Plugin PWA con Workbox para soporte offline y caché de assets
    VitePWA({
      registerType: 'autoUpdate',
      devOptions: { enabled: false },
      includeAssets: ['favicon.ico', 'icons/*.png'],
      manifest: {
        name: 'Tu Mejor Versión',
        short_name: 'TuMejorVer',
        description: 'Plataforma de entrenamiento físico personalizado con IA para universitarios',
        theme_color: '#000000',
        background_color: '#000000',
        display: 'standalone',
        orientation: 'portrait',
        start_url: '/',
        icons: [
          { src: '/icons/icon-72.png',   sizes: '72x72',   type: 'image/png' },
          { src: '/icons/icon-96.png',   sizes: '96x96',   type: 'image/png' },
          { src: '/icons/icon-128.png',  sizes: '128x128', type: 'image/png' },
          { src: '/icons/icon-144.png',  sizes: '144x144', type: 'image/png' },
          { src: '/icons/icon-152.png',  sizes: '152x152', type: 'image/png' },
          { src: '/icons/icon-192.png',  sizes: '192x192', type: 'image/png', purpose: 'any maskable' },
          { src: '/icons/icon-384.png',  sizes: '384x384', type: 'image/png' },
          { src: '/icons/icon-512.png',  sizes: '512x512', type: 'image/png', purpose: 'any maskable' },
        ],
      },
      workbox: {
        // Cachear assets estáticos generados por Vite
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        // Rutas a excluir del caché de Workbox
        navigateFallbackDenylist: [/^\/api/, /^\/sanctum/],
        runtimeCaching: [
          {
            // Caché de respuestas del servidor (Inertia pages)
            urlPattern: /^https?:\/\/.*\/(?!api)/,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'inertia-pages',
              expiration: { maxEntries: 50, maxAgeSeconds: 24 * 60 * 60 },
            },
          },
        ],
      },
    }),
  ],
  resolve: {
    alias: {
      // Alias @ apunta a resources/js para imports más limpios
      '@': path.resolve(__dirname, './resources/js'),
    },
  },
})
