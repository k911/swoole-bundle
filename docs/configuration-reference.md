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
        running_mode: 'process'
        socket_type: tcp
        ssl_enabled: false
        trusted_hosts: localhost,127.0.0.1
        trusted_proxies: *
        static:
            strategy: 'advanced'
            public_dir: '%kernel.project_dir%/public'
        services:
            debug_handler: true
            trust_all_proxies_handler: true
            cloudfront_proto_header_handler: true
            entity_manager_handler: true
        settings:
            worker_count: 4
            reactor_count: 2
            log_file: '%kernel.logs_dir%/swoole_%kernel.environment%.log'
            log_level: auto
            pid_file: '/var/run/swoole_http_server.pid'
```
