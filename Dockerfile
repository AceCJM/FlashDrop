# Dockerfile for FlashDrop
# Uses the official PHP + Apache image to serve the simple PHP app

FROM php:8.2-apache

# Install recommended PHP extensions (fileinfo is bundled, but ensure common tools available)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# Set working directory to Apache web root
WORKDIR /var/www/html

# Copy source files into the container
# We copy the repo root into /var/www/html so that `src/` files are placed at the web root
# If your app expects files under `src/`, we copy the `src/` contents into the document root.
COPY src/ /var/www/html/

# Create uploads directory and set permissions
RUN mkdir -p /var/www/uploads \
    && chown -R www-data:www-data /var/www/uploads \
    && chmod 755 /var/www/uploads

# Expose default HTTP port
EXPOSE 80

# Ensure Apache runs in foreground
CMD ["apache2-foreground"]

