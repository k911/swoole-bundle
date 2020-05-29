# Swoole Server Doctrine integration

This bundle contains a basic handler for integration with Doctrine entity managers. This handler pings the database
connection on each request and calls `EntityManager::clear()` after each request processing.

However this approach is quite simple and doesn't contain error handling logic for cases, when the entity manager instance
will get closed because of an error.

For using Doctrine with this bundle in production, it is recommended to use the 
[Doctrine resettable entity manager bundle](https://github.com/pixelfederation/doctrine-resettable-em-bundle).

This bundle will do the same as the handler mentioned above, but it does this for all the entity managers registered
in the application. It automatically wraps all of them and instead only clearing the manager, it will also reset the 
entity manager if it got closed during request processing. 

## How to use?

You need to install the doctrine-resettable-em-bundle

```shell script
composer require pixelfederation/doctrine-resettable-em-bundle
``` 

After that, you need to activate the bundle in `bundles.php`.

```php
return [
// ...
    PixelFederation\DoctrineResettableEmBundle\PixelFederationDoctrineResettableEmBundle::class => ['all' => true],
// ...
];
```

That's it! After that, all your entity managers will be able to handle all possibly occurring errors.