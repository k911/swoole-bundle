# Swoole Bundle

[![Maintainability](https://api.codeclimate.com/v1/badges/1d73a214622bba769171/maintainability)](https://codeclimate.com/github/k911/swoole-bundle/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/1d73a214622bba769171/test_coverage)](https://codeclimate.com/github/k911/swoole-bundle/test_coverage)
[![Open Source Love](https://badges.frapsoft.com/os/v1/open-source.svg?v=103)](https://github.com/ellerbrock/open-source-badges/)
[![MIT Licence](https://badges.frapsoft.com/os/mit/mit.svg?v=103)](https://opensource.org/licenses/mit-license.php)

Symfony integration with [Swoole](https://www.swoole.co.uk/) to speed up your applications.

---

## Build Matrix

| CI Job | Branch [`master`](https://github.com/k911/swoole-bundle/tree/develop)                                                                       | Branch [`develop`](https://github.com/k911/swoole-bundle/tree/master)                                                                         |
| ------ | ------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| Circle | [![CircleCI](https://circleci.com/gh/k911/swoole-bundle/tree/master.svg?style=svg)](https://circleci.com/gh/k911/swoole-bundle/tree/master) | [![CircleCI](https://circleci.com/gh/k911/swoole-bundle/tree/develop.svg?style=svg)](https://circleci.com/gh/k911/swoole-bundle/tree/develop) |
| CodeCov | [![codecov](https://codecov.io/gh/k911/swoole-bundle/branch/master/graph/badge.svg)](https://codecov.io/gh/k911/swoole-bundle) | [![codecov](https://codecov.io/gh/k911/swoole-bundle/branch/develop/graph/badge.svg)](https://codecov.io/gh/k911/swoole-bundle) |
| Travis | [![Build Status](https://travis-ci.org/k911/swoole-bundle.svg?branch=master)](https://travis-ci.org/k911/swoole-bundle)                     | [![Build Status](https://travis-ci.org/k911/swoole-bundle.svg?branch=develop)](https://travis-ci.org/k911/swoole-bundle)                      |

## Table of Contents

- [Swoole Bundle](#swoole-bundle)
  - [Build Matrix](#build-matrix)
  - [Table of Contents](#table-of-contents)
  - [Quick start guide](#quick-start-guide)
  - [Features](#features)
  - [Requirements](#requirements)
    - [Current (`0.8.x`)](#current-08x)
    - [Future](#future)
    - [Swoole](#swoole)
      - [Version check](#version-check)
      - [Installation](#installation)

## Quick start guide

1. Make sure you have installed proper Swoole PHP Extension and pass other [requirements](#requirements).

2. (optional) Create a new symfony project

    ```bash
    composer create-project symfony/skeleton project

    cd ./project
    ```

3. Install bundle in your Symfony application

    ```bash
    composer require k911/swoole-bundle
    ```

4. Edit `config/bundles.php`

    ```php
    return [
        // ...other bundles
        K911\Swoole\Bridge\Symfony\Bundle\SwooleBundle::class => ['all' => true],
    ];
    ```

5. Run Swoole HTTP Server

    ```bash
    bin/console swoole:server:run
    ```

6. Enter http://localhost:9501

7. You can now configure bundle according to your needs

## Features

- Built-in API Server

    Swoole Bundle API Server allows managing Swoole HTTP Server in real-time.

    - Reload worker processes
    - Shutdown server
    - Access metrics and settings

- Improved static files serving

    Swoole HTTP Server provides a default static files handler, but it lacks supporting many `Content-Types`. To overcome this issue, there is a configurable Advanced Static Files Server. Static files serving remains enabled by default in the development environment. Static files directory defaults to `%kernel.project_dir%/public`. To configure your custom mime types check [configuration reference](docs/configuration-reference.md) (key `swoole.http_server.static.mime_types`).

- Symfony Messenger integration

    *Available since version: `0.6`*

    Swoole Server Task Transport has been integrated into this bundle to allow easy execution of asynchronous actions. Documentation of this feature is available [here](docs/swoole-task-symfony-messenger-transport.md).

- Hot Module Reload (HMR) for development **ALPHA**

    Since Swoole HTTP Server runs in Event Loop and does not flush memory between requests, to keep DX equal with normal servers, this bundle uses code replacement technique, using `inotify` PHP Extension to allow continuous development. It is enabled by default (when the extension is found) and requires no additional configuration. You can turn it off in bundle configuration.

    *Remarks: This feature currently works only on a Linux host machine. It probably won't work with Docker, and it is possible that it works only with configuration: `swoole.http_server.running_mode: process` (default).*

## Requirements

### Current (`0.8.x`)

- PHP version `>= 7.4`
- Swoole PHP Extension `>= 4.5.10`
- Symfony `>= 4.3.1`

### Future

- PHP version `>= 8.0`
- Swoole PHP Extension `>= 4.6`
- Symfony `>= 5.0`

Additional requirements to enable specific features:

- [Inotify PHP Extension](https://pecl.php.net/package/inotify) `^2.0.0` to use Hot Module Reload (HMR)
    - When using PHP 8, inotify version `^3.0.0` is required 

### Swoole

Bundle requires [Swoole PHP Extension](https://github.com/swoole/swoole-src) version `4.5.10` or higher. Active bug fixes are provided only for the latest version.

#### Version check

To check your installed version you can run the following command:

```sh
php -r "echo swoole_version() . \PHP_EOL;"

# 4.4.7
```

#### Installation

Official GitHub repository [swoole/swoole-src](https://github.com/swoole/swoole-src#%EF%B8%8F-installation) contains comprehensive installation guide. The recommended approach is to install it [from source](https://github.com/swoole/swoole-src#3-install-from-source-recommended).
