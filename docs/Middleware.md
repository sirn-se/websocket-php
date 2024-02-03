[Documentation](Index.md) / Middleware

# Websocket: Middleware

Both [Client](Client.md) and [Server](Server.md) support adding middlewares.
All added middlewares will be called when certain actions are performed.

Add middlewares by calling the `addMiddleware` method.

```php
$client
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    ;

$server
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    ;
```

## Standard middlewares

These two middlewares provide standard operability according to WebSocket protocol,
and should be added unless you write your own implementation of close and ping/pong handling.

* [CloseHandler](Middleware/CloseHandler.md) - Automatically acts on incoming and outgoing Close requests, as specified in WebSocket protocol
* [PingResponder](Middleware/PingResponder.md) - Responds with Pong message when receiving a Ping message, as specified in WebSocket protocol

## Optional middlewares

These middlewares are included in library and can be added to provide additional functionality.

* [PingInterval](Middleware/PingInterval.md) - Used to automatically send Ping messages at specified interval
* [Callback](Middleware/Callback.md) - Apply provided callback function on specified actions

## Creating your own middleware

You can create your own middleware by implementing relevant interfaces.
A middleware may handle WebSocket message transfers, HTTP handshake operations, and Tick operability.

* [Creating](Middleware/Creating.md) - How to create a Middleware
