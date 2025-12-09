FROM public.ecr.aws/docker/library/php:8.4-cli-alpine3.22

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
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY --from=ghcr.io/php/pie:1.2.1-bin /pie /usr/bin/pie

# PHP: Install php extensions
RUN pie install pecl/pcov

# Add GitHub's SSH host key to known_hosts (for www-data user)
RUN mkdir -p /var/www/.ssh && chmod 700 /var/www/.ssh \
    && ssh-keyscan -t ed25519 github.com >> /var/www/.ssh/known_hosts \
    && chmod 644 /var/www/.ssh/known_hosts \
    && chown -R www-data:www-data /var/www/.ssh

USER www-data

WORKDIR /var/www/html
