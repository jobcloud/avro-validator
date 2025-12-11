FROM public.ecr.aws/docker/library/php:8.4-cli-alpine3.22

# PIE: Install PIE binary
COPY --from=ghcr.io/php/pie:1.2.1-bin /pie /usr/bin/pie

ARG HOST_USER_ID

# COMPOSER: install binary
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# PHP: Copy configuration files & remove dist files
COPY files/bin/ /usr/local/bin/

# SYS: add ssh key (for GitHub)
COPY --chown=www-data:www-data files/.ssh /home/www-data/.ssh

RUN chown -R www-data:www-data /home/www-data && \
    chmod 400 /home/www-data/.ssh/config

# SYS: Install required packages
RUN apk --no-cache upgrade && \
    apk --no-cache add bash git sudo openssh autoconf gcc g++ make shadow

# we need support for users with ID higher than 65k, so instead of using this:
#RUN adduser -u $HOST_USER_ID -D -H $HOST_USER
# we do it manually
RUN if [ -n "$HOST_USER_ID" ] && [ "$HOST_USER_ID" -lt 60001 ]; then \
        usermod -u ${HOST_USER_ID} -o www-data; \
    fi

# PHP: Install php extensions
RUN pie install pecl/pcov

# SYS: add ssh config (for GitHub)
COPY files/.ssh/config /root/.ssh/config
COPY files/.ssh/config /home/www-data/.ssh/config
RUN ln -s /run/secrets/ssh_host_key /root/.ssh/id_rsa && \
    ln -s /run/secrets/ssh_host_key /home/www-data/.ssh/id_rsa && \
    chown -R www-data:www-data /home/www-data && \
    chown root:root /root/.ssh/config && \
    chmod 700 /home/www-data/.ssh && \
    chmod 400 /home/www-data/.ssh/config /root/.ssh/config \

USER www-data

WORKDIR /var/www/html
