<a name=""></a>
# [](https://github.com/k911/swoole-bundle/compare/v0.4.3...v) (2019-01-06)



<a name="0.4.3"></a>
## [0.4.3](https://github.com/k911/swoole-bundle/compare/v0.4.2...v0.4.3) (2019-01-06)


### Bug Fixes

* **di:** Fix detection of doctrine bundle ([ef5920c](https://github.com/k911/swoole-bundle/commit/ef5920c))



<a name=""></a>
# [](https://github.com/k911/swoole-bundle/compare/v0.4.2...v) (2018-11-05)



<a name="0.4.2"></a>
## [0.4.2](https://github.com/k911/swoole-bundle/compare/v0.4.1...v0.4.2) (2018-11-05)


### Bug Fixes

* **xdebug-handler:** Remove process timeout ([#23](https://github.com/k911/swoole-bundle/issues/23)) ([29148af](https://github.com/k911/swoole-bundle/commit/29148af))


<a name=""></a>
# [](https://github.com/k911/swoole-bundle/compare/v0.4.1...v) (2018-10-24)



<a name="0.4.1"></a>
## [0.4.1](https://github.com/k911/swoole-bundle/compare/v0.4.0...v0.4.1) (2018-10-24)


### Bug Fixes

* **boot-manager:** Don't boot not bootable objects ([8ad97a2](https://github.com/k911/swoole-bundle/commit/8ad97a2)), closes [#19](https://github.com/k911/swoole-bundle/issues/19)
* **xdebug-handler:** Replace with custom solution ([0dc13f0](https://github.com/k911/swoole-bundle/commit/0dc13f0)), closes [#13](https://github.com/k911/swoole-bundle/issues/13)



<a name="0.4.0"></a>
# [0.4.0](https://github.com/k911/swoole-bundle/compare/v0.3.0...v0.4.0) (2018-10-20)


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



<a name="0.3.0"></a>
# [0.3.0](https://github.com/k911/swoole-bundle/compare/v0.2.0...v0.3.0) (2018-10-13)


### Bug Fixes

* **io:** Properly close stdout/stderr ([94041e6](https://github.com/k911/swoole-bundle/commit/94041e6))


### Features

* **daemon-mode:** Daemonize Swoole HTTP server ([#8](https://github.com/k911/swoole-bundle/issues/8)) ([3cca5c4](https://github.com/k911/swoole-bundle/commit/3cca5c4))



<a name="0.2.0"></a>
# [0.2.0](https://github.com/k911/swoole-bundle/compare/c5a0c27...v0.2.0) (2018-10-07)


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



