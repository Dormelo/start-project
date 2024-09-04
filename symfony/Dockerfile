# Versions
ARG NODE_VERSION=20
ARG PHP_VERSION=8.2

# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target
FROM node:${NODE_VERSION}-alpine AS node_upstream

# Base stage for dev
FROM node_upstream AS node_base

# Check https://github.com/nodejs/docker-node/tree/b4117f9333da4138b03a546ec926ef50a31506c3#nodealpine to understand why libc6-compat might be needed.
# hadolint ignore=DL3018
RUN apk add --no-cache libc6-compat

WORKDIR /srv/app

# Development image
FROM node_base AS encore_dev

CMD ["sh", "-c", "npm install; npm run watch"]

FROM node_base AS encore_builder

COPY --link package-lock.yaml ./
COPY --link . .

RUN	npm install --omit-dev && \
	npm run build

FROM php:${PHP_VERSION}-apache AS php_upstream

# Base PHP image
FROM php_upstream AS php_base

WORKDIR /app

# persistent / runtime deps
# hadolint ignore=DL3008
RUN apt-get update && apt-get install -y --no-install-recommends \
	acl \
	file \
	gettext \
	git \
	&& rm -rf /var/lib/apt/lists/*

# php extensions installer: https://github.com/mlocati/docker-php-extension-installer
COPY --from=mlocati/php-extension-installer --link /usr/bin/install-php-extensions /usr/local/bin/

RUN set -eux; \
	install-php-extensions \
		@composer \
		apcu \
		intl \
		opcache \
		zip \
		gd \
	;

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

###> recipes ###
###> doctrine/doctrine-bundle ###
RUN set -eux; \
	install-php-extensions pdo_pgsql
###< doctrine/doctrine-bundle ###

###> symfony/amqp ###
RUN set -eux; \
	install-php-extensions amqp
###< symfony/amqp ###
###< recipes ###

COPY --link .docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link --chmod=755 .docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link .docker/apache2/000-default.conf /etc/apache2/sites-available/000-default.conf

ENTRYPOINT ["docker-entrypoint"]

CMD ["apache2-foreground"]

# Dev PHP image
FROM php_base AS php_dev

ENV APP_ENV=dev XDEBUG_MODE=off
VOLUME /app/var/

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN set -eux; \
	install-php-extensions \
		xdebug \
	;

COPY --link .docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

CMD ["apache2-foreground"]

# Prod PHP image
FROM php_base AS php_prod

ENV APP_ENV=prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link .docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/

# prevent the reinstallation of vendors at every changes in the source code
COPY --link composer.* symfony.* ./
RUN set -eux; \
	composer install --no-cache --prefer-dist --no-autoloader --no-scripts --no-progress

# copy sources
COPY --link . ./
RUN rm -Rf .docker/

RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer dump-autoload --classmap-authoritative; \
	composer dump-env prod; \
	composer run-script post-install-cmd; \
	chmod +x bin/console; sync;