# --- Stage 1: Build Assets ---
FROM node:20-alpine AS assets-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# --- Stage 2: PHP Application ---
FROM serversideup/php:8.2-fpm-nginx

# Copy application files
COPY --chown=www-data:www-data . /var/www/html

# Copy built assets from Stage 1
COPY --chown=www-data:www-data --from=assets-builder /app/public/build /var/www/html/public/build

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev
