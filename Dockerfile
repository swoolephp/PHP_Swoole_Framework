# Sử dụng hình ảnh PHP chính thức với PHP 8.1
FROM php:8.1-cli

# Cài đặt các gói cần thiết
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libssl-dev \
    pkg-config \
    libevent-dev \
    libz-dev \
    libpq-dev \
    libsqlite3-dev \
    libzip-dev \
    libbrotli-dev \
    libc-ares-dev \
    libcurl4-openssl-dev \
    unzip \
    autoconf \
    build-essential \
    wget \
    && docker-php-ext-install pdo_mysql pdo_pgsql mysqli zip sockets

# Tải xuống và cài đặt Swoole từ tệp tarball
RUN cd /tmp && \
    wget https://github.com/swoole/swoole-src/archive/refs/tags/v5.1.3.tar.gz && \
    tar -xzvf v5.1.3.tar.gz && \
    cd swoole-src-5.1.3 && \
    phpize && \
    ./configure --enable-sockets=yes --enable-openssl=yes --enable-mysqlnd=yes --enable-swoole-curl=yes --enable-cares=yes --enable-brotli=yes && \
    make && make install && \
    docker-php-ext-enable swoole && \
    echo "extension=swoole.so" > /usr/local/etc/php/conf.d/docker-php-ext-swoole.ini && \
    echo "swoole.use_shortname = 'On'" >> /usr/local/etc/php/conf.d/docker-php-ext-swoole.ini


# Cài đặt Redis
RUN pecl install redis && docker-php-ext-enable redis

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Đặt thư mục làm việc
WORKDIR /www/server/swoole

# Copy mã nguồn vào container
#COPY ./src/ /www/server/swoole

# Chạy server.php khi container khởi động
CMD ["php", "./src/server.php"]
