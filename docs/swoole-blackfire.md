# Swoole Server Blackfire integration

Blackfire (https://blackfire.io/docs/introduction) is a profiler for PHP applications.

By default, blackfire does not work with Swoole Server, however, thanks to the work of [https://github.com/upscalesoftware/swoole-blackfire](https://github.com/upscalesoftware/swoole-blackfire) the Swoole server can be instrumented to produce data for blackfire.

## How to use?

First of all, setup blackfire following their docs [https://blackfire.io/docs/up-and-running/installation](https://blackfire.io/docs/up-and-running/installation)
 
Then, install the swoole-blackfire library

```shell script
composer require upscale/swoole-blackfire --dev
``` 

That's it! The bundle will automatically detect that the library was installed and it will instrument the server.

If, for some reason, you want to explicitly disable the profiler, you can do so from the bundle configuration, see [here](configuration-reference.md)