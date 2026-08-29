[Documentation](Index.md) / Server

# Websocket: Server

The library contains a multi-connection listening server based on coroutine runtime.
It does not support full parallel processing through threads or separate processes.

## Basic operation

Below will set up a WebSocket server that listens to incoming text messages.
The added middlewares provide standard operability according to WebSocket protocol.

```php
$server = new WebSocket\Server();
$server
    // Add standard middlewares
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    // Listen to incoming Text messages
    ->onText(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Message $message) {
        // Act on incoming message
        echo "Got message: {$message->getContent()} \n";
        // Possibly respond to client
        $connection->text("I got your your message");
    })
    ->start();
```
Optionally, `start()` can take timeout argument as int or float.


## Middlewares

Middlewares provide additional functionality when sending or receiving messages.
This repo comes with two middlewares that provide standard operability according to WebSocket protocol.

* `CloseHandler` - Automatically acts on incoming and outgoing Close requests, as specified in WebSocket protocol
* `PingResponder` - Responds with Pong message when receiving a Ping message, as specified in WebSocket protocol

If not added, you need to handle close operation and respond to ping requests in your own implementation.

```php
$server = new WebSocket\Server();
$server
    // Add CloseHandler middleware
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    // Add PingResponder middleware
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    ;
```

Read more on [Middlewares](Middleware.md).

## Listeners

The message listeners are used by specifying a callback function that will be called
whenever the server receives a method of the same type.
All message listeners receive Server, Connection and Message as arguments.

```php
$server = new WebSocket\Server();
$server
    // Listen to incoming Text messages
    ->onText(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Text $message) {
        // Act on incoming message
    })
    // Listen to incoming Binary messages
    ->onBinary(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Binary $message) {
        // Act on incoming message
    })
    ->start();
    ;
```

Read more on [Listeners](Listener.md).

## Messages

WebSocket messages comes as any of five types; Text, Binary, Ping, Pong and Close.
The type is defined as opcode in WebSocket standard, and each classname corresponds to current message opcode.

Text and Binary are the main content message. The others are used for internal communication and typically do not contain content.
All provide the same methods, except Close that have an additional method not present on other types of messages.

```php
echo "opcode:       {$message->getOpcode()}\n";
echo "length:       {$message->getLength()}\n";
echo "timestamp:    {$message->getTimestamp()}\n";
echo "content:      {$message->getContent()}\n";
echo "close status: {$close->getCloseStatus()}\n";
```

Read more on [Messages](Message.md).

## Sending messages

The [Connection](Connection.md) instance represents the client-server connection.
To send a message to a client, call the `send()` method on Connection instance with a Message instance.
Any of the five message types can be sent this way.

```php
$connection->send(new WebSocket\Message\Text("Server sends a message"));
$connection->send(new WebSocket\Message\Binary($binary));
$connection->send(new WebSocket\Message\Ping("My ping"));
$connection->send(new WebSocket\Message\Text("My pong"));
$connection->send(new WebSocket\Message\Close(1000, "Closing now"));
```
There are also convenience methods available for all types.
```php
$connection->text("Server sends a message");
$connection->binary($binary);
$connection->ping("My ping");
$connection->pong("My pong");
$connection->close(1000, "Closing now");
```

## Broadcasting messages

The same send methods are available at Server instance.
Using these will send the message to all currently connected clients.

```php
$server->send(new WebSocket\Message\Text("Server sends a message"));
$server->send(new WebSocket\Message\Binary($binary));
$server->send(new WebSocket\Message\Ping("My ping"));
$server->send(new WebSocket\Message\Text("My pong"));
$server->send(new WebSocket\Message\Close(1000, "Closing now"));
```
Convenience methods available for all types.
```php
$server->text("Server sends a message");
$server->binary($binary);
$server->ping("My ping");
$server->pong("My pong");
$server->close(1000, "Closing now");
```

## Configuration

The Server has two main arguments; port and ssl.
By default, ssl is false. If port is not specified, it will use 80 for non-secure and 443 for secure server.

```php
__construct(
    int $port = 80,
    bool $ssl = false,
    WebSocket\Configuration|null $configuration = null,
    Phrity\Net\StreamFactory|null $streamFactory = null,
);
```

Other options are available using the Configuration class.

- Logger
- Context
- Timeout
- Frame size
- Max connections
- Opcode registry

Read more on [Configuration](Configuration.md).

### HTTP factories

By default the Server wraps a [PSR-7 HTTP message](https://www.php-fig.org/psr/psr-7/) implementation.
Other implementations can be used by setting [PSR-17 HTTP factories](https://www.php-fig.org/psr/psr-17/) on the Server.

Set a configured HttpFactory class on the Client.
```php
$factory = new Phrity\Http\HttpFactory(
    serverRequestFactory: $myServerRequestFactory,
    responseFactory: $myResponseFactory,
    uriFactory: $myUriFactory,
);
$server->setHttpFactory($factory);
```

Or if you use factories that support multiple interfaces, available in libraries such as `nyholm/psr7` or `guzzlehttp/psr7`.
```php
$psrFactory = new Nyholm\Psr7\Factory\Psr17Factory(); // Or any other PSR-17 factory
$server->setHttpFactory(Phrity\Http\HttpFactory::create($psrFactory));
```

### Server info

Additional methods that provides information.

```php
echo "port:   {$server->getPort()}\n";
echo "scheme: {$server->getScheme()}\n";
echo "ssl:    {$server->isSsl()}\n";
```

## Server control

When started, the server will continue to run until something tells it so stop.
There are some additional methods that control the server.

```php
// If server is currently running
$server->isRunning()

// Start server - It will continuously listen to incoming messages and apply specified callback functions
$server->start();

// Stop server - When called, server will no longer listen to incoming messages but will not disconnect clients
$server->stop();

// Orderly shutdown server - Will initiate close procedure on all connected clients and stop running when all are disconnected
$server->shutdown();

// Disconnect server - Server will immediately stop and disconnect all clients without normal close procedure
$server->disconnect();
```

## Connection control

```php
// Number of connected clients
$server->getConnectionCount();

// Get all current connections (may be in any state)
$server->getConnections();

// Get all readable connections
$server->getReadableConnections();

// Get all writable connections
$server->getWritableConnections();
```

Read more on [Connection](Connection.md).
