[Documentation](Index.md) / Examples

# Websocket: Examples

Here are some examples on how to use the WebSocket library.

##  Echo logger

In dev environment (as in having run composer to include dev dependencies) you have
access to a simple echo logger that print out information synchronously.

This is usable for debugging. For production, use a proper logger.

```php
$logger = new WebSocket\Test\EchoLog();

$client = new WebSocket\Client('wss://echo.websocket.org/');
$client->setLogger($logger);

$server = new WebSocket\Server();
$server->setLogger($logger);
```

An example of server output;
```
info     | Server listening to port 80 []
debug    | Wrote 129 of 129 bytes. []
info     | Server connected to port 80 []
info     | Received 'text' message []
debug    | Wrote 9 of 9 bytes. []
info     | Sent 'text' message []
debug    | Received 'close', status: 1000. []
debug    | Wrote 32 of 32 bytes. []
info     | Sent 'close' message []
info     | Received 'close' message []
```

## The `send` client

Source: [examples/send.php](../examples/send.php)

A simple, single send/receive client.

Example use:
```
php examples/send.php --opcode text "A text message" // Send a text message to localhost
php examples/send.php --opcode ping "ping it" // Send a ping message to localhost
php examples/send.php --uri ws://echo.websocket.org "A text message" // Send a text message to echo.websocket.org
php examples/send.php --opcode text --debug "A text message" // Use runtime debugging
```

## The `echoserver` server

Source: [examples/echoserver.php](../examples/echoserver.php)

A simple server that responds to recevied commands.

Example use:
```
php examples/echoserver.php // Run with default settings
php examples/echoserver.php --port 8080 // Listen on port 8080
php examples/echoserver.php --debug //  Use runtime debugging
```

These strings can be sent as message to trigger server to perform actions;
* `@close` -  Server will close current connection
* `@ping` - Server will send a ping message on current connection
* `@disconnect` - Server will disconnect current connection
* `@info` - Server will respond with connection info
* `@server-stop` - Server will stop listening
* `@server-close` - Server will close all connections
* `@server-ping` - Server will send a ping message on all connections
* `@server-disconnect` - Server will disconnect all connections
* `@server-info` - Server will respond with server info
* For other sent strings, server will respond with the same strings

## The `random` client

Source: [examples/random_client.php](../examples/random_client.php)

The random client will use random options and continuously send/receive random messages.

Example use:
```
php examples/random_client.php --uri ws://echo.websocket.org // Connect to echo.websocket.org
php examples/random_client.php --timeout 5 --framesize 16 // Specify settings
php examples/random_client.php --debug //  Use runtime debugging
```

## The `random` server

Source: [examples/random_server.php](../examples/random_server.php)

The random server will use random options and continuously send/receive random messages.

Example use:
```
php examples/random_server.php --port 8080 // // Listen on port 8080
php examples/random_server.php --timeout 5 --framesize 16 // Specify settings
php examples/random_server.php --debug //  Use runtime debugging
```
