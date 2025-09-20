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

v4 uses the Configuration class to propagate configurations throughout internal classes.

This affects the following classes;
* Connection
* FrameHandler
* MessageHandler
* MiddlewareHandler
* All middlewares

He following methods (when applicable) are removed;
* `setLogger()`

Instead these classes get the method;
* `setConfiguration(WebSocket\Configuration $configuration)`

## Extending

