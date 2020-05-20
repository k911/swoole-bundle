## [0.7.9](https://github.com/k911/swoole-bundle/compare/v0.7.8...v0.7.9) (2020-05-20)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.7.8...v0.7.9)

### Bug Fixes

* **server:** add worker_max_request and worker_max_request_grace configuration options ([#220](https://github.com/k911/swoole-bundle/issues/220)) ([69fd435](https://github.com/k911/swoole-bundle/commit/69fd435717833d224ee6087c46fd96155e362d02))

## [0.7.8](https://github.com/k911/swoole-bundle/compare/v0.7.7...v0.7.8) (2020-05-03)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.7.7...v0.7.8)

### Miscellaneous

* Minor fixes

## [0.7.7](https://github.com/k911/swoole-bundle/compare/v0.7.6...v0.7.7) (2020-05-01)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.7.6...v0.7.7)

### Miscellaneous

* Minor fixes

## [0.7.6](https://github.com/k911/swoole-bundle/compare/v0.7.5...v0.7.6) (2020-04-01)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.7.5...v0.7.6)

### Bug Fixes

* **http-server:** return all headers with the same name joined by comma ([#175](https://github.com/k911/swoole-bundle/issues/175)) ([1e51639](https://github.com/k911/swoole-bundle/commit/1e51639306bb8f8b4d4f636c1ef1db80a6ab9b7f))

## [0.7.5](https://github.com/k911/swoole-bundle/compare/v0.7.4...v0.7.5) (2019-12-19)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.7.4...v0.7.5)

### Bug Fixes

* **server:** Add missing setting for "package_max_length" ([#96](https://github.com/k911/swoole-bundle/issues/96)) ([37758f2](https://github.com/k911/swoole-bundle/commit/37758f280e853d9c40c5dfe124ff3036e66d5294))

## [0.7.4](https://github.com/k911/swoole-bundle/compare/v0.7.3...v0.7.4) (2019-12-03)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.7.3...v0.7.4)

### Bug Fixes

* **server:** Use `SplFile::getRealPath` for `Response::sendfile` Operation for a BinaryFileReponse ([#91](https://github.com/k911/swoole-bundle/issues/91)) ([0278db7](https://github.com/k911/swoole-bundle/commit/0278db725c6b5f52bb513454a59ca16bef67f1da)), closes [#90](https://github.com/k911/swoole-bundle/issues/90)

## [0.7.3](https://github.com/k911/swoole-bundle/compare/v0.7.2...v0.7.3) (2019-11-30)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.7.2...v0.7.3)

### Reverts

* Revert "ci(circle): Remove loop in bash release script" ([a468ef7](https://github.com/k911/swoole-bundle/commit/a468ef7b455aa097ea573aeb35681fc19d753a4c)), closes [#81](https://github.com/k911/swoole-bundle/issues/81)

## [0.7.2](https://github.com/k911/swoole-bundle/compare/v0.7.1...v0.7.2) (2019-11-30)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.7.1...v0.7.2)

### Bug Fixes

* **composer:** Resolve upgrade issues ([#83](https://github.com/k911/swoole-bundle/issues/83)) ([92285ac](https://github.com/k911/swoole-bundle/commit/92285ac63b62579f5e23e965e5aa020f7e407c02))

## [0.7.1](https://github.com/k911/swoole-bundle/compare/v0.7.0...v0.7.1) (2019-11-14)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.7.0...v0.7.1)

### Miscellaneous

* Minor fixes

# [0.7.0](https://github.com/k911/swoole-bundle/compare/v0.6.2...v0.7.0) (2019-11-13)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.6.2...v0.7.0)

### Bug Fixes

* **http-client:** Make HttpClient serializable ([0ee8918](https://github.com/k911/swoole-bundle/commit/0ee89182073edfa46cefaea2d58731130d3a7252))
* **http-server:** Add top-level exception handler to prevent server timeouts ([#79](https://github.com/k911/swoole-bundle/issues/79)) ([08c76c4](https://github.com/k911/swoole-bundle/commit/08c76c4a0fa73c2b846d48f1a8afc0e8aef0b265)), closes [#78](https://github.com/k911/swoole-bundle/issues/78)


### Features

* **session:** Add in-memory syfmony session storage ([#73](https://github.com/k911/swoole-bundle/issues/73)) ([4ccdca0](https://github.com/k911/swoole-bundle/commit/4ccdca0f4709d0ab83bba068b6fad0376d53b849))

## [0.6.2](https://github.com/k911/swoole-bundle/compare/v0.6.1...v0.6.2) (2019-10-05)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.6.1...v0.6.2)

### Miscellaneous

* Minor fixes

## [0.6.1](https://github.com/k911/swoole-bundle/compare/v0.6.0...v0.6.1) (2019-10-04)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.6.0...v0.6.1)

### Miscellaneous

* Minor fixes


# [0.6.0](https://github.com/k911/swoole-bundle/compare/v0.5.3...v0.6.0) (2019-08-11)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.5.3...v0.6.0)

### Features

* **messenger:** Add Symfony Messenger integration ([#56](https://github.com/k911/swoole-bundle/issues/56)) ([d136313](https://github.com/k911/swoole-bundle/commit/d136313)), closes [#4](https://github.com/k911/swoole-bundle/issues/4)


## [0.5.3](https://github.com/k911/swoole-bundle/compare/v0.5.2...v0.5.3) (2019-06-06)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.5.2...v0.5.3)

### Bug Fixes

* **config:** set default host value to '0.0.0.0' ([#55](https://github.com/k911/swoole-bundle/issues/55)) ([2c9221d](https://github.com/k911/swoole-bundle/commit/2c9221d))


## [0.5.2](https://github.com/k911/swoole-bundle/compare/v0.5.1...v0.5.2) (2019-04-30)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.5.1...v0.5.2)

### Bug Fixes

* **server:** Make sure "reactor" running mode works correctly ([#53](https://github.com/k911/swoole-bundle/issues/53)) ([69dfea2](https://github.com/k911/swoole-bundle/commit/69dfea2))


## [0.5.1](https://github.com/k911/swoole-bundle/compare/v0.5.0...v0.5.1) (2019-04-28)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.5.0...v0.5.1)

### Bug Fixes

* **static-server:** Fix unset public dir path in "AdvancedStaticFilesServer" ([#52](https://github.com/k911/swoole-bundle/issues/52)) ([4ef8cb5](https://github.com/k911/swoole-bundle/commit/4ef8cb5))


# [0.5.0](https://github.com/k911/swoole-bundle/compare/v0.4.4...v0.5.0) (2019-04-26)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.4.4...v0.5.0)

### Bug Fixes

* **di:** Do not use integer node for port ([ac6fdcf](https://github.com/k911/swoole-bundle/commit/ac6fdcf))
* **hmr:** Drop unused reference to SymfonyStyle object in InotifyHMR ([6b22485](https://github.com/k911/swoole-bundle/commit/6b22485))
* **reload:** Make sure command works on macOS system ([4d99e9c](https://github.com/k911/swoole-bundle/commit/4d99e9c))

### Features

* **apiserver:** Create API Server component ([#32](https://github.com/k911/swoole-bundle/issues/32)) ([a8d0ec2](https://github.com/k911/swoole-bundle/commit/a8d0ec2)), closes [#2](https://github.com/k911/swoole-bundle/issues/2)
* **server:** Add setting for "buffer_output_size" ([#33](https://github.com/k911/swoole-bundle/issues/33)) ([7a50864](https://github.com/k911/swoole-bundle/commit/7a50864))
* **server:** Set-up hooks on lifecycle events ([271a341](https://github.com/k911/swoole-bundle/commit/271a341))
* Add meaningful exceptions ([#46](https://github.com/k911/swoole-bundle/issues/46)) ([4e2cc6d](https://github.com/k911/swoole-bundle/commit/4e2cc6d))

## [0.4.4](https://github.com/k911/swoole-bundle/compare/v0.4.3...v0.4.4) (2019-01-06)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.4.3...v0.4.4)

### Bug Fixes

* **di:** Fix regression introduced in v0.4.3 ([#29](https://github.com/k911/swoole-bundle/issues/29)) ([c88fcf2](https://github.com/k911/swoole-bundle/commit/c88fcf2))

## [0.4.3](https://github.com/k911/swoole-bundle/compare/v0.4.2...v0.4.3) (2019-01-06)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.4.2...v0.4.3)

### Bug Fixes

* **di:** Fix detection of doctrine bundle ([ef5920c](https://github.com/k911/swoole-bundle/commit/ef5920c))

## [0.4.2](https://github.com/k911/swoole-bundle/compare/v0.4.1...v0.4.2) (2018-11-05)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.4.1...v0.4.2)

### Bug Fixes

* **xdebug-handler:** Remove process timeout ([#23](https://github.com/k911/swoole-bundle/issues/23)) ([29148af](https://github.com/k911/swoole-bundle/commit/29148af))


## [0.4.1](https://github.com/k911/swoole-bundle/compare/v0.4.0...v0.4.1) (2018-10-24)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.4.0...v0.4.1)

### Bug Fixes

* **boot-manager:** Don't boot not bootable objects ([8ad97a2](https://github.com/k911/swoole-bundle/commit/8ad97a2)), closes [#19](https://github.com/k911/swoole-bundle/issues/19)
* **xdebug-handler:** Replace with custom solution ([0dc13f0](https://github.com/k911/swoole-bundle/commit/0dc13f0)), closes [#13](https://github.com/k911/swoole-bundle/issues/13)

# [0.4.0](https://github.com/k911/swoole-bundle/compare/v0.3.0...v0.4.0) (2018-10-20)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.3.0...v0.4.0)

### Bug Fixes

* **command:** Graceful shutdown ([7e6c9a4](https://github.com/k911/swoole-bundle/commit/7e6c9a4))


### Code Refactoring

* **di:** Simplify registering configurators ([#14](https://github.com/k911/swoole-bundle/issues/14)) ([a34d59c](https://github.com/k911/swoole-bundle/commit/a34d59c))


### Features

* **hmr:** Implement HMR with Inotify ([97e88bb](https://github.com/k911/swoole-bundle/commit/97e88bb))


### BREAKING CHANGES

- `Server\HttpServerFactory` should not be instantiated anymore, due to
removed hard coupling with `Server\Configurator\ConfiguratorInterface`, and `make()` method
becomig static. Now use directly: `Server\HttpServerFactory::make()`
- Configuring server (using object implementing `Server\Configurator\ConfiguratorInterface`)
now happens in execute method of AbstractServerStartCommand
- `Server\Configurator\ChainConfigurator` is now replaced by `Server\Configurator\GeneratedChainConfigurator`


# [0.3.0](https://github.com/k911/swoole-bundle/compare/v0.2.0...v0.3.0) (2018-10-13)

[Full changelog](https://github.com/k911/swoole-bundle/compare/v0.2.0...v0.3.0)

### Bug Fixes

* **io:** Properly close stdout/stderr ([94041e6](https://github.com/k911/swoole-bundle/commit/94041e6))


### Features

* **daemon-mode:** Daemonize Swoole HTTP server ([#8](https://github.com/k911/swoole-bundle/issues/8)) ([3cca5c4](https://github.com/k911/swoole-bundle/commit/3cca5c4))



# [0.2.0](https://github.com/k911/swoole-bundle/compare/c5a0c27...v0.2.0) (2018-10-07)

[Full changelog](https://github.com/k911/swoole-bundle/compare/c5a0c27...v0.2.0)

### Bug Fixes

* **command:** Decode configuration one more time ([32f9776](https://github.com/k911/swoole-bundle/commit/32f9776))
* **config:** Add trusted_proxies and trusted_hosts ([aae8873](https://github.com/k911/swoole-bundle/commit/aae8873)), closes [#5](https://github.com/k911/swoole-bundle/issues/5)
* **configuration:** Set proper service ids in symfony DI ([dda8c9d](https://github.com/k911/swoole-bundle/commit/dda8c9d))


### Features

* **swoole:** Allow to change publicdir at runtime ([c5a0c27](https://github.com/k911/swoole-bundle/commit/c5a0c27))


### BREAKING CHANGES

* Env `APP_TRUSTED_HOSTS` is no longer supported
* Env `APP_TRUSTED_PROXIES` is no longer supported
* Configuration `swoole.http_server.services.debug` is renamed to `swoole.http_server.services.debug_handler`
* Configuration `swoole.http_server.services.trust_all_proxies` is renamed to `swoole.http_server.services.trust_all_proxies_handler`
