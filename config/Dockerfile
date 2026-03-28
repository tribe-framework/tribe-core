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
    python3-venv \
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

# ─── PaddleOCR (Python, CPU-only) ─────────────────────────────────────────────
# Install in a venv to avoid breaking system packages.
# Pre-download models for the most common Indian languages on build
# so first-run transcription doesn't stall on model downloads.
RUN python3 -m venv /opt/paddleocr-venv \
 && /opt/paddleocr-venv/bin/pip install --no-cache-dir --upgrade pip \
 && /opt/paddleocr-venv/bin/pip install --no-cache-dir \
      paddlepaddle \
      paddleocr \
 && ln -sf /opt/paddleocr-venv/bin/python3 /usr/local/bin/python3-paddle

# PaddleOCR wrapper script
COPY ./paddle_ocr.py /usr/local/bin/paddle_ocr.py
RUN chmod +x /usr/local/bin/paddle_ocr.py \
 && sed -i '1s|.*|#!/opt/paddleocr-venv/bin/python3|' /usr/local/bin/paddle_ocr.py

# Pre-download OCR models for key Indian languages (runs detection once per lang
# so the model weights are cached inside the image at /opt/paddleocr_models).
# Uses a tiny 1x1 white PNG as input — we only care about triggering the download.
RUN mkdir -p /opt/paddleocr_models \
 && export PADDLEOCR_HOME=/opt/paddleocr_models \
 && /opt/paddleocr-venv/bin/python3 -c " \
from PIL import Image; \
img = Image.new('RGB', (100, 30), 'white'); \
img.save('/tmp/_probe.png'); \
" \
 && for lang in en hi ta te kn bn gu pa ur mr ne as ar; do \
      echo \"--- Pre-downloading PaddleOCR model: \$lang ---\"; \
      /opt/paddleocr-venv/bin/python3 -c " \
import os; os.environ['PADDLEOCR_HOME']='/opt/paddleocr_models'; \
from paddleocr import PaddleOCR; \
ocr = PaddleOCR(lang='${lang}', use_angle_cls=True, show_log=False, use_gpu=False); \
ocr.ocr('/tmp/_probe.png', cls=True); \
" 2>/dev/null || true; \
    done \
 && rm -f /tmp/_probe.png

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
