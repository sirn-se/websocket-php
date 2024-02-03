[Documentation](Index.md) / Listener

# Websocket: Listener

Both [Client](Client.md) and [Server](Server.md) support registering listeners as callback functions.
Listeners will be called when a message is received, a conneciton is opened and closed, and when an error occurs.
If you use the listener method `->start()` this will be the only way to act on incoming messages.

## Message listeners

The message listeners are called whenever the client or server receives a message of the same type.
All message listeners receive `Client|Server`, `Connection` and `Message` as arguments.

```php
$client_or_server
    // Listen to incoming Text messages
    ->onText(function (WebSocket\Client|WebSocket\Server $client_or_server, WebSocket\Connection $connection, WebSocket\Message\Text $message) {
        // Act on incoming message
    })
    // Listen to incoming Binary messages
    ->onBinary(function (WebSocket\Client|WebSocket\Server $client_or_server, WebSocket\Connection $connection, WebSocket\Message\Binary $message) {
        // Act on incoming message
    })
    // Listen to incoming Ping messages
    ->onPing(function (WebSocket\Client|WebSocket\Server $client_or_server, WebSocket\Connection $connection, WebSocket\Message\Ping $message) {
        // Act on incoming message
    })
    // Listen to incoming Pong messages
    ->onPong(function (WebSocket\Client|WebSocket\Server $client_or_server, WebSocket\Connection $connection, WebSocket\Message\Pong $message) {
        // Act on incoming message
    })
    // Listen to incoming Close messages
    ->onClose(function (WebSocket\Client|WebSocket\Server $client_or_server, WebSocket\Connection $connection, WebSocket\Message\Close $message) {
        // Act on incoming message
    })
    ;
```

## Connect and Disconnect listeners

These listeners are called when the Client or Server connects and disconnects.

* On Client, the `onConnect()` will receive a `Response` as last argument
* On Server, the `onConnect()` will receive a `ServerRequest` as last argument

```php
$client_or_server
    // Called when a connection is established
    ->onConnect(function (WebSocket\Client|WebSocket\Server $client_or_server WebSocket\Connection $connection, Psr\Http\Message\ServerRequestInterface|Psr\Http\Message\ResponseInterface $request_or_respone) {
        // Act on connect
    })
    // Called when a connection is closed
    ->onDisconnect(function (WebSocket\Client|WebSocket\Server $client_or_server, WebSocket\Connection $connection) {
        // Act on disconnect
    })
    ;
```

## Error listener

This listener is called if a resolvable error occurs.
Connection might be null. The Exception thrown is present as last argument.

```php
$client_or_server
    // When a resolvable error occurs, this listener will be called
    ->onError(function (WebSocket\Client|WebSocket\Server $client_or_server, WebSocket\Connection|null $connection, Exception $exception) {
        // Act on exception
    })
    ;
```

## Coroutine - The Tick listener

Using above functions, your Client and Server will be able to receive incoming messages and take action accordingly.

But what if your implementation need to process other data, and send unsolicited messages?
The coroutine implementation will regulary call the `onTick()` method, depending on workload and configuration.

```php
$client_or_server
    // Regulary called, regardless of connections
    ->onTick(function (WebSocket\Client|WebSocket\Server $client_or_server) {
        // Do anything
    })
    ;
```
