# Contributing guide

- [Contributing guide](#Contributing-guide)
  - [Local development](#Local-development)
    - [Environment](#Environment)
      - [PHP](#PHP)
      - [Docker](#Docker)
      - [docker-compose](#docker-compose)
  - [Testing](#Testing)
    - [Composer](#Composer)
      - [Code style quick fix command](#Code-style-quick-fix-command)
      - [All-in-one command](#All-in-one-command)
      - [Unit tests](#Unit-tests)
      - [Feature tests](#Feature-tests)
    - [Docker](#Docker-1)
      - [Building images](#Building-images)
      - [Running composer commands in docker](#Running-composer-commands-in-docker)
      - [Generating code coverage](#Generating-code-coverage)
  - [Creating a Pull Request](#Creating-a-Pull-Request)
  - [Why docker](#Why-docker)

## Local development

### Environment

There are several requirements to start developing `Swoole Bundle`:

- **Git**
- [**PHP**](#PHP)
- [**Swoole**](#Swoole)
- [**Composer**](#Composer)
- [**Docker**](#Docker)`*`
- [**docker-compose**](#docker-compose)`*`

`*` - **Docker** and **docker-compose** are not critical requirements but highly recommended. For more informations read [this](#why-docker) section.

#### [PHP](https://www.php.net/manual/en/install.php)

Make sure you have installed the latest version of PHP:

```sh
php -v

# PHP 7.3.7 (cli) (built: Jul  3 2019 13:55:48) ( NTS )
# Copyright (c) 1997-2018 The PHP Group
# Zend Engine v3.3.7, Copyright (c) 1998-2018 Zend Technologies
```

Currently, the minimum supported version is `7.2.19` but it is highly recommended to develop using the latest PHP version.

#### [Docker](https://docs.docker.com/install/)

Docker is a relatively new solution, therefore vulnerable to security issues, you should always keep it up-to-date.

```sh
docker --version

# Docker version 18.09.7-ce, build 2d0083d657
```

Minimum supported version of `Docker` due to used `docker-compose`'s [format version](https://docs.docker.com/compose/compose-file/compose-versioning/) version `3.6` is `18.02.0+`.

#### [docker-compose](https://docs.docker.com/compose/install/)

```sh
docker-compose --version

# docker-compose version 1.24.1, build unknown
```

`docker-compose` version is not that important, the only requirement it supports format version `3.6`, which should be supported by versions `1.20.0` and above.

## Testing

### Composer

#### Code style quick fix command

Command `composer fix` can be used to automatically format code according to the project's code style. It should be run always before `git commit` command.

#### All-in-one command

Command `composer test` is really helpful to run periodically during development. It consists of basic checks like code style (`php-cs-fixer`), unit tests (`phpunit`) and static analysis (`phpstan`).

Please note that this command does not run `feature/functional` tests, which are required to pass while adding a new feature or fixing a bug.

#### Unit tests

Mostly you'll use command `composer test` which already runs unit tests, but there is also dedicated command `composer unit-tests` 

#### Feature tests

Command `composer feature-tests` runs real commands and/or server in subprocesses making it a little bit unstable, upon failure it may leave running server process in the background. It is recommended to run this command using **Docker**.

To check whether it left server process running in background use command:
```sh
lsof -i :9999 | grep php
```

To kill these processes use command:

```sh
kill -9 $(lsof -i :9999 | grep php | awk '{print $2}')
```

### Docker

#### Building images

Before running any docker command it is required to `rebuild` container images.

To build all images use the command:

```sh
docker-compose build --pull
```

Or to build specific services use

```sh
docker-compose build --pull composer
```

#### Running composer commands in docker

You can run any composer command directly in docker container using bellow snippets:

```sh
docker-compose run --rm composer test
# or
docker-compose run --rm composer feature-tests
# or
docker-compose run --rm composer unit-tests
```

After you run any docker containers, it's easy to clean up, just run:

```sh
docker-compose down
```

#### Generating code coverage

To gather code coverage, a PHP extension `xdebug` or `pcov` is required. Swoole is currently officially incompatible with any debugging extension, so it is unstable and requires some hacks to reliably gather code coverage, especially for feature tests.

Therefore the full flow of gathering code coverage can be done securely only in docker containers.

**Attention**: Bellow commands creates locally `./cov` directory and `clover.xml` file with merged code coverage in the `clover` format.

```sh
docker-compose build --pull coverage-pcov coverage-xdebug-feature-with-retry merge-code-coverage

docker-compose run --rm coverage-pcov
docker-compose run --rm coverage-xdebug-feature-with-retry
docker-compose run --rm coverage-pcov feature-code-coverage
docker-compose run --rm merge-code-coverage
```

## Creating a Pull Request

1. [Fork this repository](https://help.github.com/en/articles/fork-a-repo) to your local account
2. Create a feature/bugfix/any branch from `develop` branch of this repository
3. Make your changes (don't forget to write unit/feature tests)
4. Create a commit with a message following [`conventional-changelog/angular` convetion](https://github.com/conventional-changelog/conventional-changelog/tree/master/packages/conventional-changelog-angular) or use [`commitizen`](https://github.com/commitizen/cz-cli), otherwise, your commit message must be edited by a maintainer and therefore PR will be a little bit longer in Code Review.
5. Make sure new and old tests are passing locally (see [testing](#Testing) section)
6. Use `composer fix` command locally to ensure code is formatted properly
7. Push your branch to your forked repository
8. Submit a pull request
9. If Continous Integration checks fail, try to fix issues and submit changes by either `git commit --amend && git push --force` your commit(s) or providing a new commit with fixed changes
10. That's all!

## Why docker

Testing `Swoole` components like `Server` and `Coroutines` are currently hard due to a lack of compatible debugging tools (for example to generate code coverage). There are used several hacks to provide a reliable test suite. This requires a stable testing environment due to running fragile tests. Docker ensures every test is running inside the same or very similar environment. Containers also provide nice isolation between test runs, so we won't have to worry about things like cache / temporary files / changes in test files.

Those were the main reasons but there are, of course, some other benefits of using docker like:

- The easily configurable testing matrix
- Ability to run the whole test suite locally (and debug its results!)
- Developers do not have to know for example how to compile PHP extensions (because it's already done in `Dockerfile`)
