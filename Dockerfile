ARG ALPINE_TAG="3.8"
ARG PHP_TAG="7.2-cli-alpine3.8"

FROM php:$PHP_TAG as ext-builder
RUN docker-php-source extract && \
    apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS

FROM ext-builder as ext-inotify
RUN pecl install inotify && \
    docker-php-ext-enable inotify

FROM ext-builder as ext-pcntl
RUN docker-php-ext-install pcntl

FROM ext-builder as ext-xdebug
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

FROM ext-builder as ext-swoole
ARG SWOOLE_VERSION=4.2.13
RUN pecl install swoole-$SWOOLE_VERSION && \
    docker-php-ext-enable swoole

FROM composer:latest as app-installer
WORKDIR /usr/src/app
RUN composer global require "hirak/prestissimo:^0.3" --prefer-dist --no-progress --no-suggest --classmap-authoritative --ansi
COPY composer.json composer.lock ./
RUN composer validate
ARG COMPOSER_ARGS=install
RUN composer "$COMPOSER_ARGS" --prefer-dist --ignore-platform-reqs --no-progress --no-suggest --no-scripts --no-autoloader --ansi
COPY . ./
RUN composer dump-autoload --classmap-authoritative --ansi

FROM php:$PHP_TAG as base
RUN apk add --no-cache libstdc++
WORKDIR /usr/src/app
COPY --from=ext-swoole /usr/local/lib/php/extensions/no-debug-non-zts-20170718/swoole.so /usr/local/lib/php/extensions/no-debug-non-zts-20170718/swoole.so
COPY --from=ext-swoole /usr/local/etc/php/conf.d/docker-php-ext-swoole.ini /usr/local/etc/php/conf.d/docker-php-ext-swoole.ini
COPY --from=ext-inotify /usr/local/lib/php/extensions/no-debug-non-zts-20170718/inotify.so /usr/local/lib/php/extensions/no-debug-non-zts-20170718/inotify.so
COPY --from=ext-inotify /usr/local/etc/php/conf.d/docker-php-ext-inotify.ini /usr/local/etc/php/conf.d/docker-php-ext-inotify.ini
COPY --from=ext-pcntl /usr/local/lib/php/extensions/no-debug-non-zts-20170718/pcntl.so /usr/local/lib/php/extensions/no-debug-non-zts-20170718/pcntl.so
COPY --from=ext-pcntl /usr/local/etc/php/conf.d/docker-php-ext-pcntl.ini /usr/local/etc/php/conf.d/docker-php-ext-pcntl.ini

FROM base as base-with-xdebug
COPY --from=ext-xdebug /usr/local/lib/php/extensions/no-debug-non-zts-20170718/xdebug.so /usr/local/lib/php/extensions/no-debug-non-zts-20170718/xdebug.so
COPY --from=ext-xdebug /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

FROM base as base-cli
COPY --from=app-installer /usr/src/app ./

FROM base as base-server
RUN apk add --no-cache bash
COPY --from=app-installer /usr/src/app ./

FROM base-with-xdebug as base-coverage
ENV COVERAGE=1
COPY --from=app-installer /usr/src/app ./

FROM base-with-xdebug as base-server-coverage
RUN apk add --no-cache bash
ENV COVERAGE=1
COPY --from=app-installer /usr/src/app ./

FROM base-cli as Cli
ENTRYPOINT ["./tests/Fixtures/Symfony/app/console"]
CMD ["swoole:server:run"]

FROM base-cli as Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=app-installer /usr/bin/composer /usr/local/bin/composer
ENTRYPOINT ["composer"]
CMD ["test"]

FROM base-coverage as ComposerCoverage
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=app-installer /usr/bin/composer /usr/local/bin/composer
ENTRYPOINT ["composer"]
CMD ["code-coverage"]

FROM base-server as Server
WORKDIR /usr/src/app/tests/Server
ENTRYPOINT ["/bin/bash"]
CMD ["../run-server-tests.sh"]

FROM base-server-coverage as ServerCoverage
WORKDIR /usr/src/app/tests/Server
ENV APP_ENV=cov \
    SWOOLE_ALLOW_XDEBUG=1
ENTRYPOINT ["/bin/bash"]
CMD ["../run-server-tests.sh"]
