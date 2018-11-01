FROM    php:7.2.11-fpm-alpine as baseDependencies

FROM    baseDependencies as dev

# Install dev binaries
RUN     apk --no-cache add \
			git \
			unzip \
			vim \
			wget \
			zip

# Copy composer from official composer image
COPY    --from=composer:latest /usr/bin/composer /usr/bin/composer
