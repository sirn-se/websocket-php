[Documentation](Index.md) / Error handling

# Websocket: Error handling

## WebSocket errors

Exceptions can occur on three levels; Message, Connection and root.
All internal errors has namespace `WebSocket\Exception` and implement `ExceptionInterface`.

### Message errors

Indicates that a message could not be sent or received.
The message affected is dropped as invalid but the connection will remain open for future send and receive actions.
All exceptions on this level implement `MessageLevelInterface`.

- `BadOpcodeException` - When a message has an invalid opcode
- `ConnectionTimeoutException` - Read or write operation on stream has timed out (see timeout configuration)
- `MessageEncodingException` - When message content could not be encoded or decoded, typically refers to compressed messages

### Connection errors

Indicates that the connection has failed, closed or must be closed for some reason.
The connection is no longer usable, but could possibly be reconnected.
All exceptions on this level implement `ConnectionLevelInterface`.

- `ConnectionClosedException` - The underlying stream has closed
- `ConnectionFailureException` - The underlying stream has failed for unknown reason
- `HandshakeException` - The handshake procedure has failed

### Handler errors

These errors indicate some critical error that can not be recovered.
All exceptions on this level implement `HandlerLevelInterface`.

- `BadUriException` - The provided URI is invalid
- `ClientException` - The Client failed to connect to server
- `ServerException` - The Server failed to open server socket

### Control flow exceptions

These exceptions indicate an out-of-flow action must be performed.
All exceptions on this level implement `ControlLevelInterface`.

- `CloseException` - Connection must close
- `ReconnectException` - Connection must reconnect

## Additional exceptions

These standard exceptions typically indicate a configuration error.

- `BadMethodCallException`
- `InvalidArgumentException`
- `RangeException`
- `RuntimeException`

## Handling errors

When running the [listener](Listener.md), internal errors are caught, handled and forwarded to the `onError()` callback when possible.

When using the `send()` and `receive()` methods, calling code need to deal with any exceptions thrown.
