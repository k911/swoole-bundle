# Docker usage

## Streaming logs

Symfony application logs are kept in files by default. In Docker containers, which should be ephemeral, you should either stream logs to specific service (like AWS CloudWatch) or print them to the `stdout` or `sterr` streams. For both cases you should use `monolog` bundle.

```bash
composer require monolog
```

### Streaming Symfony application logs to `stdout` in Docker

Example configuration using `monolog` and `symfony`, can be found in demo project.

Relevant configuration files:

- [docker-compose.yml](https://github.com/k911/swoole-bundle-symfony-demo/blob/master/docker-compose.yml)
- [config/services.yaml](https://github.com/k911/swoole-bundle-symfony-demo/blob/master/config/services.yaml)
- [config/packages/dev/monolog.yaml](https://github.com/k911/swoole-bundle-symfony-demo/blob/master/config/packages/dev/monolog.yaml)
- [config/packages/prod/monolog.yaml](https://github.com/k911/swoole-bundle-symfony-demo/blob/master/config/packages/prod/monolog.yaml)

### Streaming Swoole HTTP Server logs to `stdout` in Docker

To get Swoole HTTP Server stream internal logs to `stdout`, use this configuration:

```yaml
# source: https://github.com/k911/swoole-bundle-symfony-demo/blob/master/config/packages/swoole.yaml

parameters:
    ...
    env(SWOOLE_LOG_STREAM_PATH): "%kernel.logs_dir%/swoole_%kernel.environment%.log"

swoole:
    http_server:
        ...
        settings:
            log_file: "%env(resolve:SWOOLE_LOG_STREAM_PATH)%"
```

In your `docker-compose.yml` file set environment variable `SWOOLE_LOG_STREAM_PATH` to `/proc/self/fd/1` value, which is real `stdout` in docker container.

```yaml
# source: https://github.com/k911/swoole-bundle-symfony-demo/blob/master/docker-compose.yml

version: "3.6"
services:
    app:
        ...
        environment:
            SWOOLE_LOG_STREAM_PATH: /proc/self/fd/1
```

This way you'll have logs locally in `var/logs/swoole_*.log` file and printed to `stdout` while running in docker.

## Recommended Dockerfile

Features:

- Multi-stage builds (fast rebuilds)
- Secure (run as custom user)
- Portable (add as many php extensions as you want)

```dockerfile
ARG PHP_TAG="7.3-cli-alpine3.9"

FROM php:$PHP_TAG as ext-builder
RUN docker-php-source extract && \
    apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS

FROM ext-builder as ext-swoole
ARG SWOOLE_VERSION="4.3.3"
RUN pecl install swoole-${SWOOLE_VERSION} && \
    docker-php-ext-enable swoole

FROM composer:latest as app-installer
WORKDIR /usr/src/app
RUN composer global require "hirak/prestissimo:^0.3" --prefer-dist --no-progress --no-suggest --classmap-authoritative --ansi
COPY composer.json composer.lock symfony.lock ./
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

FROM base as App
USER app:runner
COPY --chown=app:runner --from=app-installer /usr/src/app ./
ENTRYPOINT ["./bin/console"]
CMD ["swoole:server:run"]
```

## Demo project

If you want to quickly test above configuration on your computer, clone [`k911/swoole-bundle-symfony-demo`](https://github.com/k911/swoole-bundle-symfony-demo) repository and run two simple commands:

```bash
git clone https://github.com/k911/swoole-bundle-symfony-demo.git
cd swoole-bundle-symfony-demo

docker-compose build
docker-compose up
```
