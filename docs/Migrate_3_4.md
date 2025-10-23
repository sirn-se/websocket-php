[Documentation](Index.md) / Migration v3 -> v4

# Websocket: Migration v3 -> v4

Version 4.x has few breaking changes compared to previous version.

## Breaking changes

* Client `setContext()` - no longer accepts array input
* Server `setContext()` - no longer accepts array input

## Removed deprecated methods

* Client `getMeta()` - removed
* Client `onConnect()` - use `onHandshake()` instead
* Server `onConnect()` - use `onHandshake()` instead

## Configuration management

`v4` uses the `Configuration` class to propagate configurations throughout internal classes.

This affects the following classes;
* Connection
* FrameHandler
* MessageHandler
* MiddlewareHandler
* All middlewares

The following methods (when applicable) are removed;
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

Increased modularization affects many internal classes and methods.
If you rely on using these directly (extending or adapting) your code may be incompatible.
