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

## A self-resuming continuous subscription Client

This setup will create Client that sends initial message to Server,
and then subscribes to messages sent by Server.
The `PingInterval` (possibly change interval) will keep conneciton open.
If something goes wrong, it will in most cases be able to re-connect and resume subscription.

```php
use Psr\Http\Message\ResponseInterface;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Connection;
use WebSocket\Exception\Exception;
use WebSocket\Message\Message;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;
use WebSocket\Middleware\PingInterval;

// Create client
$client = new Client("wss://echo.websocket.org/");
$client
    // Add standard middlewares
    ->addMiddleware(new CloseHandler())
    ->addMiddleware(new PingResponder())
    // Add ping interval middleware as heartbeat to keep connection open
    ->addMiddleware(new PingInterval(interval: 30))
    ->onConnect(function (Client $client, Connection $connection, ResponseInterface $response) {
        // Initial message, typically some authorization or configuration
        // This will be called everytime the client connect or reconnect
        $client->text($initial_message);
    })
    ->onText(function (Client $client, Connection $connection, Message $message) {
        // Act on incoming message
        $message->getContent();
        // Possibly respond to server
        $client->text($some_message);
    })
    ->onError(function (Client $client, Connection|null $connection, Exception $exception) {
        // Act on exception
        if (!$client->isRunning()) {
            // Re-start if not running - will reconnect if necessary
            $client->start();
        }
    })
    // Start subscription
    ->start()
    ;
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
