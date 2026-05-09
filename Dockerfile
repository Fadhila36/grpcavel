# Base image with PHP 8.3 and RoadRunner dependencies
FROM php:8.3-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    unzip \
    libzip-dev \
    libprotobuf \
    protobuf \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install zip pcntl pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Install RoadRunner binary
RUN ./vendor/bin/rr get-binary

# Expose gRPC port
EXPOSE 9001

# Start the gRPC server
CMD ["php", "artisan", "grpc:start"]
