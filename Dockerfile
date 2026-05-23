# ============================================================
# Imagen de producción para Tu Mejor Versión en Railway.app
# Stack: PHP 8.2 + Node 20 + Laravel 11 + Nginx
# ============================================================

# ── Etapa 1: Dependencias de Node (build del frontend Vue 3) ──
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copiar archivos de dependencias primero (caché de Docker)
COPY package.json package-lock.json ./
RUN npm ci --prefer-offline

# Copiar el resto del código fuente
COPY . .

# Construir los assets de producción (Vite + PWA)
RUN npm run build

# ── Etapa 2: Dependencias de PHP + Composer ──
FROM composer:2.7 AS composer-builder

WORKDIR /app
COPY composer.json composer.lock ./

# Instalar dependencias PHP sin scripts de desarrollo
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction \
    --prefer-dist

# ── Etapa 3: Imagen final de producción ──
FROM php:8.2-fpm-alpine

# Instalar extensiones PHP necesarias para Laravel 11
RUN apk add --no-cache \
    nginx \
    supervisor \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    && docker-php-ext-install \
       pdo_mysql \
       mbstring \
       gd \
       zip \
       intl \
       opcache \
    # Instalar extensión Redis para PHP
    && pecl install redis \
    && docker-php-ext-enable redis opcache

# Configuración de OPcache para producción
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Copiar vendor de PHP desde etapa composer
COPY --from=composer-builder /app/vendor ./vendor

# Copiar el código de la aplicación Laravel
COPY . .

# Copiar los assets compilados del frontend
COPY --from=frontend-builder /app/public/build ./public/build
COPY --from=frontend-builder /app/public/icons ./public/icons
COPY --from=frontend-builder /app/public/logo-full.png ./public/logo-full.png
COPY --from=frontend-builder /app/public/logo-horizontal.png ./public/logo-horizontal.png
COPY --from=frontend-builder /app/public/logo-icon.png ./public/logo-icon.png
COPY --from=frontend-builder /app/public/favicon.ico ./public/favicon.ico

# Configurar permisos de directorios de escritura de Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configuración de Nginx para Laravel + Inertia SPA
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Configuración de Supervisor para gestionar php-fpm + nginx + worker
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Script de inicio que ejecuta migraciones y arranca los servicios
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
