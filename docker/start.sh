#!/bin/sh
# Script de inicio del contenedor para Railway
# Ejecuta las preparaciones de Laravel y arranca todos los servicios

set -e

echo "=== Iniciando Tu Mejor Versión ==="

# Cambiar al directorio de la aplicación
cd /var/www/html

# Generar clave de aplicación si no existe
if [ -z "$APP_KEY" ]; then
    echo "⚠ APP_KEY no configurada. Generando clave temporal..."
    php artisan key:generate --force
fi

# Ejecutar migraciones en producción (con --force para entornos no-interactivos)
echo "→ Ejecutando migraciones..."
php artisan migrate --force

# Poblar ejercicios si la tabla está vacía
echo "→ Verificando seeder de ejercicios..."
php artisan db:seed --class=ExerciseSeeder --force 2>/dev/null || true

# Limpiar y reconstruir caché de configuración y rutas
echo "→ Optimizando Laravel para producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Crear enlace simbólico de storage (para archivos subidos por usuarios)
php artisan storage:link 2>/dev/null || true

echo "✅ Preparación completada. Iniciando servicios..."

# Iniciar Supervisor (gestiona php-fpm, nginx y queue worker)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
