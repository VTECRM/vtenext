FROM php:7.4-apache-bullseye

LABEL description="VTENext CE - Dockerized for local development and testing"
LABEL source="https://github.com/VTECRM/vtenext"

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libc-client-dev \
    libkrb5-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by VTENext
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j$(nproc) \
        curl \
        imap \
        xml \
        gd \
        bcmath \
        mbstring \
        zip \
        pdo \
        pdo_mysql \
        mysqli \
        intl \
        opcache \
        sockets

# Suggested: APCu for cache
RUN pecl install apcu && docker-php-ext-enable apcu

# Apache mod_rewrite
RUN a2enmod rewrite

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Clone VTENext from GitHub
ARG VTENEXT_BRANCH=main
RUN cd / \
    && rm -rf /var/www/html \
    && git clone --depth=1 --branch=${VTENEXT_BRANCH} \
    https://github.com/VTECRM/vtenext.git /var/www/html \
    && rm -rf /var/www/html/.git

# Install Composer dependencies
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Fix: check_db_utf8_support() called as global but defined inside Installation_Utils class
# https://github.com/VTECRM/vtenext/issues
RUN php -r "\
\$f = '/var/www/html/include/install/resources/utils.php'; \
\$c = file_get_contents(\$f); \
\$c = str_replace('check_db_utf8_support(\$conn)', 'self::check_db_utf8_support(\$conn)', \$c); \
\$c = str_replace('function self::check_db_utf8_support', 'function check_db_utf8_support', \$c); \
file_put_contents(\$f, \$c); \
echo 'Patched utils.php' . PHP_EOL; \
"

# PHP configuration
COPY php.ini /usr/local/etc/php/conf.d/vtenext.ini

# Apache VirtualHost
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# Writable directories required by VTENext
RUN chmod -R 775 \
    /var/www/html/cache \
    /var/www/html/logs \
    /var/www/html/upload \
    /var/www/html/storage \
    2>/dev/null || true

EXPOSE 80

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
