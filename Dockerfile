# syntax=docker/dockerfile:1

###############################################################################
# Laravel CMS Blog — self-contained runtime image.
#
#   Stage 1 "assets"  — compiles the Tailwind CSS 4 / Vite 8 bundle (Node 22)
#   Stage 2 "runtime" — PHP 8.4 FPM + nginx + Composer + app + built assets
#
# The application is served by nginx on port 80 (which redirects to HTTPS)
# and port 443 (TLS), proxying PHP requests to php-fpm on 127.0.0.1:9000.
# A self-signed certificate is generated on first boot by the entrypoint.
#
# Build: docker build -t laravel-cms-blog .
# Run:   docker compose up --build
###############################################################################

# ---------------------------------------------------------------------------
# Stage 1 — front-end assets
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /build

# Dependencies first so this (slow) layer is cached independently.
COPY package.json package-lock.json ./
RUN npm ci

# Sources required by vite.config.js, plus its default output directory.
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# ---------------------------------------------------------------------------
# Stage 2 — PHP application runtime (nginx + php-fpm)
# ---------------------------------------------------------------------------
FROM php:8.4-fpm AS runtime

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_HOME=/tmp/composer

# System packages + the PHP extensions this project requires
# (pdo_mysql, mbstring, xml, curl, gd) and opcache for production.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
        zip \
        nginx \
        openssl \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        xml \
        curl \
        gd \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 1. PHP dependencies (cached independently from the application code).
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --optimize-autoloader

# 2. Application code.
COPY . .

# 3. Compiled front-end assets from the Node stage.
COPY --from=assets /build/public/build ./public/build

# 4. Writable framework directories, then autoloader + package discovery.
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && composer dump-autoload --no-dev --optimize --classmap-authoritative

# 5. nginx server blocks: redirect HTTP to HTTPS, serve public/ over TLS and
#    forward PHP to php-fpm. The certificate is generated at container start.
RUN mkdir -p /etc/nginx/certs \
    && cat > /etc/nginx/sites-available/default <<'NGINX'
# HTTP — redirect everything to HTTPS.
server {
    listen 80;
    listen [::]:80;
    server_name _;

    return 301 https://$host$request_uri;
}

# HTTPS — the actual application.
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name _;

    ssl_certificate     /etc/nginx/certs/self-signed.crt;
    ssl_certificate_key /etc/nginx/certs/self-signed.key;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;
    ssl_session_cache   shared:SSL:10m;
    ssl_session_timeout 10m;

    root /var/www/html/public;
    index index.php;

    charset utf-8;
    client_max_body_size 32m;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        # Tell PHP the request arrived over TLS so Laravel builds https:// URLs.
        fastcgi_param HTTPS on;
        fastcgi_param SERVER_PORT 443;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

# php-fpm: run as www-data but keep the listening socket on localhost.
RUN sed -i \
        -e 's/^user = .*/user = www-data/' \
        -e 's/^group = .*/group = www-data/' \
        /usr/local/etc/php-fpm.d/www.conf

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 80 443

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsSk https://127.0.0.1/up || exit 1

ENTRYPOINT ["entrypoint"]
# Start php-fpm in the background, then keep nginx in the foreground as PID 1.
CMD ["sh", "-c", "php-fpm -D && exec nginx -g 'daemon off;'"]