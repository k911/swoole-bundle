# Swoole Bundle Configuration

Documentation of available configuration parameters. See also symfony [bundle configuration](./../src/Bridge/Symfony/Bundle/DependencyInjection/Configuration.php) file or [swoole documentation](https://github.com/swoole/swoole-docs/tree/master/modules).

- [HTTP Server](#http-server)

## HTTP Server

*Example*:

```yaml
swoole:
    http_server:
        port: 9501
        host: localhost
        running_mode: process
        socket_type: tcp
        ssl_enabled: false
        trusted_hosts: localhost,127.0.0.1
        trusted_proxies:
            - '*'
            - 127.0.0.1/8
            - 192.168.2./16

        # enables static file serving
        static: advanced
        # equals to:
        # ---
        # static:
        #     public_dir: '%kernel.project_dir%/public'
        #     strategy: advanced
        # ---
        # strategy can be one of: (default) auto, off, advanced, default
        #   - off: turn off feature
        #   - auto: use 'advanced' when debug enabled or not production environment
        #   - advanced: use request handler class \K911\Swoole\Server\RequestHandler\AdvancedStaticFilesServer
        #   - default: use default swoole static serving (faster than advanced, but supports less content types)

        # enables hot module reload using inotify
        hmr: auto
        # hmr can be one of: off, (default) auto, inotify
        #   - off: turn off feature
        #   - auto: use inotify if installed in the system
        #   - inotify: use inotify

        # enables api server on specific port
        # by default it is disabled (can be also enabled using --api flag via cli)
        api: true
        # equals to:
        # ---
        # api:
        #     enabled: true
        #     host: 0.0.0.0
        #     port: 9200

        # additional swoole symfony bundle services
        services:
            # see: \K911\Swoole\Bridge\Symfony\HttpKernel\DebugHttpKernelRequestHandler
            debug_handler: true

            # see: \K911\Swoole\Bridge\Symfony\HttpFoundation\TrustAllProxiesRequestHandler
            trust_all_proxies_handler: true

            # see: \K911\Swoole\Bridge\Symfony\HttpFoundation\CloudFrontRequestFactory
            cloudfront_proto_header_handler: true

            # see: \K911\Swoole\Bridge\Doctrine\ORM\EntityManagerHandler
            entity_manager_handler: true

        # swoole http server settings
        # see https://www.swoole.co.uk/docs/modules/swoole-server/configuration
        settings:
            reactor_count: 2
            worker_count: 4
            # when not set, swoole sets these are automatically set based on count of host CPU cores

            log_level: auto
            # can be one of: (default) auto, debug, trace, info, notice, warning, error
            #   - auto: when debug set to debug, when not set to notice
            #   - {debug,trace,info,notice,warning,error}: see swoole configuration

            log_file: '%kernel.logs_dir%/swoole_%kernel.environment%.log'
            pid_file: /var/run/swoole_http_server.pid

            buffer_output_size: 2097152
            # in bytes, 2097152b = 2 MiB
```
