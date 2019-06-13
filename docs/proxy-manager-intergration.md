# Proxy Manager integration

Swoole Bundle uses `lazy: true` option instead of internal Proxy objects when package `symfony/proxy-manager-bridge` is installed. 

## Enabling Proxy Manager

To enable it in your application run following commands

```sh
composer require symfony/proxy-manager-bridge
bin/console cache:clear
```
