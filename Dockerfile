# Render-ready Dockerfile for Laravel 12 (PHP 8.2 + Apache)
FROM php:8.2-apache

# Install system packages and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libsqlite3-dev \
    sqlite3 \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite zip mbstring exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite
RUN a2enmod rewrite

# Make Apache use port 10000 (Render default)
RUN sed -i 's/Listen 80/Listen 10000/g' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/g' /etc/apache2/sites-available/000-default.conf

# Set Laravel public as document root
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/apache2.conf

# Allow .htaccess for Laravel
RUN printf '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Install Node.js 20 (for Vite build)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy Laravel app (respects .dockerignore)
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install frontend dependencies and build assets
RUN npm install && npm run build

# Clear cached config/routes/views baked at build time
# (they will be re-cached at runtime so Render env vars take effect)
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

# Create storage symlink (re-run at runtime too, hence || true)
RUN php artisan storage:link || true

# Fix permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions \
    storage/framework/views bootstrap/cache public/uploads \
    && chown -R www-data:www-data storage bootstrap/cache public/uploads \
    && chmod -R 775 storage bootstrap/cache public/uploads

# Expose Render port
EXPOSE 10000

# Start: run migrations + cache config, then launch Apache.
# Migrations MUST run at runtime (not build time) because the
# database URL is only available when the container starts on Render.
CMD ["sh", "-c", "php artisan storage:link || true; php artisan migrate --force || true; php artisan config:cache; php artisan route:cache; php artisan view:cache; apache2-foreground"]
