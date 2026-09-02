# syntax=docker/dockerfile:1
FROM php:8.3-apache

# ── System dependencies ────────────────────────────────────────────────────────
RUN --mount=type=cache,id=webapp-apt-cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,id=webapp-apt-lib,target=/var/lib/apt,sharing=locked \
    apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        zip \
        unzip \
        cron \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) intl mysqli pdo_mysql zip gd exif

# Enable Apache modules
RUN a2enmod rewrite headers

# PHP upload & memory limits (PHP_INI_PERDIR — must be set in an ini file, not at runtime)
RUN { \
        echo "upload_max_filesize = 512M"; \
        echo "post_max_size = 512M"; \
        echo "memory_limit = 512M"; \
        echo "max_execution_time = 300"; \
        echo "max_input_time = 300"; \
    } > /usr/local/etc/php/conf.d/upload-limits.ini

# Move DocumentRoot to public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# ── Composer dependency layer ─────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN --mount=type=cache,id=webapp-composer-cache,target=/root/.composer/cache \
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --ignore-platform-reqs \
    && rm /usr/bin/composer

# ── Application code ──────────────────────────────────────────────────────────
COPY . /var/www/html

RUN mkdir -p /var/www/html/writable \
    && chown -R www-data:www-data /var/www/html/writable \
    && chmod -R 775 /var/www/html/writable

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
EXPOSE 80
