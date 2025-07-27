FROM php:8.3-fpm-alpine

# Install build dependencies
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    autoconf \
    gcc \
    g++ \
    make \
    pthread-dev

# Copy extension source
COPY ext/phreakscope /tmp/phreakscope

# Build and install phreakscope extension
WORKDIR /tmp/phreakscope
RUN phpize && \
    ./configure && \
    make -j$(nproc) && \
    make install && \
    echo "extension=phreakscope.so" > /usr/local/etc/php/conf.d/phreakscope.ini

# Copy PHP library
COPY src /app/src

# Set configuration
ENV PHREAKSCOPE_SOCKET=/var/run/phreakscope.sock
ENV PHREAKSCOPE_LABELS="service=demo,instance={HOSTNAME}"

# Include autoloader in php.ini
RUN echo "auto_prepend_file=/app/src/Autoload.php" >> /usr/local/etc/php/conf.d/phreakscope.ini

# Clean up build dependencies
RUN apk del $PHPIZE_DEPS autoconf gcc g++ make && \
    rm -rf /tmp/phreakscope

WORKDIR /var/www/html