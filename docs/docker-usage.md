# Docker usage

## Example Dockerfile

```Docker
FROM php:7.2-cli-alpine3.8

ARG SWOOLE_VERSION=4.2.1

RUN docker-php-source extract && \
    apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS && \
    pecl install swoole-$SWOOLE_VERSION && \
    docker-php-ext-enable swoole && \
    apk del .phpize-deps && \
    docker-php-source delete

WORKDIR /usr/src/app
ENTRYPOINT ["bin/console"]
CMD ["swoole:server:run"]

COPY . ./

```
