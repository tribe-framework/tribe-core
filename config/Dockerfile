# ══════════════════════════════════════════════════════════════════════════════
# Tribe Core
# Base: php:8.4-fpm (Debian bookworm)
# Runs php-fpm AND the Typesense inotify watcher under supervisord.
# ══════════════════════════════════════════════════════════════════════════════
FROM php:8.4-fpm

# ─── System dependencies ──────────────────────────────────────────────────────
# Combines packages from both Dockerfiles.
# inotify-tools  → kernel-level file watching for the indexer watcher
# poppler-utils  → pdftotext + pdfinfo (PDF text extraction)
# exiftool       → image/video metadata extraction
# libimage-exiftool-perl is the Debian package name for exiftool
# supervisor     → process manager: runs php-fpm + watcher side-by-side
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
    python3-pip \
    tesseract-ocr \
    tesseract-ocr-eng \
    tesseract-ocr-hin \
    ghostscript \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ─── PHP extensions ───────────────────────────────────────────────────────────
# gd must be configured before install (needs freetype/jpeg/webp flags)
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
# Set the web root as the working directory so that vendor/, _init.php, and
# the indexer scripts (index_files.php, index_db.php, watcher.sh) all share
# the same base path. This is required for _init.php and vendor/autoload.php
# to be resolvable from the indexer scripts at runtime.
WORKDIR /var/www/html

# Custom PHP configuration
COPY ./php.ini /usr/local/etc/php/php.ini

# www user for php-fpm processes
RUN groupadd -g 1000 www \
 && useradd -u 1000 -ms /bin/bash -g www www \
 && chown -R www:www /var/www/html

# ─── Indexer Composer dependencies ───────────────────────────────────────────
# Installed into /var/www/html so vendor/autoload.php is at the same path
# that index_files.php and index_db.php expect: /var/www/html/vendor/autoload.php
# The Tribe app's own composer install (run at container start via docker-compose)
# will merge its dependencies into this same vendor/ directory via upsert.
# The scripts themselves (index_files.php, index_db.php, watcher.sh) are mounted
# at runtime via docker-compose volumes so they can be edited without rebuilding.
RUN composer init --no-interaction --name="tribe/app" --stability=stable \
 && composer require \
      typesense/typesense-php \
      php-http/curl-client \
      --no-interaction --no-progress

# ─── supervisord ──────────────────────────────────────────────────────────────
# supervisord config is mounted at runtime via docker-compose
# (./config/tribe/supervisord.conf → /etc/supervisor/conf.d/tribe.conf)
# so you can tune log paths and restart policies without rebuilding.
# We create the log dir here so supervisord can write to it immediately.
RUN mkdir -p /var/log/supervisor

EXPOSE 9000

# supervisord runs as root so it can manage both the www-user php-fpm
# worker and the root-level inotify watcher process.
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/tribe.conf"]