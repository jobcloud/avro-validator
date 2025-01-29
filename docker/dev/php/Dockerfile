FROM php:8.3-cli-alpine3.18

ARG HOST_USER_ID

# PHP: Copy configuration files & remove dist files
RUN mkdir /phpIni
COPY dev/php/files/bin/ /usr/local/bin/

# SYS: Install required packages
RUN apk --no-cache upgrade && \
    apk --no-cache add bash git sudo openssh autoconf gcc g++ make shadow

# we need support for users with ID higher than 65k, so instead of using this:
#RUN adduser -u $HOST_USER_ID -D -H $HOST_USER
# we do it manually
RUN if [ -n "$HOST_USER_ID" ] && [ "$HOST_USER_ID" -lt 60001 ]; then \
        usermod -u ${HOST_USER_ID} -o www-data; \
    fi

# COMPOSER: install binary and prestissimo
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# PHP: Install php extensions
RUN pecl channel-update pecl.php.net && \
    pecl install pcov && \
    php-ext-enable pcov

USER www-data

WORKDIR /var/www/html
