[Documentation](Index.md) / [Middleware](Middleware.md) / PingResponder

# Websocket: PingResponder middleware

Thid middleware provide standard operability according to WebSocket protocol,
and should be added unless you write your own implementation of ping/pong handling.

* When a Ping message is received, PingResponder will respond with a Pong message

```php
$client_or_server->addMiddleware(new WebSocket\Middleware\PingResponder());
```
