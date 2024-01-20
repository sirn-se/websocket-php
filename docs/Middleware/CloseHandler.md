[Documentation](../Index.md) / [Middleware](../Middleware.md) / CloseHandler

# Websocket: CloseHandler middleware

Thid middleware provide standard operability according to WebSocket protocol,
and should be added unless you write your own implementation of close handling.

* When a Close message is received, CloseHandler will respond with a Close confirmation message
* When a Close confirmation message is received, CloseHandler will close the connection
* When a Close message is sent, CloseHandler will block further messages from being sent

```php
$client_or_server->addMiddleware(new WebSocket\Middleware\CloseHandler());
```
