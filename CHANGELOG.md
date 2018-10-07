<a name=""></a>
# [](https://github.com/k911/swoole-bundle/compare/v0.2.0-dev...v) (2018-10-07)



<a name="0.2.0-dev"></a>
# [0.2.0-dev](https://github.com/k911/swoole-bundle/compare/04a7dcc...v0.2.0-dev) (2018-09-30)


### Bug Fixes

* **command:** Decode configuration one more time ([32f9776](https://github.com/k911/swoole-bundle/commit/32f9776))
* **config:** Add trusted_proxies and trusted_hosts ([aae8873](https://github.com/k911/swoole-bundle/commit/aae8873)), closes [#5](https://github.com/k911/swoole-bundle/issues/5)
* **configuration:** Set proper service ids in symfony DI ([dda8c9d](https://github.com/k911/swoole-bundle/commit/dda8c9d))
* **swoole:** Fix static file serving ([443bd13](https://github.com/k911/swoole-bundle/commit/443bd13))
* **swoole:** Make swoole http server lazy ([#132](https://github.com/k911/swoole-bundle/issues/132)) ([04a7dcc](https://github.com/k911/swoole-bundle/commit/04a7dcc))


### Features

* **swoole:** Add ability to customize server ([3534ed0](https://github.com/k911/swoole-bundle/commit/3534ed0))
* **swoole:** Add advanced static file serving ([17cde60](https://github.com/k911/swoole-bundle/commit/17cde60))
* **swoole:** Allow to change publicdir at runtime ([c5a0c27](https://github.com/k911/swoole-bundle/commit/c5a0c27))
* **swoole:** Disable Xdebug using XdebugHandler ([97ae8e7](https://github.com/k911/swoole-bundle/commit/97ae8e7))
* **swoole:** Process and respond cookies ([0b7e883](https://github.com/k911/swoole-bundle/commit/0b7e883))
* **swoole:** Use multiple http server workers ([8062a33](https://github.com/k911/swoole-bundle/commit/8062a33))


### Performance Improvements

* **swoole:** Improve Dependency Injection configuration ([b9f6ddc](https://github.com/k911/swoole-bundle/commit/b9f6ddc))
* **swoole:** Improve Dependency Injection configuration ([6f83e11](https://github.com/k911/swoole-bundle/commit/6f83e11))
* **swoole:** Use callable array form to handle request to avoid function call ([67e3154](https://github.com/k911/swoole-bundle/commit/67e3154))


### BREAKING CHANGES

* **config:**   - Env APP_TRUSTED_HOSTS is no longer supported
  - Env APP_TRUSTED_PROXIES is no longer supported
  - Configuration 'swoole.http_server.services.debug' is renamed to 'swoole.http_server.services.debug_handler'
  - Configuration 'swoole.http_server.services.trust_all_proxies' is renamed to 'swoole.http_server.services.trust_all_proxies_handler'



