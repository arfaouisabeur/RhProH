FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    gd \
    zip \
    intl \
    opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./

RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-dev

# Create PostgreSQL schema from entities (skip MySQL migrations)
RUN php bin/console doctrine:schema:drop --force --full-database || true
RUN php bin/console doctrine:schema:create || true

# Create admin user automatically
RUN php bin/console app:create-admin --no-interaction || true

EXPOSE 10000

CMD php -S 0.0.0.0:10000 -t public
