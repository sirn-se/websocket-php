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
* FrameHandler `setLogger()` - removed

## Extending

