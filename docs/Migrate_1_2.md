[Documentation](Index.md) / Migration v1 -> v2

# Websocket: Migration v1 -> v2

Version `2.x` has significant changes compared to previous version.
Amongst other things, it introduces callback listeners, middleware support, and the Server now support multiple connections.

## Constructor and configuration

Where `1.x` was configured by using an array of options on the constructor,
`2.x` provides a number of configuration methods instead.
Replace configuration with a call to corresponding method instead.

```php
new WebSocket\Client(string $uri)
new WebSocket\Server(bool $ssl, int $port)

setLogger(Psr\Log\LoggerInterface $logger) // logger
setTimeout(int $timeout) // timeout
setFrameSize(int $frameSize) // fragment_size
setPersistent(bool $persistent) // persistent
setContext(array $context_as_array) // context
addHeader(string $name, string $value) // headers
```

The `filter` and `return_obj` are no longer valid.

## Middlewares

`2.x` introduces middlewares to extend functionality to Client and Server.
Two of the middlewares cover functionality built-in in `1.x` and should be added unless you create your own code instead.

```php
addMiddleware(new WebSocket\Middleware\CloseHandler())
addMiddleware(new WebSocket\Middleware\PingResponder())
```

## Receiving messages

The Client `receive()` method always return an instance of Message (Text, Binary, Ping, Pong or Close).
This corresponds to setting `return_obj: true` in `1.x` configuration. By default `1.x` returned string.

You are encouraged to read incoming messages using [listener callback methods](https://github.com/sirn-se/websocket-php/blob/2.0.0/docs/Client.md#subscribe-operation) instead.

The Server no longer has the `receive()` method.
Receiving, use [listener callback methods](https://github.com/sirn-se/websocket-php/blob/2.0.0/docs/Server.md#message-listeners).

## Sending messages

The `send(...)` method no longer accepts message and opcode as strings,
but only accepts an instance of Message (Text, Binary, Ping, Pong or Close).
However, there are convenience methods available as `text(...)`, `binary(...)`, `ping(...)`, `pong(...)` and `close(...)`.

## More?

As `2.x` has significant internal code changes, other classes and methods are likely to have changed.
