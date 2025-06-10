# Use PHP 8.3 (latest stable) with CLI support as the base image
FROM php:8.3-cli

# Install required dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    autoconf \
    build-essential \
    ca-certificates \
    libxml2-dev \
    libssl-dev \
    sqlite3 \
    wget \
    libcurl4-openssl-dev \
    libsqlite3-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Download and extract PHP source code
RUN docker-php-source extract

# Build PHP with Zend Thread Safety (ZTS) enabled (parallel requires ZTS)
RUN cd /usr/src/php \
    && ./buildconf --force \
    && ./configure --enable-zts --with-curl \
    && make -j"$(nproc)" \
    && make install \
    && docker-php-source delete

# Install PHP extensions
RUN docker-php-ext-install \
    zip \
    pdo_mysql \
    curl

# Verify cURL is installed
RUN php -m | grep curl || { echo "cURL extension not loaded!"; exit 1; }

# Install parallel extension manually from GitHub
RUN git clone https://github.com/krakjoe/parallel.git \
    && cd parallel \
    && phpize \
    && ./configure --enable-parallel \
    && make \
    && make test \
    && make install

# Enable parallel extension in php.ini
RUN echo "extension=parallel.so" > /usr/local/lib/php.ini

# Verify parallel extension is installed
RUN php -m | grep parallel || { echo "Parallel extension not loaded!"; exit 1; }

# Set working directory
WORKDIR /var/www/html

# Set proper permissions for mounted volumes
RUN chown -R www-data:www-data /var/www/html

# Expose port 9000 (if needed)
EXPOSE 9000

# Run PHP CLI server
CMD ["php", "-S", "0.0.0.0:9000"]
