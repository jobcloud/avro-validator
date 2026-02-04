FROM public.ecr.aws/docker/library/php:8.4-cli-alpine3.23

# PIE: Install PIE binary
COPY --from=ghcr.io/php/pie:1.3.1-bin /pie /usr/bin/pie

# COMPOSER: install binary
COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

ARG HOST_USER_ID

# SYS: Install required packages
RUN apk --no-cache upgrade && \
    apk --no-cache add bash git sudo autoconf gcc g++ make shadow

# PHP: Install php extensions
RUN pie install pecl/pcov

# Set host user id if given, to avoid permission conflicts in dev
RUN /bin/bash -c 'if [ -n "$HOST_USER_ID" ] && [ "$HOST_USER_ID" -lt 60000 ]; then \
        usermod -u ${HOST_USER_ID} www-data; \
    fi'

RUN usermod -s /bin/bash www-data && \
    echo 'www-data ALL=(ALL) NOPASSWD: ALL' > '/etc/sudoers.d/www-data'


USER www-data

WORKDIR /var/www/html
