[Client](Client.md) • Server • [Message](Message.md) • [Classes](Classes.md) • [Examples](Examples.md) • [Changelog](Changelog.md) • [Contributing](Contributing.md)

# Websocket: Server

The library contains a multi-connection listening server based on coroutine runtime.
It does not, however, support full parallell processing through threads or separate processes.

## Basic operation

Below will set up a rudimentary WebSocket server that listens to incoming text messages.
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

## Configuration

The Server takes two arguments; port (default is 8000) and if it should use secure connection (default is no).
Other options are avialble runtime by calling configuration methods.

```php
// Secure server on port 8080
$server = new WebSocket\Server(8080, true);
$server
    // Use a PSR-3 compatible logger
    ->setLogger(Psr\Log\LoggerInterface $logger)
    // Specify timeout in seconds (default 60s)
    ->setTimeout(300)
    // Specify frame size in bytes (default 4096b)
    ->setFrameSize(1024)
    ;

echo "port:         {$server->getPort()}\n";
echo "scheme:       {$server->getScheme()}\n";
echo "timeout:      {$server->getTimeout()}s\n";
echo "frame size:   {$server->getFrameSize()}b\n";
echo "running:      {$server->isRunning()}\n";
echo "connections:  {$server->getConnectionCount()}\n";
```

## Middlewares

Middlewares provide additional functionality when sending or receiving messages.
This repo comes with two middlewares that provide standard operability according to WebSocket protocol.

* CloseHandler - Automatically acts on incoming and outgoing Close requests, as specified in WebSocket protocol
* PingResponder - Responds with Pong message when receiving a Ping message, as specified in WebSocket protocol

If not present you would need to handle close operation and respond to ping requests in your own implementation.

```php
$server = new WebSocket\Server();
$server
    // Add CloseHandler middleware
    ->addMiddleware(new WebSocket\Middleware\CloseHandler())
    // Add PingResponder middleware
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    ;
```

Read more on [Middlewares](Middlewares.md).

## Message listeners

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
    ->onBinary(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Binary $message) {
        // Act on incoming message
    })
    ->onPing(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Ping $message) {
        // Act on incoming message
    })
    ->onPong(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Pong $message) {
        // Act on incoming message
    })
    ->onClose(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Close $message) {
        // Act on incoming message
    })
    ;
```

## Messages

WebSocket messages comes as any of five types; Text, Binary, Ping, Pong and Close.
The type is defined as opcode in WebSocket standard, and each classname corresponds to current message opcode.

Text and Binary are the main content message. The others are used for internal communication and typically do not contain content.
All provide the same metods, excpet Close that have an additional methods not present on other types of messages.

```php
echo "opcode:       {$message->getOpcode()}\n";
echo "length:       {$message->getLength()}\n";
echo "timestamp:    {$message->getTimestamp()}\n";
echo "content:      {$message->getContent()}\n";
echo "close status: {$close->getCloseStatus()}\n";
```

Read more on [Messages](Messages.md).

## Sending a message to connected client

The Connection instance represents the client-server channel.
To send a message to a client, call the send() method on Connection instance with a Message instance.
Any of the five message types can be sent this way.

```php
$connection->send(new WebSocket\Message\Text("Server sends a message"));
$connection->send(new WebSocket\Message\Binary($binary));
$connection->send(new WebSocket\Message\Ping("My ping"));
$connection->send(new WebSocket\Message\Text("My pong"));
$connection->send(new WebSocket\Message\Close(1000, "Closing now"));
```
The are also convenience methods available for for all types.
```php
$connection->text("Server sends a message");
$connection->binary($binary);
$connection->ping("My ping");
$connection->pong("My pong");
$connection->close(1000, "Closing now");
```

## Broadcasting message to all connects clients

The same send methods are available at Server instance.
Using these will send the message to all currently connected clients.

```php
$server->send(new WebSocket\Message\Text("Server sends a message"));
$server->send(new WebSocket\Message\Binary($binary));
$server->send(new WebSocket\Message\Ping("My ping"));
$server->send(new WebSocket\Message\Text("My pong"));
$server->send(new WebSocket\Message\Close(1000, "Closing now"));
```
```php
$server->text("Server sends a message");
$server->binary($binary);
$server->ping("My ping");
$server->pong("My pong");
$server->close(1000, "Closing now");
```

## Server control

When started, the server will continue to run until something tells it so stop.
There are some additional methods that control the server.

Start server - It will continuously listen to incoming messages and apply specified callback functions.
```php
$server->start();
```

Stop server - When called, server will no longer listen to incoming messages but will not disconnect clients.
```php
$server->stop();
```

Disconnect server - Server will immediately stop and disconnect all clients without normal close procedure.
```php
$server->disconnect();
```

## Connection control

The Connection instance have some additional functions, besides sending messages to client.

```php
// Is connection open?
$connection->isConnected();

// Immediately disconnect client without normal close procedure
$connection->disconnect();

// Get local name for connection
$connection->getName();

// Get remote name for connection
$connection->getRemoteName();

// Get the Request client sent during handshake procedure
$connection->getHandshakeRequest();

// Get the Response server sent during handshake procedure
$connection->getHandshakeResponse();
```

Read more on [Connection](Connection.md).


## Connect, Disconnect and Error listeners

Some additional listeners are available for more advanced features.

```php
$server = new WebSocket\Server();
$server
    // Called when a client is connected
    ->onConnect(function (WebSocket\Server $server, WebSocket\Connection $connection, Psr\Http\Message\ServerRequestInterface $request) {
        // Act on incoming message
    })
    // Called when a client is disconnected
    ->onDisconnect(function (WebSocket\Server $server, WebSocket\Connection $connection) {
        // Act on disconnect
    })
    // When resolvable error occurs, this listener will be called
    ->onError(function (WebSocket\Server $server, WebSocket\Connection|null $connection, Exception $exception) {
        // Act on exception
    })
    ;
```

## Coroutine - The Tick listener

Using above functions, your server will be able to receive incoming messages and take action accordingly.

But what if your server need to process other data, and send unsolicited message to connected clients?
This is where coroutine pattern enters the picture.
The server might not be able to do things in parallell,
but it can give you space to run additional code not necessarily triggered by an incoming message.

Depending on workload and timeout configuration, the Tick listener will be called every now and then.

```php
$server = new WebSocket\Server();
$server
    // Called when a client is connected
    ->onTick(function (WebSocket\Server $server) {
        // Do anything
    })
    ;
```


## Exceptions

* `WebSocket\BadOpcodeException` - Thrown if provided opcode is invalid.
* `WebSocket\ConnectionException` - Thrown on any socket I/O failure.
* `WebSocket\TimeoutException` - Thrown when the socket experiences a time out.
