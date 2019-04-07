ARG PHP_TAG="7.3-cli-alpine3.9"

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
ARG SWOOLE_VERSION="4.3.1"
RUN pecl install swoole-$SWOOLE_VERSION && \
    docker-php-ext-enable swoole

FROM composer:latest as app-installer
WORKDIR /usr/src/app
RUN composer global require "hirak/prestissimo:^0.3" --prefer-dist --no-progress --no-suggest --classmap-authoritative --ansi
COPY composer.json composer.lock ./
RUN composer validate
ARG COMPOSER_ARGS="install"
RUN composer ${COMPOSER_ARGS} --prefer-dist --ignore-platform-reqs --no-progress --no-suggest --no-scripts --no-autoloader --ansi
COPY . ./
RUN composer dump-autoload --classmap-authoritative --ansi

FROM php:$PHP_TAG as base
WORKDIR /usr/src/app
RUN addgroup -g 1000 -S runner && \
    adduser -u 1000 -S app -G runner && \
    chown app:runner /usr/src/app
RUN apk add --no-cache libstdc++
# php -i | grep 'PHP API' | sed -e 's/PHP API => //'
ARG PHP_API_VERSION="20180731"
COPY --from=ext-swoole /usr/local/lib/php/extensions/no-debug-non-zts-${PHP_API_VERSION}/swoole.so /usr/local/lib/php/extensions/no-debug-non-zts-${PHP_API_VERSION}/swoole.so
COPY --from=ext-swoole /usr/local/etc/php/conf.d/docker-php-ext-swoole.ini /usr/local/etc/php/conf.d/docker-php-ext-swoole.ini
COPY --from=ext-inotify /usr/local/lib/php/extensions/no-debug-non-zts-${PHP_API_VERSION}/inotify.so /usr/local/lib/php/extensions/no-debug-non-zts-${PHP_API_VERSION}/inotify.so
COPY --from=ext-inotify /usr/local/etc/php/conf.d/docker-php-ext-inotify.ini /usr/local/etc/php/conf.d/docker-php-ext-inotify.ini
COPY --from=ext-pcntl /usr/local/lib/php/extensions/no-debug-non-zts-${PHP_API_VERSION}/pcntl.so /usr/local/lib/php/extensions/no-debug-non-zts-${PHP_API_VERSION}/pcntl.so
COPY --from=ext-pcntl /usr/local/etc/php/conf.d/docker-php-ext-pcntl.ini /usr/local/etc/php/conf.d/docker-php-ext-pcntl.ini

FROM base as base-with-xdebug
ARG PHP_API_VERSION="20180731"
COPY --from=ext-xdebug /usr/local/lib/php/extensions/no-debug-non-zts-${PHP_API_VERSION}/xdebug.so /usr/local/lib/php/extensions/no-debug-non-zts-${PHP_API_VERSION}/xdebug.so
COPY --from=ext-xdebug /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

FROM base as base-cli
USER app:runner
COPY --chown=app:runner --from=app-installer /usr/src/app ./

FROM base-with-xdebug as base-coverage
RUN apk add --no-cache bash && chown app:runner /bin/bash
USER app:runner
ENV COVERAGE="1"
COPY --chown=app:runner --from=app-installer /usr/src/app ./

FROM base-cli as Cli
ENTRYPOINT ["./tests/Fixtures/Symfony/app/console"]
CMD ["swoole:server:run"]

FROM base-cli as Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --chown=app:runner --from=app-installer /usr/bin/composer /usr/local/bin/composer
ENTRYPOINT ["composer"]
CMD ["test"]

FROM base-coverage as ComposerCoverage
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --chown=app:runner --from=app-installer /usr/bin/composer /usr/local/bin/composer
ENTRYPOINT ["composer"]
CMD ["unit-code-coverage"]

FROM base-coverage as ServerCoverage
ENTRYPOINT ["/bin/bash"]
CMD ["tests/run-feature-tests-code-coverage.sh"]
