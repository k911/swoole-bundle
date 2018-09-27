![travis](https://api.travis-ci.org/k911/swoole-bundle.svg?branch=develop)
[![Maintainability](https://api.codeclimate.com/v1/badges/1d73a214622bba769171/maintainability)](https://codeclimate.com/github/k911/swoole-bundle/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/1d73a214622bba769171/test_coverage)](https://codeclimate.com/github/k911/swoole-bundle/test_coverage)
[![Open Source Love](https://badges.frapsoft.com/os/v1/open-source.svg?v=103)](https://github.com/ellerbrock/open-source-badges/)
[![MIT Licence](https://badges.frapsoft.com/os/mit/mit.svg?v=103)](https://opensource.org/licenses/mit-license.php)

# Swoole Bundle
Symfony integration with [Swoole](https://www.swoole.co.uk/) to speed up your applications.

- [Quick start guide](#quick-start-guide)
- [Configuration](./docs/configuration-reference.md)
- [Usage with Docker](./docs/docker-usage.md)

## Quick start guide

0. Make sure you've installed swoole php extension (v4.x.x)

    ```bash
    $ pecl install swoole
    ```

1. Install bundle

    ```bash
    $ composer require k911/swoole-bundle
    ```

2. Edit `config/bundles.php`

    ```php
    return [
        // ...other bundles
        K911\Swoole\Bridge\Symfony\Bundle\SwooleBundle::class => ['all' => true],
    ];
    ```

3. Create basic configuration file `config/packages/swoole.yaml`

    ```yaml
    parameters:
        env(HOST): localhost
        env(PORT): 9501

    swoole:
        http_server:
            port: '%env(int:PORT)%'
            host: '%env(HOST)%'
    ```

4. Run swoole server

    ```bash
    $ bin/console swoole:server:run
    ```
