# syntax=docker/dockerfile:1
FROM ubuntu:22.04

RUN apt-get update && apt-get -y upgrade && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    sudo \
    ssh \
    git \
    nano \
    less \
    net-tools \
    inetutils-ping \
    iproute2 \
    telnet \
    apache2 \
    curl \
    ca-certificates \
    gnupg \
    postgresql-client \
    php \
    php-fpm \
    php-pgsql \
    php-xml \
    php-xdebug \
    php-curl \
    composer

ENV PGHOST database-server
ENV PGUSER admin
ENV PGPASSWORD admin

# Apache configuration
RUN a2enmod rewrite
RUN a2enmod actions

COPY ./docker/apache.conf /etc/apache2/sites-enabled/000-default.conf
COPY ./docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD apachectl -D FOREGROUND

# Used for debugging purposes only to keep the container up and running
#CMD tail -f /dev/null