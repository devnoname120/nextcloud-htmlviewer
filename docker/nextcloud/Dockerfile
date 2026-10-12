FROM nextcloud:35-fpm

# Composer installs the dev dependencies (PHPUnit, nextcloud/ocp) for the unit tests
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

ENV COMPOSER_HOME=/tmp/composer
