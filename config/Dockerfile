# ══════════════════════════════════════════════════════════════════════════════
# Tribe Core
# Base: php:8.4-fpm (Debian bookworm)
# ══════════════════════════════════════════════════════════════════════════════
FROM php:8.4-fpm

# ─── System dependencies ──────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    # ── utilities ──
    vim \
    zip \
    unzip \
    p7zip-full \
    curl \
    git \
    s3cmd \
    net-tools \
    nginx \
    supervisor \
    # ── indexer tools ──
    inotify-tools \
    poppler-utils \
    libimage-exiftool-perl \
    ffmpeg \
    pdftk \
    # ── imaging ──
    imagemagick \
    libmagickwand-dev \
    # ── PHP build deps ──
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ─── PHP extensions ───────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) gd

# All remaining extensions in a single layer
RUN docker-php-ext-install \
    pdo_mysql \
    mysqli \
    exif \
    pcntl \
    bcmath \
    zip \
    opcache

# ImageMagick PHP extension via PECL
RUN pecl install imagick \
 && docker-php-ext-enable imagick

# ─── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ─── App setup ────────────────────────────────────────────────────────────────
WORKDIR /var/www/html

# Custom PHP configuration
COPY ./php.ini /usr/local/etc/php/php.ini

# www user for php-fpm processes
RUN groupadd -g 1000 www \
 && useradd -u 1000 -ms /bin/bash -g www www \
 && chown -R www:www /var/www/html

# ─── Indexer Composer dependencies ───────────────────────────────────────────
RUN composer init --no-interaction --name="tribe/app" --stability=stable \
 && composer require \
      typesense/typesense-php \
      php-http/curl-client \
      --no-interaction --no-progress

# ─── supervisord ──────────────────────────────────────────────────────────────
RUN mkdir -p /var/log/supervisor

EXPOSE 9000

# supervisord runs as root so it can manage both the www-user php-fpm
# worker and the root-level inotify watcher process.
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/tribe.conf"]