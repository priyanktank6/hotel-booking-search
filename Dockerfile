FROM php:8.1-apache

# Install system dependencies including git, zip, unzip
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    postgresql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql pgsql zip \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod headers

# Create necessary directories
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Create .env file from example
RUN cp .env.example .env || true

# Generate app key
RUN php artisan key:generate || true

# Create storage link
RUN php artisan storage:link || true

# Wait for database and run migrations
RUN echo "Waiting for database..." && sleep 10 && \
    php artisan migrate --force || echo "Migration failed but continuing" && \
    php artisan db:seed --force || echo "Seeding failed but continuing"

# Configure Apache to serve from public directory
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Disable SSL requirement for PostgreSQL
RUN echo "sslmode=disable" >> /usr/local/etc/php/conf.d/pgsql.ini

# Run migrations and seeders during build
RUN php artisan migrate --force || true
RUN php artisan db:seed --force || true

EXPOSE 80

# Run setup script
RUN php setup-database.php || true

CMD ["apache2-foreground"]