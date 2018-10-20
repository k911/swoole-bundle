<a name=""></a>
# [](https://github.com/k911/swoole-bundle/compare/v0.4.0...v) (2018-10-20)



<a name="0.4.0"></a>
# [0.4.0](https://github.com/k911/swoole-bundle/compare/v0.3.0...v0.4.0) (2018-10-20)


### Bug Fixes

* **command:** Graceful shutdown ([7e6c9a4](https://github.com/k911/swoole-bundle/commit/7e6c9a4))


### Code Refactoring

* **di:** Simplify registering configurators ([#14](https://github.com/k911/swoole-bundle/issues/14)) ([a34d59c](https://github.com/k911/swoole-bundle/commit/a34d59c))


### Features

* **hmr:** Implement HMR with Inotify ([97e88bb](https://github.com/k911/swoole-bundle/commit/97e88bb))


### BREAKING CHANGES

* **di:** - Server\HttpServerFactory should not be instantiated anymore, due to
removed hard coupling with ConfiguratorInterface, and `make()` method
becomig static. Now use directly: `HttpServerFactory::make()`
- Configuring server (using object implementing ConfiguratorInterface)
now happens in execute method of AbstractServerStartCommand
- Server\Configurator\ChainConfigurator now accepts
ConfiguratorInterface variadic starting from second argument and
implements IteratorAggregate retruning its configurators to ease DI usage (see
src/Bridge/Symfony/Bundle/Resources/commands.yaml)



<a name="0.3.0"></a>
# [0.3.0](https://github.com/k911/swoole-bundle/compare/v0.2.0...v0.3.0) (2018-10-13)


### Bug Fixes

* **io:** Properly close stdout/stderr ([94041e6](https://github.com/k911/swoole-bundle/commit/94041e6))


### Features

* **daemon-mode:** Daemonize Swoole HTTP server ([#8](https://github.com/k911/swoole-bundle/issues/8)) ([3cca5c4](https://github.com/k911/swoole-bundle/commit/3cca5c4))



<a name="0.2.0"></a>
# [0.2.0](https://github.com/k911/swoole-bundle/compare/17cde60...v0.2.0) (2018-10-07)


### Bug Fixes

* **command:** Decode configuration one more time ([32f9776](https://github.com/k911/swoole-bundle/commit/32f9776))
* **config:** Add trusted_proxies and trusted_hosts ([aae8873](https://github.com/k911/swoole-bundle/commit/aae8873)), closes [#5](https://github.com/k911/swoole-bundle/issues/5)
* **configuration:** Set proper service ids in symfony DI ([dda8c9d](https://github.com/k911/swoole-bundle/commit/dda8c9d))


### Features

* **swoole:** Add ability to customize server ([3534ed0](https://github.com/k911/swoole-bundle/commit/3534ed0))
* **swoole:** Add advanced static file serving ([17cde60](https://github.com/k911/swoole-bundle/commit/17cde60))
* **swoole:** Allow to change publicdir at runtime ([c5a0c27](https://github.com/k911/swoole-bundle/commit/c5a0c27))


### Performance Improvements

* **swoole:** Improve Dependency Injection configuration ([b9f6ddc](https://github.com/k911/swoole-bundle/commit/b9f6ddc))
* **swoole:** Improve Dependency Injection configuration ([6f83e11](https://github.com/k911/swoole-bundle/commit/6f83e11))


### BREAKING CHANGES

* **config:**   - Env APP_TRUSTED_HOSTS is no longer supported
  - Env APP_TRUSTED_PROXIES is no longer supported
  - Configuration 'swoole.http_server.services.debug' is renamed to 'swoole.http_server.services.debug_handler'
  - Configuration 'swoole.http_server.services.trust_all_proxies' is renamed to 'swoole.http_server.services.trust_all_proxies_handler'



