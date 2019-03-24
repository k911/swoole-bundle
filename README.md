![travis](https://api.travis-ci.org/k911/swoole-bundle.svg?branch=develop)
[![Maintainability](https://api.codeclimate.com/v1/badges/1d73a214622bba769171/maintainability)](https://codeclimate.com/github/k911/swoole-bundle/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/1d73a214622bba769171/test_coverage)](https://codeclimate.com/github/k911/swoole-bundle/test_coverage)
[![Open Source Love](https://badges.frapsoft.com/os/v1/open-source.svg?v=103)](https://github.com/ellerbrock/open-source-badges/)
[![MIT Licence](https://badges.frapsoft.com/os/mit/mit.svg?v=103)](https://opensource.org/licenses/mit-license.php)

# Swoole Bundle
Symfony integration with [Swoole](https://www.swoole.co.uk/) to speed up your applications.

- [Quick start guide](#quick-start-guide)
- [Features](#features)
- [Requirements](#requirements)
- [Configuration](./docs/configuration-reference.md)
- [Usage with Docker](./docs/docker-usage.md)

## Quick start guide
0. Make sure you have installed proper Swoole PHP Extension and pass other [requirements](#requirements).

1. Create symfony project

    (You can skip this step if you already have a project)

    ```bash
    composer create-project symfony/skeleton project

    cd ./project
    ```

2. Install bundle in your symfony application

    ```bash
    composer require k911/swoole-bundle
    ```

3. Edit `config/bundles.php`

    ```php
    return [
        // ...other bundles
        K911\Swoole\Bridge\Symfony\Bundle\SwooleBundle::class => ['all' => true],
    ];
    ```

4. Run Swoole HTTP Server

    ```bash
    $ bin/console swoole:server:run
    ```

5. Enter http://localhost:9501

6. You can now configure bundle according to your needs

## Features

- Improved static files serving
    
    Swoole HTTP Server provides a default static files handler, but it lacks supporting many `Content-Types`. In order to overcome this issue, there is (not yet configurable) Advanced Static Files Server. Static files serving is enabled by default in development environment. Static files directory defaults to `%kernel.project_dir%/public`. 

- Hot Module Reload (HMR) - for development

    Since Swoole HTTP Server runs in Event Loop and do not flush memory between requests, to keep DX equal with normal servers, this bundle uses code replacement techinque, using `inotify` PHP Extension to allow contionus development. It is enabled by default (when extension is found), and requires no additional configuration. You can turn it off in bundle configuration. 

## Requirements

- Swoole PHP Extension `^4.3.0`
- Symfony `^4.2`

Additional requirements to enable specific features:
- [Inotify PHP Extension](https://pecl.php.net/package/inotify) `^2.0.0` to use Hot Module Reload (HMR)

### Swoole
Bundle requires [Swoole PHP Extension](https://github.com/swoole/swoole-src) version 4.3.0 or higher. Active bug fixes are provided only for latest version.


#### Version check
To check your installed version you can run following command:

```bash
php -r "echo swoole_version();"

# 4.2.3
```

#### Installation

To install latest version of swoole use `pecl`:

```bash
pecl install swoole
```

Or, to skip interactive questions:

```bash
echo "\n" | pecl install swoole 
```




