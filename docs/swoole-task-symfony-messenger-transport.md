# Swoole Server Task Transport (Symfony Messenger)

## Usage

1. Make sure you've enabled task workers in Swoole server

    ```yaml
    # config/packages/swoole.yaml
    swoole:
        http_server:
            settings:
                task_worker_count: auto
    ```

2. Install `symfony/messenger` package in your application

    ```sh
    composer require symfony/messenger
    ```

3. Configure Swoole Transport

    ```yaml
    # config/packages/messenger.yaml
    framework:
        messenger:
            transports:
                swoole: swoole://task
            routing:
                '*': swoole
    ```

4. Now follow official Symfony Messenger guide to create messages, handlers and optionally different transports.

    https://symfony.com/doc/current/messenger.html

## Example

You can also clone and play with [`swoole-bundle-symfony-demo`](https://github.com/k911/swoole-bundle-symfony-demo), where everything including Symfony Messenger configuration is set-up properly. You can run it in seconds using Docker!

## Implementation Notes

Swoole Task Transport always execute tasks / messages on the same Swoole HTTP Server instance, so before using it on production make sure you don't need to share persist/messages between different hosts without using external queue system like RabbitMQ. However, this transport should be convinenent to use for testing / local/development environments or on non-critical production workloads due to its simplicity. Also, keep in note that messages are passed between server processes without any serialization process, so unlike to **AMPQ** transport it is **NOT** required to implement `\Serializable` interface on your messages or include `symfony/serializer` package.
