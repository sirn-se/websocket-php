[Documentation](Index.md) / Migration v3 -> v4

# Websocket: Migration v3 -> v4

Version 4.x has few changes compared to previous version.

## Breaking changes


## Removed deprecated code

* Client `getMeta()` - removed
* Client `onConnect()` - use `onHandshake()` instead
* Client `setContext()` - no longer accepts array input
* Server `onConnect()` - use `onHandshake()` instead
* Server `setContext()` - no longer accepts array input

## Configuration management

v4 uses the Configuration class to propagate configurations throughout internal classes.

This affects the following classes;
* Connection
* FrameHandler
* MessageHandler
* MiddlewareHandler
* All middlewares

He following methods (when applicable) are removed;
* `setLogger()`
* `getTimeout()`
* `setTimeout()`
* `getFrameSize()`
* `setFrameSize()`

Instead these classes can be configured using;
* `__construct(..., WebSocket\Configuration|null $configuration = null)`
* `setConfiguration(WebSocket\Configuration $configuration)`

If you need to set configuration on internal classes, best way is to clone the original;
```php
$clonedConfiguration = clone $source->getConfiguration();
$clonedConfiguration->setLogger(...);
$clonedConfiguration->setTimeout(...);
$clonedConfiguration->setFrameSize(...);
$source->setConfiguration($clonedConfiguration);
```

## Extending

