[Documentation](Index.md) / Migration v3 -> v4

# Websocket: Migration v3 -> v4

Version 4.x has few changes compared to previous version.

## Breaking changes


## Removed deprecated code

* Client `getMeta()` - removed
* Client `onConnect()` - use `onHandshake()` instead
* Server `onConnect()` - use `onHandshake()` instead
* FrameHandler `setLogger()` - removed

## Extending

