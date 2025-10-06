FROM php:8.2-cli-alpine

# Set memory limit
RUN echo 'memory_limit = 256M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini

# Install PHP extensions
COPY --from=mlocati/php-extension-installer:1.2.25 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pcov

# Install composer
COPY --from=composer:2.5.1 /usr/bin/composer /usr/local/bin/

# Create user with UID:GID 1000:1000
RUN addgroup -g 1000 appuser && \
    adduser -D -u 1000 -G appuser appuser

# Create app directory and set ownership
RUN mkdir -p /app && chown -R appuser:appuser /app

# Copy application files as root, then fix ownership
COPY ../ /app
RUN chown -R appuser:appuser /app

# Switch to non-root user
USER appuser

WORKDIR /app