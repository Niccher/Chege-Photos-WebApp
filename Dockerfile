FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    cron \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) intl mysqli pdo_mysql zip gd exif

# Enable Apache rewrite module
RUN a2enmod rewrite headers

# PHP upload limits – 512 MB max (upload_max_filesize & post_max_size are PHP_INI_PERDIR,
# so they MUST be set here in the PHP config, NOT at runtime with ini_set())
RUN echo "upload_max_filesize = 512M" > /usr/local/etc/php/conf.d/upload-limits.ini \
    && echo "post_max_size = 512M" >> /usr/local/etc/php/conf.d/upload-limits.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/upload-limits.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/upload-limits.ini \
    && echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/upload-limits.ini

# Update Apache configuration for DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Set permissions for writable directory (create it if not exists)
RUN mkdir -p /var/www/html/writable \
    && chown -R www-data:www-data /var/www/html/writable \
    && chmod -R 775 /var/www/html/writable


# Copy and set entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
EXPOSE 80
